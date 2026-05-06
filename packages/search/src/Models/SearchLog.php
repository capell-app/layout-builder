<?php

declare(strict_types=1);

namespace Capell\Search\Models;

use Capell\Search\Database\Factories\SearchLogFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $site_id
 * @property int|null $language_id
 * @property string $query
 * @property string $normalized_query
 * @property int $results_count
 * @property string|null $clicked_result_url
 * @property string|null $ip_hash
 * @property string|null $user_agent_hash
 * @property CarbonImmutable $searched_at
 */
final class SearchLog extends Model
{
    /** @use HasFactory<SearchLogFactory> */
    use HasFactory;

    protected static string $factory = SearchLogFactory::class;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('capell-search.logs.table_name', 'search_logs');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'searched_at' => 'immutable_datetime',
        ];
    }
}
