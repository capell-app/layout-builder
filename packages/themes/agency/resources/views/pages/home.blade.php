@extends('agency::layouts.app', ['title' => $title ?? 'Home'])

@section('content')
    <x-agency::hero-statement
        :statement="$hero['statement'] ?? 'Brands worth the attention they demand.'"
        :subtitle="$hero['subtitle'] ?? 'We design identities, products and campaigns for ambitious teams.'"
        :cta-label="$hero['cta_label'] ?? 'Start a project'"
        :cta-url="$hero['cta_url'] ?? '#inquiry'"
    />

    <x-agency::clients-marquee :clients="$clients ?? []" />

    <x-agency::portfolio-grid :projects="$projects ?? []" />

    <x-agency::services-showcase :services="$services ?? []" />

    <x-agency::process-flow :steps="$steps ?? []" />

    <x-agency::testimonials-quote :testimonials="$testimonials ?? []" />

    <x-agency::awards-badges :awards="$awards ?? []" />

    <x-agency::contact-inquiry />
@endsection

{{-- Render the section inside the layout slot --}}
{{ $__env->yieldContent('content') }}
