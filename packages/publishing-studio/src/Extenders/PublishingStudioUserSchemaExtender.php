<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Extenders;

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Support\Schemas\AbstractUserSchemaExtender;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\PreviewLinksRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\VersionsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceApprovalsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceFieldCommentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceReviewAssignmentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspacesRelationManager;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;
use Capell\PublishingStudio\Settings\PublishingStudioSettings;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Throwable;

class PublishingStudioUserSchemaExtender extends AbstractUserSchemaExtender
{
    public function supports(UserSchemaContextData $context): bool
    {
        return $this->shouldLoadBridge();
    }

    /**
     * @return array<int, Component>
     */
    public function extendSidebarComponents(Schema $schema, UserSchemaContextData $context): array
    {
        if (! $context->record instanceof Model) {
            return [];
        }

        return [
            Section::make(__('capell-publishing-studio::workspace.user_bridge.content_workflow'))
                ->icon('heroicon-o-document-check')
                ->compact()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            $this->summaryText('workspaces', fn (): int => $this->workspaceCount($context->record)),
                            $this->summaryText('review_assignments', fn (): int => $this->morphCount(WorkspaceReviewAssignment::query(), $context->record, 'reviewer')),
                            $this->summaryText('approvals', fn (): int => $this->morphCount(WorkspaceApproval::query(), $context->record, 'actionable')),
                            $this->summaryText('field_comments', fn (): int => $this->morphCount(WorkspaceFieldComment::query(), $context->record, 'author')),
                            $this->summaryText('preview_links', fn (): int => $this->morphCount(PreviewLink::query(), $context->record, 'issued_by')),
                            $this->summaryText('versions', fn (): int => $this->morphCount(Version::query(), $context->record, 'published_by')),
                        ]),
                ]),
        ];
    }

    /**
     * @param  array<int, mixed>  $relationManagers
     * @return array<int, mixed>
     */
    public function extendRelationManagers(Model $record, array $relationManagers, UserSchemaContextData $context): array
    {
        return [
            ...$relationManagers,
            WorkspacesRelationManager::class,
            WorkspaceReviewAssignmentsRelationManager::class,
            WorkspaceApprovalsRelationManager::class,
            WorkspaceFieldCommentsRelationManager::class,
            PreviewLinksRelationManager::class,
            VersionsRelationManager::class,
        ];
    }

    private function shouldLoadBridge(): bool
    {
        try {
            $packageSettingEnabled = resolve(PublishingStudioSettings::class)->enable_user_resource_bridge;
        } catch (Throwable) {
            $packageSettingEnabled = true;
        }

        $actionClass = 'Capell\\Admin\\Actions\\Users\\ShouldLoadUserResourceBridgeAction';

        if (class_exists($actionClass) && method_exists($actionClass, 'run')) {
            if (! $actionClass::run('enable_content_ownership_user_bridge', true)) {
                return false;
            }

            return $actionClass::run(
                'enable_publishing_studio_user_bridge',
                $packageSettingEnabled,
                PublishingStudioServiceProvider::$packageName,
            );
        }

        return $packageSettingEnabled;
    }

    private function summaryText(string $translationKey, callable $count): Text
    {
        return Text::make(fn (): string => sprintf(
            '%s: %s',
            __('capell-publishing-studio::workspace.user_bridge.' . $translationKey),
            Number::format($count()),
        ));
    }

    private function workspaceCount(Model $user): int
    {
        return Workspace::query()
            ->withoutGlobalScopes()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('created_by', $user->getKey())
                    ->orWhere('updated_by', $user->getKey());
            })
            ->count();
    }

    private function morphCount(Builder $query, Model $user, string $morphName): int
    {
        return $query
            ->withoutGlobalScopes()
            ->where($morphName . '_type', $user->getMorphClass())
            ->where($morphName . '_id', $user->getKey())
            ->count();
    }
}
