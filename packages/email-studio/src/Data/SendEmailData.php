<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class SendEmailData extends Data
{
    /**
     * @param  DataCollection<int, EmailAddressData>  $to
     * @param  DataCollection<int, EmailAddressData>  $cc
     * @param  DataCollection<int, EmailAddressData>  $bcc
     * @param  array<string, mixed>  $variables
     * @param  DataCollection<int, EmailHeaderData>  $headers
     */
    public function __construct(
        public string $templateKey,
        public DataCollection $to,
        public DataCollection $cc,
        public DataCollection $bcc,
        public ?int $siteId,
        public string $siteScopeKey,
        public ?int $emailProfileId,
        public array $variables,
        public DataCollection $headers,
        public ?string $triggeredByType,
        public ?int $triggeredById,
        public bool $queue = true,
        public ?string $locale = null,
    ) {}
}
