<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum EventType: string
{
    case AreaCreated = 'area_created';
    case RegistrationCreated = 'registration_created';
    case RegistrationApproved = 'registration_approved';
    case RegistrationRejected = 'registration_rejected';
    case GrantCreated = 'grant_created';
    case GrantRevoked = 'grant_revoked';
    case ClaimTokenCreated = 'claim_token_created';
    case ClaimTokenClaimed = 'claim_token_claimed';
    case BrowserTokenCreated = 'browser_token_created';
    case BrowserTokenRevoked = 'browser_token_revoked';
    case ExternalInviteCreated = 'external_invite_created';
    case ExternalInviteAccepted = 'external_invite_accepted';
    case UserLoggedIn = 'user_logged_in';
    case AccessDenied = 'access_denied';
}
