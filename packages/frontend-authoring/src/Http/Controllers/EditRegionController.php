<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Http\Controllers;

use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class EditRegionController extends BaseController
{
    public function __invoke(Request $request, string $payload): View
    {
        $this->authorizeAdmin($request);

        return view('capell::editor.region', [
            'payload' => $payload,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();

        abort_unless($user instanceof AuthenticatableContract, 403);
        abort_unless(resolve(AdminAccessCheckerInterface::class)->isAdmin($user), 403);
    }
}
