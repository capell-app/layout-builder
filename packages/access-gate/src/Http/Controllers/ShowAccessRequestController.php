<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Controllers;

use Capell\AccessGate\Actions\ListAccessRequestMethodsAction;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ShowAccessRequestController
{
    public function __construct(
        private readonly RegistrationFieldRegistry $fields,
        private readonly ListAccessRequestMethodsAction $listAccessRequestMethods,
    ) {}

    public function __invoke(Request $request, string $area): Response
    {
        $accessArea = Area::query()->where('key', $area)->firstOrFail();

        return $this->noStore(response()->view($accessArea->gate_view ?? 'capell-access-gate::request', [
            'area' => $accessArea,
            'fields' => $this->fields->all(),
            'requestMethods' => $this->listAccessRequestMethods->handle($accessArea, $this->requestedUrl($request)),
            'emailRequestEnabled' => (bool) config('access-gate.registration.methods.email.enabled', true),
            'requestedUrl' => $this->requestedUrl($request),
        ]));
    }

    private function requestedUrl(Request $request): ?string
    {
        $requestedUrl = $request->query('redirect');

        return is_string($requestedUrl) && $requestedUrl !== '' ? $requestedUrl : null;
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
