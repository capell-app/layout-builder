<?php

declare(strict_types=1);

namespace Capell\MediaAssistant\Support;

use Capell\Core\Models\Media;
use Capell\MediaAssistant\Contracts\ImageDoctor;
use Capell\MediaAssistant\Data\ImageDoctorRequest;
use Capell\MediaAssistant\Data\ImageDoctorResult;

final class NullImageDoctor implements ImageDoctor
{
    public function doctor(Media $media, ImageDoctorRequest $request): ImageDoctorResult
    {
        return ImageDoctorResult::failure(__('capell-media-assistant::media-assistant.not_configured'));
    }
}
