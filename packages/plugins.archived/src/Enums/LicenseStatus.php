<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case PastDue = 'past_due';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Cancelled = 'cancelled';
    /** Entitled to older versions only (anystack `RESTRICTED`). */
    case Restricted = 'restricted';
    /** Fingerprint mismatch — install identity invalid (anystack `FINGERPRINT_*`). */
    case Invalid = 'invalid';

    /**
     * Whether the plugin should load at all. Restricted and Invalid are
     * terminal until manual intervention (user renews / re-activates on a
     * fresh install), so they're NOT usable — admin renders a warning
     * banner instead of loading the plugin.
     */
    public function isUsable(): bool
    {
        return in_array($this, [self::Active, self::Trial, self::PastDue], true);
    }
}
