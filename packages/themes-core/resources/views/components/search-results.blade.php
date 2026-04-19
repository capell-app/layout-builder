@props([
    'results',
    'query' => '',
    'search' => null,
])

@php
    /** @var \Capell\Themes\Core\Search\SiteSearch|null $search */
    $search = $search ?? new \Capell\Themes\Core\Search\DatabaseSiteSearch(app('db')->connection());
@endphp

<section class="capell-search-results" aria-label="Search results">
    @if ($results->isEmpty())
        <p class="text-gray-600">
            No results for <strong>{{ $query }}</strong>. Try another keyword.
        </p>
    @else
        <p class="mb-4 text-sm text-gray-500">
            {{ $results->total() }} {{ \Illuminate\Support\Str::plural('result', $results->total()) }} for
            <strong>{{ $query }}</strong>
        </p>
        <ol class="space-y-4" role="list">
            @foreach ($results as $result)
                <li class="rounded-lg border border-gray-100 p-4">
                    <h3 class="text-lg font-semibold">
                        <a href="{{ $result->url }}" class="hover:underline">
                            {!! $search->highlight($result->title, $query) !!}
                        </a>
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {!! $search->highlight($result->excerpt, $query) !!}
                    </p>
                    <p class="mt-2 text-xs uppercase tracking-wide text-gray-400">
                        {{ $result->type }}
                    </p>
                </li>
            @endforeach
        </ol>
        <div class="mt-6">
            {{ $results->links() }}
        </div>
    @endif
</section>
