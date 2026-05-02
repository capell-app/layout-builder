<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Pages\Actions;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Override;

class SaveAsDraftFormAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::button.save_as_draft'))
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->modalHeading(__('capell-admin::button.save_as_draft_modal_heading'))
            ->modalDescription(__('capell-admin::message.save_as_draft_description'))
            ->modalSubmitActionLabel(__('capell-admin::button.save_as_draft_modal_submit'))
            ->form(fn (): array => $this->formSchema())
            ->fillForm(fn (): array => $this->defaults())
            ->action(function (array $data): void {
                $this->getLivewire()->saveAsDraftWithLocation($data);
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'saveAsDraft';
    }

    /** @return array<int, Component> */
    private function formSchema(): array
    {
        $active = WorkspaceContext::current();

        $radio = Radio::make('location')
            ->hiddenLabel()
            ->required()
            ->options($this->locationOptions($active))
            ->live();

        $select = Select::make('workspace_id')
            ->label(__('capell-admin::generic.workspace'))
            ->required()
            ->options($this->workspaceOptions($active))
            ->visible(fn (Get $get): bool => $get('location') === 'other');

        return [$radio, $select];
    }

    /** @return array<string, string> */
    private function locationOptions(?Workspace $active): array
    {
        $options = ['new' => __('capell-admin::message.save_as_draft_option_new')];

        if ($active instanceof Workspace) {
            $options['active'] = __('capell-admin::message.save_as_draft_option_active', ['workspace' => $active->name]);
        }

        $options['other'] = __('capell-admin::message.save_as_draft_option_other');

        return $options;
    }

    /** @return array<int|string, string> */
    private function workspaceOptions(?Workspace $active): array
    {
        return Workspace::query()
            ->where('status', WorkspaceStatusEnum::Open)
            ->when(
                $active,
                fn ($query) => $query->where('id', '!=', $active->id),
            )
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'location' => WorkspaceContext::isInWorkspace() ? 'active' : 'new',
        ];
    }
}
