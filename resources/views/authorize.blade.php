{{--
    OAuth authorization consent screen.

    Passport 13 no longer ships a view for this and offers nothing publishable,
    so the package registers this one via Passport::authorizationView(). Publish
    it with `php artisan vendor:publish --tag=mcp-views` to restyle it, or bind
    your own view before the package boots and it will be left alone.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Authorization Request') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f5f4ff; color: #1c1a2e;
        }
        .card {
            width: min(28rem, 92vw); background: #fff; border-radius: 14px; padding: 2rem;
            box-shadow: 0 10px 40px rgba(28, 26, 46, .12);
        }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        p { margin: .5rem 0 0; line-height: 1.5; color: #4a4763; }
        .client { font-weight: 600; color: #1c1a2e; }
        ul { margin: 1rem 0 0; padding-left: 1.1rem; color: #4a4763; }
        .actions { display: flex; gap: .75rem; margin-top: 1.75rem; }
        form { flex: 1; margin: 0; }
        button {
            width: 100%; padding: .7rem 1rem; border-radius: 9px; border: 0; cursor: pointer;
            font-size: .95rem; font-weight: 600;
        }
        .approve { background: #6d54ff; color: #fff; }
        .deny { background: #eceaf6; color: #1c1a2e; }
        @media (prefers-color-scheme: dark) {
            body { background: #16151f; color: #f2f1f7; }
            .card { background: #1f1e2b; box-shadow: none; }
            p, ul { color: #b6b3c9; }
            .client { color: #f2f1f7; }
            .deny { background: #2b2939; color: #f2f1f7; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('Authorization Request') }}</h1>

        <p>
            <span class="client">{{ $client->name }}</span>
            {{ __('is requesting permission to access your account') }}@if (! empty($user->email))
                ({{ $user->email }})@endif.
        </p>

        @if (count($scopes) > 0)
            <p>{{ __('This application will be able to:') }}</p>

            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">{{ __('Authorize') }}</button>
            </form>

            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">{{ __('Cancel') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
