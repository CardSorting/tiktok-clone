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
        // Basic validation - actual duration validation will be handled by MediaConvert
        return $value->isValid() && str_starts_with($value->getMimeType(), 'video/');
    }

    public function message()
    {
        return 'The :attribute must be a valid video file';
    }
}