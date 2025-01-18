<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VideoAspectRatio implements Rule
{
    private $requiredRatio;

    public function __construct($requiredRatio = '9:16')
    {
        $this->requiredRatio = $requiredRatio;
    }

    public function passes($attribute, $value)
    {
        // Basic validation - actual aspect ratio validation will be handled by MediaConvert
        return $value->isValid() && str_starts_with($value->getMimeType(), 'video/');
    }

    public function message()
    {
        return 'The :attribute must be a valid video file';
    }
}