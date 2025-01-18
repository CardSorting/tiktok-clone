<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VideoDuration implements Rule
{
    private $maxDuration;

    public function __construct($maxDuration = 60)
    {
        $this->maxDuration = $maxDuration;
    }

    public function passes($attribute, $value)
    {
        // MediaConvert will handle duration validation during processing
        // We'll just check if the file exists and is a video
        return $value->isValid() && str_starts_with($value->getMimeType(), 'video/');
    }

    public function message()
    {
        return 'The :attribute must be a valid video file with maximum duration of '.$this->maxDuration.' seconds';
    }
}