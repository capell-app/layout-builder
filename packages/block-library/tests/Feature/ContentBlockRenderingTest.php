<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildContentBlockDemoDataAction;
use Capell\BlockLibrary\Actions\RegisterDefaultBlockLibraryAction;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Testing\TestResponse;
use Sinnbeck\DomAssertions\Asserts\AssertElement;

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    config(['capell-core.disable_cache' => true]);

    $registry = new ContentBlockRegistry;

    view()->addNamespace('capell-block-library', __DIR__ . '/../../resources/views');
    app('translator')->addNamespace('capell-block-library', __DIR__ . '/../../resources/lang');
    Blade::anonymousComponentPath(__DIR__ . '/../Fixtures/components', 'capell');

    RegisterDefaultBlockLibraryAction::run($registry);
    app()->instance(ContentBlockRegistry::class, $registry);
});

function renderContentBlockForDomAssertions(string $key): TestResponse
{
    $data = BuildContentBlockDemoDataAction::run($key);
    $data['meta'] = removeContentBlockIconValues($data['meta']);
    $html = Blade::render(
        '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
        $data,
    );

    return TestResponse::fromBaseResponse(
        new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'),
    );
}

function assertContentBlockDomElementCount(TestResponse $response, string $selector, int $count): TestResponse
{
    return $response->assertElementExists('body', static function (AssertElement $body) use ($selector, $count): void {
        $body->contains($selector, $count);
    });
}

/**
 * @param  array<string, mixed>  $meta
 * @return array<string, mixed>
 */
function removeContentBlockIconValues(array $meta): array
{
    foreach ($meta as $key => $value) {
        if ($key === 'icon') {
            unset($meta[$key]);

            continue;
        }

        if (is_array($value)) {
            $meta[$key] = removeContentBlockIconValues($value);
        }
    }

    return $meta;
}

it('renders accordion panels as disclosure elements', function (): void {
    renderContentBlockForDomAssertions('accordion')
        ->assertContainsElement('section.content-block-accordion', ['text' => 'Accordion'])
        ->assertContainsElement('section.content-block-accordion details[open]')
        ->assertContainsElement('section.content-block-accordion summary', ['text' => 'How quickly can editors update content?'])
        ->assertContainsElement('section.content-block-accordion .prose p', ['text' => 'Editors can update reusable panels once and reuse them across pages.']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('accordion'), 'section.content-block-accordion details', 2);
});

it('renders call to action headings copy and actions', function (): void {
    renderContentBlockForDomAssertions('call_to_action')
        ->assertContainsElement('section.content-block-call-to-action.text-center', ['text' => 'Call to Action'])
        ->assertContainsElement('section.content-block-call-to-action a', ['href' => '#', 'text' => 'Start a project'])
        ->assertContainsElement('section.content-block-call-to-action a', ['href' => '#', 'text' => 'View examples'], 1);
});

it('renders comparison columns rows and highlighted column state', function (): void {
    renderContentBlockForDomAssertions('comparison')
        ->assertContainsElement('section.content-block-comparison table')
        ->assertContainsElement('section.content-block-comparison thead th', ['text' => 'Growth'])
        ->assertContainsElement('section.content-block-comparison thead th', ['class' => 'bg-slate-950 text-white', 'text' => 'Growth'])
        ->assertContainsElement('section.content-block-comparison tbody th', ['text' => 'Reusable blocks'])
        ->assertContainsElement('section.content-block-comparison td', ['text' => 'Unlimited']);
});

it('renders content block copy as prose', function (): void {
    renderContentBlockForDomAssertions('content')
        ->assertContainsElement('section.content-block-content h2', ['text' => 'Content'])
        ->assertContainsElement('section.content-block-content .prose', ['text' => 'Reusable rich text content.']);
});

it('renders counter cards with formatted values and labels', function (): void {
    renderContentBlockForDomAssertions('counter')
        ->assertContainsElement('section.content-block-counter p.text-4xl', ['text' => '42%'])
        ->assertContainsElement('section.content-block-counter p.text-4xl', ['text' => '+18'])
        ->assertContainsElement('section.content-block-counter h3', ['text' => 'Faster publishing'])
        ->assertContainsElement('section.content-block-counter p', ['text' => 'Average reduction in edit-to-live time.']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('counter'), 'section.content-block-counter article', 3);
});

it('renders configured block icons through blade icons', function (): void {
    $data = BuildContentBlockDemoDataAction::run('counter');
    $html = Blade::render(
        '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
        $data,
    );

    expect($html)
        ->toContain('<svg')
        ->toContain('mx-auto mb-4 h-8 w-8 text-slate-500');
});

it('renders divider dots when configured', function (): void {
    renderContentBlockForDomAssertions('divider')
        ->assertContainsElement('div.content-block-divider', ['text' => '...'])
        ->assertDoesntExist('div.content-block-divider hr');
});

it('renders FAQ questions as disclosure elements', function (): void {
    renderContentBlockForDomAssertions('faq')
        ->assertContainsElement('section.content-block-faq details[open]')
        ->assertContainsElement('section.content-block-faq summary', ['text' => 'Can FAQ content be reused?'])
        ->assertContainsElement('section.content-block-faq .prose p', ['text' => 'Yes. The block stores reusable question and answer pairs.']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('faq'), 'section.content-block-faq details', 2);
});

