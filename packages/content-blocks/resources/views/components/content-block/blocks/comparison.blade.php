@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $columns = is_array($meta['columns'] ?? null) ? $meta['columns'] : [];
    $rows = is_array($meta['rows'] ?? null) ? $meta['rows'] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-comparison']) }}
>
    @if ($title)
        <h2 class="mb-6 text-3xl font-bold">{{ $title }}</h2>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border p-3 text-left"></th>
                    @foreach ($columns as $column)
                        <th class="border p-3 text-left">
                            {{ $column['heading'] ?? '' }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <th class="border p-3 text-left">
                            {{ $row['label'] ?? '' }}
                        </th>
                        @foreach (explode('|', (string) ($row['values'] ?? '')) as $value)
                            <td class="border p-3">{{ trim($value) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
