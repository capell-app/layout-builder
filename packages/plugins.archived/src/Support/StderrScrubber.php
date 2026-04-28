<?php

declare(strict_types=1);

namespace Capell\Plugins\Support;

/**
 * Scrubs composer stderr tails before we persist them to the audit log.
 *
 * Composer prints http-basic auth as `user:password@host/` inside URLs on
 * failure, and anystack uses `unlock:<license_key>:<fingerprint>` for that
 * user:password. If we persisted the raw stderr, an operator reviewing the
 * audit log would see the license key in plain text. This helper redacts
 * credentials embedded in URLs and, when the caller has the license key
 * itself in hand, replaces any literal occurrences too.
 */
final class StderrScrubber
{
    private const REDACTED = '[REDACTED]';

    public static function scrub(string $stderr, ?string $licenseKey): string
    {
        if ($stderr === '') {
            return $stderr;
        }

        // URL form: https://user:password@host.composer.sh/...
        $scrubbed = (string) preg_replace(
            '#(https?://)([^:@/\s]+):([^@/\s]+)@([^/\s]+\.composer\.sh)#i',
            '$1' . self::REDACTED . '@$4',
            $stderr,
        );

        // Composer's `http-basic.<host>.composer.sh` config line can print as
        // `http-basic.host.composer.sh: "user":"password"` in some outputs.
        $scrubbed = (string) preg_replace(
            '#(http-basic\.[^\s:]+\.composer\.sh[^"\']*["\'])([^"\']+)(["\']\s*[:=]\s*["\'])([^"\']+)(["\'])#i',
            '$1' . self::REDACTED . '$3' . self::REDACTED . '$5',
            $scrubbed,
        );

        if ($licenseKey !== null && $licenseKey !== '') {
            return str_replace($licenseKey, self::REDACTED, $scrubbed);
        }

        return $scrubbed;
    }
}
