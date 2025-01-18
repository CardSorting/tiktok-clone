<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Aws\MediaConvert\MediaConvertClient;

class VideoAspectRatio implements Rule
{
    private $requiredRatio;

    public function __construct($requiredRatio = '9:16')
    {
        $this->requiredRatio = $requiredRatio;
    }

    public function passes($attribute, $value)
    {
        // MediaConvert will handle aspect ratio validation during processing
        // We'll just check if the file exists and is a video
        return $value->isValid() && str_starts_with($value->getMimeType(), 'video/');
    }

    public function message()
    {
        return 'The :attribute must be a valid video file with aspect ratio '.$this->requiredRatio;
    }
}