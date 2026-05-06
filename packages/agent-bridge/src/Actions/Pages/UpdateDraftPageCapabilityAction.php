<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions\Pages;

use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityAction;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class UpdateDraftPageCapabilityAction implements CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);

        return new CapabilityResultData(
            ok: true,
            message: 'The selected page will be updated with safe editable fields.',
            data: [
                'page_id' => $payload['page_id'],
                'changes' => array_diff_key($payload, ['page_id' => true]),
            ],
        );
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);
        $pageClass = $this->pageClass();
        $page = $pageClass::query()
            ->whereKey($payload['page_id'])
            ->firstOrFail();

        $updates = array_intersect_key($payload, array_flip([
            'name',
            'meta',
            'admin',
            'visible_from',
            'visible_until',
        ]));

        $page->forceFill($updates)->save();

        return new CapabilityResultData(
            ok: true,
            message: 'Page has been updated.',
            data: [
                'page_id' => (int) $page->getKey(),
                'updated' => array_keys($updates),
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
            'name' => ['sometimes', 'string', 'max:255'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'admin' => ['sometimes', 'nullable', 'array'],
            'visible_from' => ['sometimes', 'nullable', 'date'],
            'visible_until' => ['sometimes', 'nullable', 'date'],
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