it('renders feature cards with links', function (): void {
    renderContentBlockForDomAssertions('features')
        ->assertContainsElement('section.content-block-features .grid', ['class' => 'md:grid-cols-3'])
        ->assertContainsElement('section.content-block-features h3', ['text' => 'Reusable patterns'])
        ->assertContainsElement('section.content-block-features a', ['href' => '#', 'text' => 'Read more']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('features'), 'section.content-block-features article', 3);
});

it('renders hero as a centered feature section', function (): void {
    renderContentBlockForDomAssertions('hero')
        ->assertContainsElement('section.content-block-hero.text-center')
        ->assertContainsElement('section.content-block-hero h1', ['text' => 'Hero'])
        ->assertContainsElement('section.content-block-hero div', ['text' => 'Introductory content for a page or section.']);
});

it('keeps demo frame styling separate from block component styling', function (): void {
    $html = view('capell-block-library::content-block.demo', BuildContentBlockDemoDataAction::run('hero'))->render();

    expect($html)
        ->toContain('<div class="rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">')
        ->toContain('content-block content-block-hero rounded-lg bg-slate-950 p-10 text-white')
        ->not->toContain('content-block-hero rounded-lg bg-slate-950 p-10 text-white rounded-lg bg-white');
});

it('renders logo links in the configured grid', function (): void {
    renderContentBlockForDomAssertions('logos')
        ->assertContainsElement('section.content-block-logos .grid', ['class' => 'grid-cols-2 md:grid-cols-4'])
        ->assertContainsElement('section.content-block-logos a', ['href' => '#', 'text' => 'Northstar'])
        ->assertContainsElement('section.content-block-logos a', ['href' => '#', 'text' => 'Signal Works']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('logos'), 'section.content-block-logos a', 4);
});

it('renders pricing plans with features actions and highlighted plan state', function (): void {
    renderContentBlockForDomAssertions('pricing')
        ->assertContainsElement('section.content-block-pricing article', ['class' => 'border-slate-950 shadow-lg', 'text' => 'Scale'])
        ->assertContainsElement('section.content-block-pricing p', ['text' => '$149 /mo'])
        ->assertContainsElement('section.content-block-pricing li', ['text' => 'Priority support'])
        ->assertContainsElement('section.content-block-pricing a', ['href' => '#', 'text' => 'Choose Scale']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('pricing'), 'section.content-block-pricing article', 3);
});

it('renders stats as a metric grid', function (): void {
    renderContentBlockForDomAssertions('stats')
        ->assertContainsElement('section.content-block-stats .grid', ['class' => 'md:grid-cols-4'])
        ->assertContainsElement('section.content-block-stats p.text-3xl', ['text' => '18'])
        ->assertContainsElement('section.content-block-stats h3', ['text' => 'Block types']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('stats'), 'section.content-block-stats article', 4);
});

it('renders structured table captions headers and cells', function (): void {
    renderContentBlockForDomAssertions('table')
        ->assertContainsElement('section.content-block-table table')
        ->assertContainsElement('section.content-block-table caption', ['text' => 'Editorial workflow comparison'])
        ->assertContainsElement('section.content-block-table th', ['text' => 'Workflow'])
        ->assertContainsElement('section.content-block-table td', ['text' => 'Content lead'])
        ->assertContainsElement('section.content-block-table td', ['text' => 'Same day']);
});

it('renders tabs with a tablist and linked panels', function (): void {
    renderContentBlockForDomAssertions('tabs')
        ->assertContainsElement('section.content-block-tabs [role="tablist"]')
        ->assertContainsElement('section.content-block-tabs [role="tablist"] a', ['href' => '#content-block-tabs-', 'text' => 'Plan'])
        ->assertContainsElement('section.content-block-tabs article[id^="content-block-tabs-"]', ['text' => 'Build'])
        ->assertContainsElement('section.content-block-tabs article .prose p', ['text' => 'Preview, approve, and ship the page with confidence.']);
});

it('renders team member profiles with initials roles bios and links', function (): void {
    renderContentBlockForDomAssertions('team')
        ->assertContainsElement('section.content-block-team .grid', ['class' => 'md:grid-cols-3'])
        ->assertContainsElement('section.content-block-team article div', ['text' => 'A'])
        ->assertContainsElement('section.content-block-team h3', ['text' => 'Priya Shah'])
        ->assertContainsElement('section.content-block-team p', ['text' => 'UX Designer'])
        ->assertContainsElement('section.content-block-team a', ['href' => '#', 'text' => 'Read more']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('team'), 'section.content-block-team article', 3);
});

it('renders testimonial quote attribution and role', function (): void {
    renderContentBlockForDomAssertions('testimonial')
        ->assertContainsElement('figure.content-block-testimonial blockquote', ['text' => 'Capell gives our editors the right amount of structure without slowing them down.'])
        ->assertContainsElement('figure.content-block-testimonial figcaption p', ['text' => 'Morgan Ellis'])
        ->assertContainsElement('figure.content-block-testimonial figcaption p', ['text' => 'Digital Director']);
});

it('renders timeline milestones in order', function (): void {
    renderContentBlockForDomAssertions('timeline')
        ->assertContainsElement('section.content-block-timeline ol')
        ->assertContainsElement('section.content-block-timeline li p', ['text' => 'Week 1'])
        ->assertContainsElement('section.content-block-timeline li h3', ['text' => 'Configure'])
        ->assertContainsElement('section.content-block-timeline li p', ['text' => 'Capture screenshots and verify frontend output.']);

    assertContentBlockDomElementCount(renderContentBlockForDomAssertions('timeline'), 'section.content-block-timeline li', 3);
});
