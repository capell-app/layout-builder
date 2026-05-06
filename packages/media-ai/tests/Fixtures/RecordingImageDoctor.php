<?php

declare(strict_types=1);

namespace Capell\MediaAI\Tests\Fixtures;

use Capell\Core\Models\Media;
use Capell\MediaAI\Contracts\ImageDoctor;
use Capell\MediaAI\Data\ImageDoctorRequest;
use Capell\MediaAI\Data\ImageDoctorResult;

final class RecordingImageDoctor implements ImageDoctor
{
    public ?Media $media = null;

    public ?ImageDoctorRequest $request = null;

    public function doctor(Media $media, ImageDoctorRequest $request): ImageDoctorResult
    {
        $this->media = $media;
        $this->request = $request;

        return ImageDoctorResult::success('Doctor finished');
    }
}
