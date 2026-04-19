@extends('saas::layouts.app', ['title' => $title ?? 'Home'])

@section('content')
    <x-saas::hero-with-screenshot
        :title="$hero['title'] ?? 'Ship faster. Scale without friction.'"
        :subtitle="$hero['subtitle'] ?? 'The all-in-one platform teams love — built for modern product development.'"
    />

    <x-saas::integrations-grid :integrations="$integrations ?? []" />

    <x-saas::feature-matrix :features="$features ?? []" />

    <x-saas::use-cases-tabs :use-cases="$useCases ?? []" />

    <x-saas::pricing-table :tiers="$tiers ?? []" />

    <x-saas::testimonials-wall :testimonials="$testimonials ?? []" />

    <x-saas::faq-accordion :faqs="$faqs ?? []" />

    <x-saas::cta-banner />
@endsection

{{ $__env->yieldContent('content') }}
