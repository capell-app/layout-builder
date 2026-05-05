<?php

declare(strict_types=1);

namespace Capell\MediaAssistant\Contracts;

use Capell\Core\Models\Media;
use Capell\MediaAssistant\Data\ImageDoctorRequest;
use Capell\MediaAssistant\Data\ImageDoctorResult;

interface ImageDoctor
{
    public function doctor(Media $media, ImageDoctorRequest $request): ImageDoctorResult;
}
