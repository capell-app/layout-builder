<?php

declare(strict_types=1);

use Capell\LoginAudit\Http\Middleware\AdminActivityMiddleware;
use Capell\LoginAudit\Http\Middleware\UserActivityMiddleware;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function loginAuditActivityRequest(string $path, User $user, string $ipAddress, string $userAgent): Request
{
    $request = Request::create(
        uri: $path,
        method: 'GET',
        server: [
            'REMOTE_ADDR' => $ipAddress,
            'HTTP_USER_AGENT' => $userAgent,
        ],
    );

    $request->setUserResolver(fn (): User => $user);

    return $request;
}

function loginAuditActivityTimestamp(?DateTimeInterface $timestamp): ?string
{
    return $timestamp?->toDateTimeString();
}

it('updates matching admin activity for the authenticated actor ip path and user agent', function (): void {
    $trackedAt = now()->setMicrosecond(0);
    $this->travelTo($trackedAt);

    $adminUser = User::factory()->create();
    $otherUser = User::factory()->create();

    $ipAddress = '198.51.100.23';
    $userAgent = 'Capell Admin Browser/1.0';
    $matchingAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $adminUser->getMorphClass(),
        'authenticatable_id' => $adminUser->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'login_at' => $trackedAt->copy()->subHour(),
    ]);

    $wrongIpAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $adminUser->getMorphClass(),
        'authenticatable_id' => $adminUser->getKey(),
        'ip_address' => '203.0.113.88',
        'user_agent' => $userAgent,
        'login_at' => $trackedAt->copy()->subHour(),
    ]);

    $wrongActorAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $otherUser->getMorphClass(),
        'authenticatable_id' => $otherUser->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'login_at' => $trackedAt->copy()->subHour(),
    ]);

    $futureLoginAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $adminUser->getMorphClass(),
        'authenticatable_id' => $adminUser->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'login_at' => $trackedAt->copy()->addMinute(),
    ]);

    $wrongIpAuditLastSeenAt = loginAuditActivityTimestamp($wrongIpAudit->refresh()->last_seen_at);
    $wrongActorAuditLastSeenAt = loginAuditActivityTimestamp($wrongActorAudit->refresh()->last_seen_at);
    $futureLoginAuditLastSeenAt = loginAuditActivityTimestamp($futureLoginAudit->refresh()->last_seen_at);

    $this->actingAs($adminUser);

    $request = loginAuditActivityRequest(
        path: '/admin/login-audits?from=dashboard',
        user: $adminUser,
        ipAddress: $ipAddress,
        userAgent: $userAgent,
    );

    $response = (new AdminActivityMiddleware)->handle(
        $request,
        fn (Request $handledRequest): Response => new Response('next:' . $handledRequest->path()),
    );

    expect($response->getContent())->toBe('next:admin/login-audits')
        ->and(loginAuditActivityTimestamp($matchingAudit->refresh()->last_seen_at))->toBe($trackedAt->toDateTimeString())
        ->and(loginAuditActivityTimestamp($wrongIpAudit->refresh()->last_seen_at))->toBe($wrongIpAuditLastSeenAt)
        ->and(loginAuditActivityTimestamp($wrongActorAudit->refresh()->last_seen_at))->toBe($wrongActorAuditLastSeenAt)
        ->and(loginAuditActivityTimestamp($futureLoginAudit->refresh()->last_seen_at))->toBe($futureLoginAuditLastSeenAt);
});

