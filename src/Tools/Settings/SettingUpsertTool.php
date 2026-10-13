<?php

namespace Webkul\MCP\Tools\Settings;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class SettingUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more settings (Channels or Locales) in UnoPim. Automatically determines create vs update based on code existence. A new channel needs root_category_id, locales and currencies; an update only changes the fields you send.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_settings';

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
        protected CurrencyRepository $currencyRepository,
    ) {}

    protected function execute(Request $request): Response
    {
        $request->validate([
            'type' => ['required', 'string', 'in:channels,locales'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array'],
            'items.*.code' => ['required', 'string', 'max:100'],
        ]);

        $type = $request->get('type');
        $repository = $type === 'channels' ? $this->channelRepository : $this->localeRepository;

        $existing = [];

        foreach ($request->get('items') as $index => $item) {
            $existing[$index] = $repository->findOneByField('code', $item['code']);
        }

        $validated = $request->validate($type === 'channels'
            ? $this->channelRules($existing)
            : $this->localeRules($existing));

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['items'] as $index => $itemData) {
                $results[] = $type === 'channels'
                    ? $this->upsertChannel($itemData, $existing[$index])
                    : $this->upsertLocale($itemData, $existing[$index]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json([
            'success' => true,
            'type' => $type,
            'results' => $results,
        ]);
    }

    /**
     * Channel rules mirror the admin channel form: a new channel needs a root category,
     * locales and currencies, while an update only validates the fields it sends.
     *
     * @param  array<int|string, Model|null>  $existing
     * @return array<string, mixed>
     */
    protected function channelRules(array $existing): array
    {
        $rules = [
            'items' => ['required', 'array'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.labels' => ['nullable', 'array', $this->activeLocaleKeys()],
            'items.*.labels.*' => ['nullable', 'string', 'max:255'],
        ];

        foreach ($existing as $index => $channel) {
            $presence = $channel ? 'sometimes' : 'required';

            if (! $channel) {
                $rules["items.{$index}.code"] = ['required', 'string', new Code, Rule::unique('channels', 'code')];
            }

            $rules["items.{$index}.root_category_id"] = [$presence, 'integer', Rule::exists('categories', 'id')->whereNull('parent_id')];
            $rules["items.{$index}.locales"] = [$presence, 'array', 'min:1', $this->resolvable($this->localeRepository)];
            $rules["items.{$index}.currencies"] = [$presence, 'array', 'min:1', $this->resolvable($this->currencyRepository)];
        }

        return $rules;
    }

    /**
     * Locale rules mirror the admin locale form, and like it refuse to disable a locale
     * that a channel or user still uses.
     *
     * @param  array<int|string, Model|null>  $existing
     * @return array<string, mixed>
     */
    protected function localeRules(array $existing): array
    {
        $rules = [
            'items' => ['required', 'array'],
            'items.*.code' => ['required', 'string', 'max:100'],
        ];

        foreach ($existing as $index => $locale) {
            if (! $locale) {
                $rules["items.{$index}.code"] = ['required', 'string', new Code, Rule::unique('locales', 'code')];
                $rules["items.{$index}.status"] = ['sometimes', 'boolean'];

                continue;
            }

            $rules["items.{$index}.status"] = ['sometimes', 'boolean', function (string $attribute, mixed $value, Closure $fail) use ($locale): void {
                if (! filter_var($value, FILTER_VALIDATE_BOOLEAN) && $this->localeRepository->checkLocaleBeingUsed($locale->id)) {
                    $fail(trans('admin::app.settings.locales.index.can-not-disable-error'));
                }
            }];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $itemData
     * @return array{id: int, code: string, action: string}
     */
    protected function upsertChannel(array $itemData, ?Model $channel): array
    {
        $data = $this->channelTranslations($itemData);

        if (isset($itemData['root_category_id'])) {
            $data['root_category_id'] = (int) $itemData['root_category_id'];
        }

        if ($channel instanceof Model) {
            $data['locales'] = isset($itemData['locales'])
                ? $this->resolveIds($this->localeRepository, $itemData['locales'])
                : $channel->locales()->pluck('locales.id')->all();

            $data['currencies'] = isset($itemData['currencies'])
                ? $this->resolveIds($this->currencyRepository, $itemData['currencies'])
                : $channel->currencies()->pluck('currencies.id')->all();

            Event::dispatch('core.channel.update.before', $channel->id);

            $channel = $this->channelRepository->update($data, $channel->id);

            Event::dispatch('core.channel.update.after', $channel);

            return ['id' => $channel->id, 'code' => $channel->code, 'action' => 'updated'];
        }

        $data['code'] = $itemData['code'];
        $data['locales'] = $this->resolveIds($this->localeRepository, $itemData['locales']);
        $data['currencies'] = $this->resolveIds($this->currencyRepository, $itemData['currencies']);

        Event::dispatch('core.channel.create.before');

        $channel = $this->channelRepository->create($data);

        Event::dispatch('core.channel.create.after', $channel);

        return ['id' => $channel->id, 'code' => $channel->code, 'action' => 'created'];
    }

    /**
     * @param  array<string, mixed>  $itemData
     * @return array{id: int, code: string, action: string}
     */
    protected function upsertLocale(array $itemData, ?Model $locale): array
    {
        $data = array_intersect_key($itemData, array_flip($locale instanceof Model ? ['status'] : ['code', 'status']));

        if (array_key_exists('status', $data)) {
            $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($locale instanceof Model) {
            $locale = $this->localeRepository->update($data, $locale->id);

            return ['id' => $locale->id, 'code' => $locale->code, 'action' => 'updated'];
        }

        $locale = $this->localeRepository->create($data);

        return ['id' => $locale->id, 'code' => $locale->code, 'action' => 'created'];
    }

    /**
     * Per-locale names keyed the way the channel repository expects; `labels` wins over
     * `name`, which fills every other active locale.
     *
     * @param  array<string, mixed>  $itemData
     * @return array<string, array{name: string|null}>
     */
    protected function channelTranslations(array $itemData): array
    {
        $translations = [];

        foreach (core()->getAllActiveLocales() as $locale) {
            if (array_key_exists($locale->code, $itemData['labels'] ?? [])) {
                $translations[$locale->code] = ['name' => $itemData['labels'][$locale->code]];
            } elseif (isset($itemData['name'])) {
                $translations[$locale->code] = ['name' => $itemData['name']];
            }
        }

        return $translations;
    }

    /**
     * Turn a list of codes or ids into the matching record ids.
     *
     * @param  array<int, mixed>  $values
     * @return list<int>
     */
    protected function resolveIds(Repository $repository, array $values): array
    {
        $ids = array_values(array_filter($values, fn ($value): bool => is_int($value) || (is_string($value) && ctype_digit($value))));
        $codes = array_values(array_filter($values, fn ($value): bool => is_string($value) && ! ctype_digit($value)));

        $resolved = [];

        if ($ids !== []) {
            $resolved = $repository->findWhereIn('id', array_map(intval(...), $ids), ['id'])->pluck('id')->all();
        }

        if ($codes !== []) {
            $resolved = array_merge($resolved, $repository->findWhereIn('code', $codes, ['id'])->pluck('id')->all());
        }

        return array_values(array_unique(array_map(intval(...), $resolved)));
    }

    /**
     * A rule that fails unless every entry is the code or id of an existing record.
     */
    protected function resolvable(Repository $repository): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($repository): void {
            $values = array_values(array_unique(array_filter((array) $value, is_scalar(...))));

            if (count($values) !== count((array) $value) || count($this->resolveIds($repository, $values)) !== count($values)) {
                $fail('validation.exists')->translate(['attribute' => $attribute]);
            }
        };
    }

    /**
     * A rule that fails when a labels map uses a key that is not an active locale code.
     */
    protected function activeLocaleKeys(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $active = core()->getAllActiveLocales()->pluck('code')->all();

            if (array_diff(array_keys((array) $value), $active) !== []) {
                $fail('validation.in')->translate(['attribute' => $attribute]);
            }
        };
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('The type of settings to upsert (channels or locales).')
                ->enum(['channels', 'locales'])
                ->required(),
            'items' => $schema->array()
                ->description('Array of settings to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The unique code (identifier).')->required(),
                        'status' => $schema->boolean()->description('Locales only: whether the locale is enabled. A locale used by a channel or user cannot be disabled.'),
                        'name' => $schema->string()->description('Channels only: the channel name, applied to every active locale not given in labels.'),
                        'labels' => $schema->object()->description('Channels only: channel names keyed by active locale code, e.g. {"en_US": "Web"}.'),
                        'root_category_id' => $schema->integer()->description('Channels only: id of a root category. Required for a new channel.'),
                        'locales' => $schema->array()->items($schema->string())->description('Channels only: locale codes or ids. Required for a new channel; replaces the channel\'s locales when sent.'),
                        'currencies' => $schema->array()->items($schema->string())->description('Channels only: currency codes or ids. Required for a new channel; replaces the channel\'s currencies when sent.'),
                    ])
                )->required(),
        ];
    }
}
