<?php

declare(strict_types=1);

namespace Capell\MediaAI\Support;

use Capell\Core\Models\Media;
use Capell\MediaAI\Contracts\ImageDoctor;
use Capell\MediaAI\Data\ImageDoctorRequest;
use Capell\MediaAI\Data\ImageDoctorResult;

final class NullImageDoctor implements ImageDoctor
{
    public function doctor(Media $media, ImageDoctorRequest $request): ImageDoctorResult
    {
        return ImageDoctorResult::failure(__('capell-media-ai::media-ai.not_configured'));
    }
}