it('skips admin activity for unauthenticated requests', function (): void {
    $trackedAt = now()->setMicrosecond(0);
    $this->travelTo($trackedAt);

    $adminUser = User::factory()->create();
    $audit = LoginAudit::factory()->create([
        'authenticatable_type' => $adminUser->getMorphClass(),
        'authenticatable_id' => $adminUser->getKey(),
        'ip_address' => '198.51.100.23',
        'user_agent' => 'Capell Admin Browser/1.0',
        'login_at' => $trackedAt->copy()->subHour(),
    ]);
    $lastSeenAt = loginAuditActivityTimestamp($audit->refresh()->last_seen_at);

    $request = Request::create(
        uri: '/admin/login-audits',
        method: 'GET',
        server: [
            'REMOTE_ADDR' => '198.51.100.23',
            'HTTP_USER_AGENT' => 'Capell Admin Browser/1.0',
        ],
    );

    $response = (new AdminActivityMiddleware)->handle(
        $request,
        fn (Request $handledRequest): Response => new Response('next:' . $handledRequest->path()),
    );

    expect($response->getContent())->toBe('next:admin/login-audits')
        ->and(loginAuditActivityTimestamp($audit->refresh()->last_seen_at))->toBe($lastSeenAt);
});

it('updates user middleware activity without overwriting unrelated audit state', function (): void {
    $trackedAt = now()->setMicrosecond(0);
    $this->travelTo($trackedAt);

    $user = User::factory()->create();
    $ipAddress = '203.0.113.45';
    $userAgent = 'Capell Frontend Browser/1.0';
    $loginAt = $trackedAt->copy()->subHours(3);
    $logoutAt = $trackedAt->copy()->subHour();
    $location = ['country' => 'United Kingdom', 'city' => 'London'];

    $matchingAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'login_at' => $loginAt,
        'logout_at' => $logoutAt,
        'login_successful' => true,
        'cleared_by_user' => false,
        'location' => $location,
    ]);

    $wrongAgentAudit = LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => 'Another Browser/1.0',
        'login_at' => $loginAt,
    ]);
    $wrongAgentAuditLastSeenAt = loginAuditActivityTimestamp($wrongAgentAudit->refresh()->last_seen_at);

    $request = loginAuditActivityRequest(
        path: '/account/profile',
        user: $user,
        ipAddress: $ipAddress,
        userAgent: $userAgent,
    );

    $response = (new UserActivityMiddleware)->handle(
        $request,
        fn (Request $handledRequest): Response => new Response('next:' . $handledRequest->path()),
    );

    $matchingAudit->refresh();

    expect($response->getContent())->toBe('next:account/profile')
        ->and(loginAuditActivityTimestamp($matchingAudit->last_seen_at))->toBe($trackedAt->toDateTimeString())
        ->and(loginAuditActivityTimestamp($matchingAudit->login_at))->toBe($loginAt->toDateTimeString())
        ->and(loginAuditActivityTimestamp($matchingAudit->logout_at))->toBe($logoutAt->toDateTimeString())
        ->and($matchingAudit->login_successful)->toBeTrue()
        ->and($matchingAudit->cleared_by_user)->toBeFalse()
        ->and($matchingAudit->location)->toBe($location)
        ->and(loginAuditActivityTimestamp($wrongAgentAudit->refresh()->last_seen_at))->toBe($wrongAgentAuditLastSeenAt);
});

it('skips user activity for guest requests', function (): void {
    $trackedAt = now()->setMicrosecond(0);
    $this->travelTo($trackedAt);

    $user = User::factory()->create();
    $audit = LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'ip_address' => '203.0.113.45',
        'user_agent' => 'Capell Frontend Browser/1.0',
        'login_at' => $trackedAt->copy()->subHour(),
    ]);
    $lastSeenAt = loginAuditActivityTimestamp($audit->refresh()->last_seen_at);

    $request = Request::create(
        uri: '/account/profile',
        method: 'GET',
        server: [
            'REMOTE_ADDR' => '203.0.113.45',
            'HTTP_USER_AGENT' => 'Capell Frontend Browser/1.0',
        ],
    );

    $response = (new UserActivityMiddleware)->handle(
        $request,
        fn (Request $handledRequest): Response => new Response('next:' . $handledRequest->path()),
    );

    expect($response->getContent())->toBe('next:account/profile')
        ->and(loginAuditActivityTimestamp($audit->refresh()->last_seen_at))->toBe($lastSeenAt);
});
