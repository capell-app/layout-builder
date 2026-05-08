<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex, nofollow" />
        <title>
            {{ __('capell-access-gate::public.request.title', ['area' => $area->name]) }}
            
        </title>
        <style>
            :root {
                color-scheme: light dark;
                --access-gate-bg: #f4f7f6;
                --access-gate-panel: #ffffff;
                --access-gate-text: #1f2933;
                --access-gate-muted: #5f6b78;
                --access-gate-border: #d8dfdc;
                --access-gate-accent: #165a4a;
                --access-gate-accent-hover: #12483c;
                --access-gate-accent-text: #ffffff;
                --access-gate-error: #a53f3f;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --access-gate-bg: #151817;
                    --access-gate-panel: #202622;
                    --access-gate-text: #f2f0ea;
                    --access-gate-muted: #aeb8b1;
                    --access-gate-border: #3a443f;
                    --access-gate-accent: #9fd6c2;
                    --access-gate-accent-hover: #b9e7d6;
                    --access-gate-accent-text: #10201a;
                    --access-gate-error: #f0a0a0;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                display: grid;
                place-items: center;
                padding: 24px;
                background: var(--access-gate-bg);
                color: var(--access-gate-text);
                font-family:
                    ui-sans-serif,
                    system-ui,
                    -apple-system,
                    BlinkMacSystemFont,
                    'Segoe UI',
                    sans-serif;
            }

            main {
                width: min(100%, 460px);
                padding: 32px;
                border: 1px solid var(--access-gate-border);
                border-radius: 8px;
                background: var(--access-gate-panel);
            }

            h1 {
                margin: 0 0 12px;
                font-size: 28px;
                line-height: 1.15;
                letter-spacing: 0;
            }

            p {
                margin: 0 0 24px;
                color: var(--access-gate-muted);
                line-height: 1.55;
            }

            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 650;
            }

            input {
                width: 100%;
                min-height: 46px;
                padding: 10px 12px;
                border: 1px solid var(--access-gate-border);
                border-radius: 6px;
                background: transparent;
                color: var(--access-gate-text);
                font: inherit;
            }

            button,
            .method {
                width: 100%;
                min-height: 46px;
                border-radius: 6px;
                font: inherit;
                font-weight: 700;
            }

            button {
                margin-top: 16px;
                border: 0;
                background: var(--access-gate-accent);
                color: var(--access-gate-accent-text);
                cursor: pointer;
            }

            button:hover,
            .method-primary:hover {
                background: var(--access-gate-accent-hover);
            }

            .method {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 16px;
                padding: 12px;
                border: 1px solid var(--access-gate-border);
                color: var(--access-gate-text);
                text-align: center;
                text-decoration: none;
            }

            .method-primary {
                border-color: transparent;
                background: var(--access-gate-accent);
                color: var(--access-gate-accent-text);
            }

            .method-description {
                margin: 8px 0 0;
                font-size: 14px;
            }

            .separator {
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 24px 0;
                color: var(--access-gate-muted);
                font-size: 14px;
            }

            .separator::before,
            .separator::after {
                content: '';
                flex: 1;
                height: 1px;
                background: var(--access-gate-border);
            }

            .notice {
                margin-bottom: 18px;
                padding: 12px;
                border: 1px solid var(--access-gate-border);
                border-radius: 6px;
                color: var(--access-gate-text);
            }

            .error {
                margin-top: 8px;
                color: var(--access-gate-error);
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <main>
            <h1>
                {{ __('capell-access-gate::public.request.heading', ['area' => $area->name]) }}
            </h1>
            <p>{{ __('capell-access-gate::public.request.intro') }}</p>

            @if (session('access_gate_status'))
                <div class="notice">{{ session('access_gate_status') }}</div>
            @endif

            @foreach ($requestMethods as $requestMethod)
                <a
                    class="method {{ $requestMethod->primary ? 'method-primary' : '' }}"
                    href="{{ $requestMethod->url }}"
                >
                    {{ $requestMethod->label }}
                </a>
                @if ($requestMethod->description !== null)
                    <p class="method-description">
                        {{ $requestMethod->description }}
                    </p>
                @endif
            @endforeach

            @if ($emailRequestEnabled && $requestMethods->isNotEmpty())
                <div class="separator">
                    {{ __('capell-access-gate::public.request.or_email') }}
                </div>
            @endif

            @if ($emailRequestEnabled)
                <form
                    method="post"
                    action="{{ route('capell-access-gate.request.store', ['area' => $area->key]) }}"
                >
                    @csrf
                    <input
                        type="hidden"
                        name="requested_url"
                        value="{{ old('requested_url', $requestedUrl) }}"
                    />

                    <label for="access-gate-email">
                        {{ __('capell-access-gate::public.request.email') }}
                    </label>
                    <input
                        id="access-gate-email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    />
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror

                    @foreach ($fields as $field)
                        <label for="access-gate-field-{{ $field->key() }}">
                            {{ $field->label() }}
                        </label>
                        <input
                            id="access-gate-field-{{ $field->key() }}"
                            name="{{ $field->key() }}"
                            type="text"
                            value="{{ old($field->key()) }}"
                        />
                        @error($field->key())
                            <div class="error">{{ $message }}</div>
                        @enderror
                    @endforeach

                    <button type="submit">
                        {{ __('capell-access-gate::public.request.submit') }}
                    </button>
                </form>
            @endif
        </main>
    </body>
</html>
