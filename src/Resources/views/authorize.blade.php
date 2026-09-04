<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('mcp::app.authorize.title', ['client' => $client->name])
    </x-slot>

    <div class="flex min-h-screen w-full items-center justify-center p-6 bg-gradient-to-b from-primary-50 to-gray-50 dark:from-cherry-900 dark:to-cherry-900">
        <div class="w-full max-w-[400px]">
            <div class="mb-8 flex justify-center">
                @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                    <img
                        class="h-10"
                        src="{{ Storage::url($logo) }}"
                        alt="{{ config('app.name') }}"
                    />
                @else
                    {{-- Default UnoPim logo — swaps with the theme. --}}
                    <img
                        class="h-10 w-max dark:hidden"
                        src="{{ unopim_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    />

                    <img
                        class="h-10 w-max hidden dark:block"
                        src="{{ unopim_asset('images/dark_logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    />
                @endif
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-cherry-700 bg-white dark:bg-cherry-800 shadow-[0_8px_30px_rgba(0,0,0,0.08)] p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    @lang('mcp::app.authorize.heading', ['client' => $client->name])
                </h1>

                <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">
                    @lang('mcp::app.authorize.lede')
                </p>

                <div class="mb-6 flex items-center gap-3 rounded-lg border border-gray-100 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900 p-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-unopim-avatar font-semibold uppercase text-white">
                        {{ mb_substr($user->email, 0, 1) }}
                    </span>

                    <span class="min-w-0">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            @lang('mcp::app.authorize.signed-in-as')
                        </span>

                        <span class="block truncate font-medium text-gray-800 dark:text-white">
                            {{ $user->email }}
                        </span>
                    </span>
                </div>

                @if (count($scopes) > 0)
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        @lang('mcp::app.authorize.permissions')
                    </p>

                    <ul class="mb-6 grid gap-2">
                        @foreach ($scopes as $scope)
                            <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <span class="shrink-0 icon-done text-lg leading-none text-primary-700 dark:text-primary-400"></span>

                                <span>{{ $scope->description }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex gap-3">
                    <form
                        method="POST"
                        action="{{ route('passport.authorizations.deny') }}"
                        class="flex-1"
                    >
                        @csrf
                        @method('DELETE')

                        <input type="hidden" name="state" value="{{ $request->input('state') }}">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">

                        <button
                            type="submit"
                            class="secondary-button w-full justify-center py-2.5"
                            aria-label="@lang('mcp::app.authorize.deny')"
                        >
                            @lang('mcp::app.authorize.deny')
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('passport.authorizations.approve') }}"
                        class="flex-1"
                    >
                        @csrf

                        <input type="hidden" name="state" value="{{ $request->input('state') }}">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">

                        <button
                            type="submit"
                            class="primary-button w-full justify-center py-2.5"
                            aria-label="@lang('mcp::app.authorize.approve')"
                        >
                            @lang('mcp::app.authorize.approve')
                        </button>
                    </form>
                </div>

                <p class="mt-5 text-center text-xs text-gray-500 dark:text-gray-400">
                    @lang('mcp::app.authorize.footnote')
                </p>
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
