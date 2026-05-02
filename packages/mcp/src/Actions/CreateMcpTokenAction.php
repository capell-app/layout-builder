<?php

declare(strict_types=1);

namespace Capell\Mcp\Actions;

use Capell\Mcp\Models\CapellMcpToken;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{token: CapellMcpToken, plainTextToken: string} run(Authenticatable $user, string $name, array<int, string> $scopes, ?\DateTimeInterface $expiresAt = null)
 */
final class CreateMcpTokenAction
{
    use AsAction;

    /**
     * @param  array<int, string>  $scopes
     * @return array{token: CapellMcpToken, plainTextToken: string}
     */
    public function handle(Authenticatable $user, string $name, array $scopes, ?DateTimeInterface $expiresAt = null): array
    {
        if (! $user instanceof Model) {
            throw new InvalidArgumentException('Capell MCP tokens must be linked to an Eloquent user model.');
        }

        $plainTextToken = CapellMcpToken::generatePlainTextToken();

        $token = new CapellMcpToken([
            'name' => $name,
            'token_hash' => CapellMcpToken::hashPlainTextToken($plainTextToken),
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
