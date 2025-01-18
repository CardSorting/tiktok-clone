<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\VideoDuration;
use App\Rules\VideoAspectRatio;

class VideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'caption' => ['nullable', 'string', 'max:255'],
            'is_private' => ['boolean'],
        ];

        if ($this->isMethod('POST')) {
            $rules['video'] = [
                'required',
                'file',
                'mimetypes:video/mp4,video/quicktime',
                'max:51200', // 50MB
            ];
            
            $rules['thumbnail'] = [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'video.required' => 'A video file is required',
            'video.mimetypes' => 'The video must be a valid MP4 or MOV file',
            'video.max' => 'The video may not be greater than 50MB',
            'thumbnail.image' => 'The thumbnail must be a valid image',
            'thumbnail.mimes' => 'The thumbnail must be a JPEG, PNG, or JPG file',
            'thumbnail.max' => 'The thumbnail may not be greater than 2MB',
        ];
    }

    public function attributes(): array
    {
        return [
            'caption' => 'video caption',
            'is_private' => 'privacy setting',
            'video' => 'video file',
            'thumbnail' => 'thumbnail image',
        ];
    }
}