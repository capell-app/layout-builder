<?php

declare(strict_types=1);

namespace Capell\MediaAI\Contracts;

use Capell\Core\Models\Media;
use Capell\MediaAI\Data\ImageDoctorRequest;
use Capell\MediaAI\Data\ImageDoctorResult;

interface ImageDoctor
{
    public function doctor(Media $media, ImageDoctorRequest $request): ImageDoctorResult;
}
