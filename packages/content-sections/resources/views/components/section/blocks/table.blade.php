@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $headers = is_array($meta['headers'] ?? null) ? $meta['headers'] : [];
    $rows = is_array($meta['rows'] ?? null) ? $meta['rows'] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-table']) }}
>
    @if ($title)
        <h2 class="mb-6 text-3xl font-bold">{{ $title }}</h2>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            @if (filled($meta['caption'] ?? null))
                <caption class="mb-3 text-left">
                    {{ $meta['caption'] }}
                </caption>
            @endif

            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th class="border p-3 text-left">
                            {{ $header['label'] ?? '' }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach (explode('|', (string) ($row['cells'] ?? '')) as $cell)
                            <td class="border p-3">{{ trim($cell) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
