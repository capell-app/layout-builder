@php
    $url = $getState();
@endphp

@if ($url)
    <div class="mt-2">
        <img
            src="{{ $url }}"
            alt="AI Generated Image Preview"
            class="max-w-full rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
            style="max-height: 300px; object-fit: cover"
        />
    </div>
@endif
