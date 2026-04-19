@extends('corporate::layouts.app', ['title' => $title ?? 'Home'])

@section('content')
    <x-corporate::hero-section
        :title="$hero['title'] ?? 'Built for serious businesses.'"
        :subtitle="$hero['subtitle'] ?? 'A trustworthy, accessible, modern site in under an hour.'"
        :cta-label="$hero['cta_label'] ?? 'Get started'"
        :cta-url="$hero['cta_url'] ?? '#contact'"
    />

    <x-corporate::features-grid
        :title="'Why Capell'"
        :features="$features ?? []"
    />

    <x-corporate::case-studies-carousel :studies="$studies ?? []" />

    <x-corporate::blog-listing :posts="$posts ?? []" />

    <x-corporate::contact-form />
@endsection

{{-- Render the section inside the layout slot --}}
{{ $__env->yieldContent('content') }}
