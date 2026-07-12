<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum WidgetPresentationCapability: string
{
    case DeliveryMode = 'delivery_mode';
    case DeviceVisibility = 'device_visibility';
    case ConnectionRequirement = 'connection_requirement';
    case LoadingStrategy = 'loading_strategy';
    case Width = 'width';
    case Alignment = 'alignment';
    case PresentationPreset = 'presentation_preset';
}
