<?php

declare(strict_types=1);

namespace Capell\Plugins\Capabilities;

use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use InvalidArgumentException;

final class CapabilityRegistry
{
    public static function describe(Capability $capability, ?string $parameter = null): CapabilityDescriptor
    {
        if ($capability->acceptsParameter() && $parameter === null) {
            throw new InvalidArgumentException(
                sprintf('Capability %s requires a parameter', $capability->value),
            );
        }

        return match ($capability) {
            Capability::WritesFiles => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: self::resolveWritesFilesLevel($parameter),
                title: 'Writes files',
                summary: 'Plugin writes files outside its package directory (parameter scopes the target).',
                parameter: $parameter,
            ),
            Capability::DbSchemaChanges => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Yellow,
                title: 'Database schema changes',
                summary: 'Plugin runs migrations that add or modify tables.',
            ),
            Capability::HttpOutbound => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: self::resolveHttpOutboundLevel($parameter),
                title: 'Outbound HTTP',
                summary: 'Plugin makes HTTP calls to external hosts (parameter names the host or wildcard).',
                parameter: $parameter,
            ),
            Capability::ReadsSecrets => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Red,
                title: 'Reads secrets',
                summary: 'Plugin reads environment variables or encrypted config. Requires explicit admin confirmation.',
            ),
            Capability::AdminPages => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Green,
                title: 'Admin pages',
                summary: 'Plugin registers Filament pages inside the admin panel.',
            ),
            Capability::FrontendRoutes => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Green,
                title: 'Frontend routes',
                summary: 'Plugin registers publicly accessible routes.',
            ),
            Capability::QueueJobs => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Green,
                title: 'Queued jobs',
                summary: 'Plugin dispatches jobs to the queue.',
            ),
            Capability::ModifiesCoreModels => new CapabilityDescriptor(
                capability: $capability,
                warningLevel: CapabilityWarningLevel::Yellow,
                title: 'Modifies core models',
                summary: 'Plugin observes or extends core Capell models.',
            ),
        };
    }

    public static function parse(string $raw): CapabilityDescriptor
    {
        $parts = explode(':', $raw, 2);
        $capability = Capability::tryFrom($parts[0]);

        if ($capability === null) {
            throw new InvalidArgumentException('Unknown capability: ' . $parts[0]);
        }

        $parameter = $parts[1] ?? null;

        if ($capability->acceptsParameter() && $parameter === null) {
            throw new InvalidArgumentException(sprintf('Capability %s requires a parameter', $capability->value));
        }

        if (! $capability->acceptsParameter() && $parameter !== null) {
            throw new InvalidArgumentException(sprintf('Capability %s does not accept a parameter', $capability->value));
        }

        throw_if($capability === Capability::WritesFiles
            && $parameter !== null
            && ! in_array($parameter, ['storage', 'public', 'config'], true), InvalidArgumentException::class, sprintf('Capability writes_files parameter must be one of: storage, public, config (got: %s)', $parameter));

        return self::describe($capability, $parameter);
    }

    private static function resolveWritesFilesLevel(?string $parameter): CapabilityWarningLevel
    {
        return match ($parameter) {
            'storage' => CapabilityWarningLevel::Yellow,
            'public', 'config' => CapabilityWarningLevel::Red,
            default => CapabilityWarningLevel::Yellow,
        };
    }

    private static function resolveHttpOutboundLevel(?string $parameter): CapabilityWarningLevel
    {
        if ($parameter === null) {
            return CapabilityWarningLevel::Yellow;
        }

        return $parameter === 'capell.app' || str_ends_with($parameter, '.capell.app')
            ? CapabilityWarningLevel::Green
            : CapabilityWarningLevel::Yellow;
    }
}
