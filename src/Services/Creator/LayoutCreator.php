<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\LayoutEnum;
use InvalidArgumentException;

class LayoutCreator
{
    /**
     * @var class-string<Layout>
     */
    private readonly string $layoutModel;

    public function __construct()
    {
        $this->layoutModel = CapellCore::getModel(ModelEnum::Layout);
    }

    public function create(string $key): Layout
    {
        return match ($key) {
            'home' => $this->createHomeLayout(),
            default => throw new InvalidArgumentException('Invalid layout key: '.$key),
        };
    }

    private function createHomeLayout(): Layout
    {
        return $this->layoutModel::firstOrCreate(['key' => LayoutEnum::Home->value], [
            'name' => __('capell-admin::generic.home'),
            'group' => LayoutGroupEnum::Default,
        ]);
    }
}
