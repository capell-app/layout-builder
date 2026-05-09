<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Actions;

use Capell\EmailStudio\Enums\EmailVariantStatus;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class ResolveEmailTemplateVariantAction
{
    use AsAction;

    public function handle(EmailTemplate $template, string $siteScopeKey = 'global', ?string $locale = null): ?EmailTemplateVariant
    {
        return $template->variants()
            ->where('status', EmailVariantStatus::Active)
            ->whereIn('site_scope_key', [$siteScopeKey, 'global'])
            ->orderByRaw('case when site_scope_key = ? then 0 else 1 end', [$siteScopeKey])
            ->when($locale !== null, fn (Builder $query): Builder => $query
                ->whereIn('locale', [$locale, null])
                ->orderByRaw('case when locale = ? then 0 when locale is null then 1 else 2 end', [$locale]))
            ->latest('version')
            ->first();
    }
}
