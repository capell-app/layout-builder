<?php

declare(strict_types=1);

namespace Capell\Mcp\Actions\Pages;

use Capell\Mcp\Contracts\CapellMcpCapabilityAction;
use Capell\Mcp\Data\CapabilityInvocationData;
use Capell\Mcp\Data\CapabilityResultData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class InspectPagePublishingReadinessCapabilityAction implements CapellMcpCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        return $this->execute($invocation);
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);
        $pageClass = $this->pageClass();
        $page = $pageClass::query()
            ->with(['site', 'type', 'layout', 'pageUrls'])
            ->whereKey($payload['page_id'])
            ->firstOrFail();
        $pageUrls = $page->getAttribute('pageUrls');

        $checks = [
            'has_site' => $page->getAttribute('site') !== null,
            'has_type' => $page->getAttribute('type') !== null,
            'has_layout' => $page->getAttribute('layout') !== null,
            'has_page_urls' => $pageUrls instanceof Collection && $pageUrls->isNotEmpty(),
            'is_visible_now' => method_exists($page, 'isVisible') ? (bool) $page->isVisible() : null,
        ];

        return new CapabilityResultData(
            ok: ! in_array(false, $checks, true),
            message: 'Page publishing readiness has been inspected.',
            data: [
                'page_id' => (int) $page->getKey(),
                'name' => $page->getAttribute('name'),
                'checks' => $checks,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validatedPayload(array $payload): array
    {
        validator($payload, [
            'page_id' => ['required', 'integer'],
        ])->validate();

        return $payload;
    }

    /** @return class-string<Model> */
    private function pageClass(): string
    {
        $pageClass = 'Capell\\Core\\Models\\Page';

        if (! is_subclass_of($pageClass, Model::class)) {
            throw ValidationException::withMessages(['page' => 'Capell Page model is not available.']);
        }

        return $pageClass;
    }
}
