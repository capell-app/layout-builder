<?php

declare(strict_types=1);

use Capell\ContentSections\Actions\BuildSectionDemoDataAction;
use Capell\ContentSections\Actions\RegisterDefaultSectionsAction;
use Capell\ContentSections\Support\SectionRegistry;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Sinnbeck\DomAssertions\Asserts\AssertElement;

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    config(['capell-core.disable_cache' => true]);

    $registry = new SectionRegistry;

    view()->addNamespace('capell-content-sections', __DIR__ . '/../../resources/views');
    resolve(Translator::class)->addNamespace('capell-content-sections', __DIR__ . '/../../resources/lang');
    Blade::anonymousComponentPath(__DIR__ . '/../Fixtures/components', 'capell');

    RegisterDefaultSectionsAction::run($registry);
    app()->instance(SectionRegistry::class, $registry);
});

function renderSectionForDomAssertions(string $key): TestResponse
{
    $data = BuildSectionDemoDataAction::run($key);
    $data['meta'] = removeSectionIconValues($data['meta']);
    $html = Blade::render(
        '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
        $data,
    );

    return TestResponse::fromBaseResponse(
        new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'),
    );
}

function assertSectionDomElementCount(TestResponse $response, string $selector, int $count): TestResponse
{
    return $response->assertElementExists('body', static function (AssertElement $body) use ($selector, $count): void {
        $body->contains($selector, $count);
    });
}

/**
 * @param  array<string, mixed>  $meta
 * @return array<string, mixed>
 */
function removeSectionIconValues(array $meta): array
{
    foreach ($meta as $key => $value) {
        if ($key === 'icon') {
            unset($meta[$key]);

            continue;
        }

        if (is_array($value)) {
            $meta[$key] = removeSectionIconValues($value);
        }
    }

    return $meta;
}

it('renders accordion panels as disclosure elements', function (): void {
    renderSectionForDomAssertions('accordion')
        ->assertContainsElement('section.section-accordion', ['text' => 'Accordion'])
        ->assertContainsElement('section.section-accordion details[open]')
        ->assertContainsElement('section.section-accordion summary', ['text' => 'How quickly can editors update content?'])
        ->assertContainsElement('section.section-accordion .prose p', ['text' => 'Editors can update reusable panels once and reuse them across pages.']);

    assertSectionDomElementCount(renderSectionForDomAssertions('accordion'), 'section.section-accordion details', 2);
});

it('renders call to action headings copy and actions', function (): void {
    renderSectionForDomAssertions('call_to_action')
        ->assertContainsElement('section.section-call-to-action.text-center', ['text' => 'Call to Action'])
        ->assertContainsElement('section.section-call-to-action a', ['href' => '#', 'text' => 'Start a project'])
        ->assertContainsElement('section.section-call-to-action a', ['href' => '#', 'text' => 'View examples'], 1);
});

