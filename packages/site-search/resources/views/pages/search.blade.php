<section class="capell-site-search-page space-y-6">
    <h1>{{ __('capell-site-search::generic.page_title') }}</h1>

    <x-capell-site-search::form :query="$query" />

    <x-capell-site-search::results :query="$query" :results="$results" />
</section>
