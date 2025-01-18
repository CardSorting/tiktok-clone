<?php

namespace App\Providers;

use Aws\MediaConvert\MediaConvertClient;
use Illuminate\Support\ServiceProvider;

class MediaConvertServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('mediaconvert', function() {
            $config = config('mediaconvert');
            
            $client = new MediaConvertClient([
                'version' => '2017-08-29',
                'region' => $config['region'],
                'endpoint' => $config['endpoint'],
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ]
            ]);

            return $client;
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/mediaconvert.php' => config_path('mediaconvert.php'),
        ], 'mediaconvert-config');
    }
}