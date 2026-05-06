@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    use Capell\CampaignStudio\Models\CampaignCtaBlock;

    $ctaBlock = CampaignCtaBlock::query()->find($widget->getMeta('cta_block_id'));
@endphp

<x-capell-layout-builder::widget.wrapper
    class="widget-campaign-cta-block"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if ($ctaBlock)
        <section class="campaign-cta-block px-6 py-12 text-center">
            @if ($ctaBlock->headline)
                <h2 class="text-3xl font-bold">{{ $ctaBlock->headline }}</h2>
            @endif

            @if ($ctaBlock->body)
                <p class="mx-auto mt-4 max-w-2xl">{{ $ctaBlock->body }}</p>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @foreach ($ctaBlock->actions ?? [] as $action)
                    <a
                        href="{{ BuildCampaignUrlAction::run($action->url, $action->utm ?? $ctaBlock->default_utm ?? new UtmData) }}"
                        class="layout-builder-btn {{ $action->style === 'secondary' ? 'layout-builder-btn-secondary' : 'layout-builder-btn-primary' }}"
                        data-campaign-id="{{ $ctaBlock->campaign_group_id }}"
                        data-campaign-goal="{{ $action->goalKey }}"
                        data-campaign-location="cta-block"
                    >
                        {{ $action->label }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-capell-layout-builder::widget.wrapper>
