<?php

declare(strict_types=1);

namespace Capell\MediaAssistant\Tests\Fixtures;

use Capell\Core\Models\Media;
use Capell\MediaAssistant\Contracts\ImageDoctor;
use Capell\MediaAssistant\Data\ImageDoctorRequest;
use Capell\MediaAssistant\Data\ImageDoctorResult;

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
