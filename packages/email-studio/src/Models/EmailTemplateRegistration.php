<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailTemplateRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateRegistration extends Model
{
    /** @use HasFactory<EmailTemplateRegistrationFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'template_key',
        'package_name',
        'name',
        'description',
        'variables',
    ];

    protected static string $factory = EmailTemplateRegistrationFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.template_registrations');

        return is_string($tableName) ? $tableName : 'email_template_registrations';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }
}
