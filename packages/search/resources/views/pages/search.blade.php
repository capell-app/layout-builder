<section class="capell-search-page space-y-6">
    <h1>{{ __('capell-search::generic.page_title') }}</h1>

    <x-capell-search::form :query="$query" />

    <x-capell-search::results :query="$query" :results="$results" />
</section>
