<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class VideoProcessingException extends Exception
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected $message = 'An error occurred while processing the video';

    public function __construct(?string $message = null, ?int $code = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }

        if ($code !== null) {
            $this->code = $code;
        }

        parent::__construct($this->message, $this->code);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => $this->code,
        ], $this->code);
    }
}