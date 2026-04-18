<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Models\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Select run(Select $select)
 */
class ModifyContentSelectCreateAction
{
    use AsObject;

    public function handle(Select $select): Select
    {
        return $select
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modal()
                    ->modalHeading(__('capell-admin::generic.type'))
                    ->fillForm(function (): array {
                        $site = Site::getDefault();

                        /** @var class-string<Type> $model */
                        $model = CapellCore::getModel(ModelEnum::Type);

                        return [
                            'type_id' => $model::query()
                                ->where('type', LayoutTypeEnum::Content)
                                ->default()
                                ->value('id'),
                            'translations' => $site->translations->mapWithKeys(fn (Translation $translation): array => [
                                (string) Str::uuid() => [
                                    'language_id' => $translation->language_id,
                                ],
                            ])
                                ->all(),
                        ];
                    })
                    ->modalWidth(Width::ScreenLarge)
                    ->slideOver()
                    ->visible(fn (?int $state, Section $record): bool => filled($state))
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }
}
