<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Controllers;

use Capell\AccessGate\Actions\ListAccessRequestMethodsAction;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
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

        $requestedUrl = $this->requestedUrl($request, $accessArea);

        return $this->noStore(response()->view($accessArea->gate_view ?? 'capell-access-gate::request', [
            'area' => $accessArea,
            'fields' => $this->fields->all(),
            'requestMethods' => $this->listAccessRequestMethods->handle($accessArea, $requestedUrl),
            'emailRequestEnabled' => config('access-gate.registration.methods.email.enabled', true),
            'requestedUrl' => $requestedUrl,
            'submittedRegistration' => $this->submittedRegistration($request, $accessArea),
        ]));
    }

    private function submittedRegistration(Request $request, Area $area): ?Registration
    {
        $registrationId = $request->session()->get('access_gate_registration_id');

        if (! is_numeric($registrationId)) {
            return null;
        }

        return Registration::query()
            ->whereKey((int) $registrationId)
            ->where('access_area_id', $area->getKey())
            ->first();
    }

    private function requestedUrl(Request $request, Area $area): ?string
    {
        $requestedUrl = $request->query('redirect');

        if (! is_string($requestedUrl) || $requestedUrl === '') {
            return null;
        }

        $host = parse_url($requestedUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        if ($host === $request->getHost()) {
            return $requestedUrl;
        }

        $allowedHosts = collect($area->claim_url_hosts ?? [])
            ->filter(fn (mixed $allowedHost): bool => is_string($allowedHost) && $allowedHost !== '')
            ->all();

        return in_array($host, $allowedHosts, true) ? $requestedUrl : null;
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
