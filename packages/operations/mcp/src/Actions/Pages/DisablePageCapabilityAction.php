<?php

declare(strict_types=1);

namespace Capell\Mcp\Actions\Pages;

use Capell\Mcp\Contracts\CapellMcpCapabilityAction;
use Capell\Mcp\Data\CapabilityInvocationData;
use Capell\Mcp\Data\CapabilityResultData;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class DisablePageCapabilityAction implements CapellMcpCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $payload = $this->validatedPayload($invocation->payload);

        return new CapabilityResultData(
            ok: true,
            message: 'The page visibility window will be ended immediately.',
            data: [
                'page_id' => $payload['page_id'],
                'visible_until' => now()->toIso8601String(),
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
        $page->forceFill(['visible_until' => now()])->save();
        $visibleUntil = $page->getAttribute('visible_until');

        return new CapabilityResultData(
            ok: true,
            message: 'Page has been disabled.',
            data: [
                'page_id' => (int) $page->getKey(),
                'visible_until' => $visibleUntil instanceof CarbonInterface ? $visibleUntil->toIso8601String() : $visibleUntil,
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
