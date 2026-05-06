<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions\Pages;

use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityAction;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class CreateDraftPageCapabilityAction implements CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);

        return new CapabilityResultData(
            ok: true,
            message: 'A new unpublished page record will be created.',
            data: [
                'page' => $payload,
            ],
        );
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);
        $pageClass = $this->pageClass();

        $page = $pageClass::query()->create([
            'name' => $payload['name'],
            'site_id' => $payload['site_id'],
            'type_id' => $payload['type_id'],
            'layout_id' => $payload['layout_id'],
            'parent_id' => $payload['parent_id'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'admin' => $payload['admin'] ?? null,
            'visible_from' => $payload['visible_from'] ?? null,
            'visible_until' => $payload['visible_until'] ?? null,
        ]);

        return new CapabilityResultData(
            ok: true,
            message: 'Draft page has been created.',
            data: [
                'page_id' => (int) $page->getKey(),
                'name' => $page->getAttribute('name'),
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
            'name' => ['required', 'string', 'max:255'],
            'site_id' => ['required', 'integer'],
            'type_id' => ['required', 'integer'],
            'layout_id' => ['required', 'integer'],
            'parent_id' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
            'admin' => ['nullable', 'array'],
            'visible_from' => ['nullable', 'date'],
            'visible_until' => ['nullable', 'date'],
        ])->validate();

        return $payload;
    }

    /** @return class-string<Model> */
    private function pageClass(): string
    {
        $pageClass = Page::class;

        if (! is_subclass_of($pageClass, Model::class)) {
            throw ValidationException::withMessages(['page' => 'Capell Page model is not available.']);
        }

        return $pageClass;
    }
}
