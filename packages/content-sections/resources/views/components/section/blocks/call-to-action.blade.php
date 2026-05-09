@props(['asset', 'linkText' => null, 'meta' => [], 'summary' => null, 'title' => null, 'url' => null])

@php
    $actions = is_array($meta['actions'] ?? null) ? $meta['actions'] : [];
    $alignment = $meta['alignment'] ?? 'center';
@endphp

<section
    @class([
        'section section-call-to-action',
        'text-left' => $alignment === 'start',
        'text-center' => $alignment === 'center',
        'text-right' => $alignment === 'end',
        $attributes->get('class'),
    ])
>
    @if ($title)
        <h2 class="text-3xl font-bold">{{ $title }}</h2>
    @endif

    @if ($summary)
        <div class="mx-auto mt-4 max-w-3xl text-lg opacity-80">
            {!! $summary !!}
        </div>
    @endif

    @if ($actions !== [] || ($url && $linkText))
        <div
            class="@if ($alignment === 'center') justify-center @elseif ($alignment === 'end') justify-end @endif mt-6 flex flex-wrap gap-3"
        >
            @foreach ($actions as $action)
                @if (($action['type'] ?? '') === 'public_action' && Route::has('capell-public-actions.submit') && filled($action['public_action_key'] ?? null))
                    @php
                        $payload = array_filter([
                            'area' => $action['access_gate_area'] ?? null,
                            'requested_url' => url()->current(),
                            'redirect' => $action['redirect'] ?? null,
                            'source_type' => 'section_action',
                        ], static fn (mixed $payloadValue): bool => $payloadValue !== null && $payloadValue !== '');
                    @endphp

                    <form
                        method="post"
                        action="{{ route('capell-public-actions.submit', ['action' => $action['public_action_key']]) }}"
                        class="inline-flex"
                    >
                        @csrf

                        @foreach ($payload as $payloadKey => $payloadValue)
                            @if (is_string($payloadKey) && is_scalar($payloadValue) && filled((string) $payloadValue))
                                <input
                                    type="hidden"
                                    name="{{ $payloadKey }}"
                                    value="{{ (string) $payloadValue }}"
                                />
                            @endif
                        @endforeach

                        <button
                            type="submit"
                            class="inline-flex rounded bg-slate-950 px-5 py-3 font-semibold text-white"
                        >
                            {{ $action['label'] ?? '' }}
                        </button>
                    </form>
                    @continue
                @endif

                @if (($action['type'] ?? '') === 'public_action')
                    @continue
                @endif

                <a
                    href="{{ $action['url'] ?? '#' }}"
                    class="inline-flex rounded bg-slate-950 px-5 py-3 font-semibold text-white"
                >
                    {{ $action['label'] ?? '' }}
                </a>
            @endforeach

            @if ($actions === [] && $url && $linkText)
                <a
                    href="{{ $url }}"
                    class="inline-flex rounded bg-slate-950 px-5 py-3 font-semibold text-white"
                >
                    {{ $linkText }}
                </a>
            @endif
        </div>
    @endif
</section>