it('renders public action buttons through the public actions component when available', function (): void {
    Route::post('/actions/{action}', static fn (): string => 'submitted')->name('capell-public-actions.submit');
    Route::getRoutes()->refreshNameLookups();
    view()->addNamespace('capell-public-actions', __DIR__ . '/../../../public-actions/resources/views');

    $data = BuildSectionDemoDataAction::run('call_to_action');
    $data['meta']['actions'] = [
        [
            'type' => 'public_action',
            'public_action_key' => 'request-preview',
            'label' => 'Request preview',
            'access_gate_area' => 'preview',
            'redirect' => '/thanks',
        ],
    ];

    $html = Blade::render(
        '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
        $data,
    );

    TestResponse::fromBaseResponse(new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'))
        ->assertContainsElement('section.section-call-to-action form', ['action' => 'http://localhost/actions/request-preview'])
        ->assertContainsElement('section.section-call-to-action input', ['name' => 'area', 'value' => 'preview'])
        ->assertContainsElement('section.section-call-to-action button', ['text' => 'Request preview']);
});

it('renders comparison columns rows and highlighted column state', function (): void {
    renderSectionForDomAssertions('comparison')
        ->assertContainsElement('section.section-comparison table')
        ->assertContainsElement('section.section-comparison thead th', ['text' => 'Growth'])
        ->assertContainsElement('section.section-comparison thead th', ['class' => 'bg-slate-950 text-white', 'text' => 'Growth'])
        ->assertContainsElement('section.section-comparison tbody th', ['text' => 'Reusable sections'])
        ->assertContainsElement('section.section-comparison td', ['text' => 'Unlimited']);
});

it('renders section copy as prose', function (): void {
    renderSectionForDomAssertions('content')
        ->assertContainsElement('section.section-content h2', ['text' => 'Content'])
        ->assertContainsElement('section.section-content .prose', ['text' => 'Reusable rich text content.']);
});

it('renders counter cards with formatted values and labels', function (): void {
    renderSectionForDomAssertions('counter')
        ->assertContainsElement('section.section-counter p.text-4xl', ['text' => '42%'])
        ->assertContainsElement('section.section-counter p.text-4xl', ['text' => '+18'])
        ->assertContainsElement('section.section-counter h3', ['text' => 'Faster publishing'])
        ->assertContainsElement('section.section-counter p', ['text' => 'Average reduction in edit-to-live time.']);

    assertSectionDomElementCount(renderSectionForDomAssertions('counter'), 'section.section-counter article', 3);
});

it('renders configured section icons through blade icons', function (): void {
    $data = BuildSectionDemoDataAction::run('counter');
    $html = Blade::render(
        '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
        $data,
    );

    expect($html)
        ->toContain('<svg')
        ->toContain('mx-auto mb-4 h-8 w-8 text-slate-500');
});

it('renders divider dots when configured', function (): void {
    renderSectionForDomAssertions('divider')
        ->assertContainsElement('div.section-divider', ['text' => '...'])
        ->assertDoesntExist('div.section-divider hr');
});

it('renders FAQ questions as disclosure elements', function (): void {
    renderSectionForDomAssertions('faq')
        ->assertContainsElement('section.section-faq details[open]')
        ->assertContainsElement('section.section-faq summary', ['text' => 'Can FAQ content be reused?'])
        ->assertContainsElement('section.section-faq .prose p', ['text' => 'Yes. The block stores reusable question and answer pairs.']);

    assertSectionDomElementCount(renderSectionForDomAssertions('faq'), 'section.section-faq details', 2);
});

it('renders feature cards with links', function (): void {
    renderSectionForDomAssertions('features')
        ->assertContainsElement('section.section-features .grid', ['class' => 'md:grid-cols-3'])
        ->assertContainsElement('section.section-features h3', ['text' => 'Reusable patterns'])
        ->assertContainsElement('section.section-features a', ['href' => '#', 'text' => 'Read more']);

    assertSectionDomElementCount(renderSectionForDomAssertions('features'), 'section.section-features article', 3);
});

it('renders hero as a centered feature section', function (): void {
    renderSectionForDomAssertions('hero')
        ->assertContainsElement('section.section-hero.text-center')
        ->assertContainsElement('section.section-hero h1', ['text' => 'Hero'])
        ->assertContainsElement('section.section-hero div', ['text' => 'Introductory content for a page or section.']);
});

it('keeps demo frame styling separate from section component styling', function (): void {
    $html = view('capell-content-sections::section.demo', BuildSectionDemoDataAction::run('hero'))->render();
    $normalizedHtml = preg_replace('/\s+/', ' ', $html) ?? $html;

    expect($normalizedHtml)
        ->toMatch('/<div class="rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200"\\s*>/')
        ->toContain('section section-hero rounded-lg bg-slate-950 p-10 text-white')
        ->not->toContain('section-hero rounded-lg bg-slate-950 p-10 text-white rounded-lg bg-white');
});

it('renders logo links in the configured grid', function (): void {
    renderSectionForDomAssertions('logos')
        ->assertContainsElement('section.section-logos .grid', ['class' => 'grid-cols-2 md:grid-cols-4'])
        ->assertContainsElement('section.section-logos a', ['href' => '#', 'text' => 'Northstar'])
        ->assertContainsElement('section.section-logos a', ['href' => '#', 'text' => 'Signal Works']);

    assertSectionDomElementCount(renderSectionForDomAssertions('logos'), 'section.section-logos a', 4);
});

it('renders pricing plans with features actions and highlighted plan state', function (): void {
    renderSectionForDomAssertions('pricing')
        ->assertContainsElement('section.section-pricing article', ['class' => 'border-slate-950 shadow-lg', 'text' => 'Scale'])
        ->assertContainsElement('section.section-pricing p', ['text' => '$149 /mo'])
        ->assertContainsElement('section.section-pricing li', ['text' => 'Priority support'])
        ->assertContainsElement('section.section-pricing a', ['href' => '#', 'text' => 'Choose Scale']);

    assertSectionDomElementCount(renderSectionForDomAssertions('pricing'), 'section.section-pricing article', 3);
});

it('renders stats as a metric grid', function (): void {
    renderSectionForDomAssertions('stats')
        ->assertContainsElement('section.section-stats .grid', ['class' => 'md:grid-cols-4'])
        ->assertContainsElement('section.section-stats p.text-3xl', ['text' => '18'])
        ->assertContainsElement('section.section-stats h3', ['text' => 'Block types']);

    assertSectionDomElementCount(renderSectionForDomAssertions('stats'), 'section.section-stats article', 4);
});

it('renders structured table captions headers and cells', function (): void {
    renderSectionForDomAssertions('table')
        ->assertContainsElement('section.section-table table')
        ->assertContainsElement('section.section-table caption', ['text' => 'Editorial workflow comparison'])
        ->assertContainsElement('section.section-table th', ['text' => 'Workflow'])
        ->assertContainsElement('section.section-table td', ['text' => 'Content lead'])
        ->assertContainsElement('section.section-table td', ['text' => 'Same day']);
});

it('renders tabs with a tablist and linked panels', function (): void {
    renderSectionForDomAssertions('tabs')
        ->assertContainsElement('section.section-tabs [role="tablist"]')
        ->assertContainsElement('section.section-tabs [role="tablist"] a', ['href' => '#section-tabs-', 'text' => 'Plan'])
        ->assertContainsElement('section.section-tabs article[id^="section-tabs-"]', ['text' => 'Build'])
        ->assertContainsElement('section.section-tabs article .prose p', ['text' => 'Preview, approve, and ship the page with confidence.']);
});

it('renders team member profiles with initials roles bios and links', function (): void {
    renderSectionForDomAssertions('team')
        ->assertContainsElement('section.section-team .grid', ['class' => 'md:grid-cols-3'])
        ->assertContainsElement('section.section-team article div', ['text' => 'A'])
        ->assertContainsElement('section.section-team h3', ['text' => 'Priya Shah'])
        ->assertContainsElement('section.section-team p', ['text' => 'UX Designer'])
        ->assertContainsElement('section.section-team a', ['href' => '#', 'text' => 'Read more']);

    assertSectionDomElementCount(renderSectionForDomAssertions('team'), 'section.section-team article', 3);
});

it('renders testimonial quote attribution and role', function (): void {
    renderSectionForDomAssertions('testimonial')
        ->assertContainsElement('figure.section-testimonial blockquote', ['text' => 'Capell gives our editors the right amount of structure without slowing them down.'])
        ->assertContainsElement('figure.section-testimonial figcaption p', ['text' => 'Morgan Ellis'])
        ->assertContainsElement('figure.section-testimonial figcaption p', ['text' => 'Digital Director']);
});

it('renders timeline milestones in order', function (): void {
    renderSectionForDomAssertions('timeline')
        ->assertContainsElement('section.section-timeline ol')
        ->assertContainsElement('section.section-timeline li p', ['text' => 'Week 1'])
        ->assertContainsElement('section.section-timeline li h3', ['text' => 'Configure'])
        ->assertContainsElement('section.section-timeline li p', ['text' => 'Capture screenshots and verify frontend output.']);

    assertSectionDomElementCount(renderSectionForDomAssertions('timeline'), 'section.section-timeline li', 3);
});
