<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions;

use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{token: CapellAgentBridgeToken, plainTextToken: string} run(Authenticatable $user, string $name, array<int, string> $scopes, ?DateTimeInterface $expiresAt = null)
 */
final class CreateAgentBridgeTokenAction
{
    use AsAction;

    /**
     * @param  array<int, string>  $scopes
     * @return array{token: CapellAgentBridgeToken, plainTextToken: string}
     */
    public function handle(Authenticatable $user, string $name, array $scopes, ?DateTimeInterface $expiresAt = null): array
    {
        throw_unless($user instanceof Model, InvalidArgumentException::class, 'Capell Agent Bridge tokens must be linked to an Eloquent user model.');

        $plainTextToken = CapellAgentBridgeToken::generatePlainTextToken();

        $token = new CapellAgentBridgeToken([
            'name' => $name,
            'token_hash' => CapellAgentBridgeToken::hashPlainTextToken($plainTextToken),
            'scopes' => array_values($scopes),
            'expires_at' => $expiresAt,
        ]);

        $token->user()->associate($user);
        $token->save();

        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }
}
