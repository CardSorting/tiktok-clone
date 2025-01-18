<?php

namespace Tests\Feature;

use Aws\MediaConvert\MediaConvertClient;
use Tests\TestCase;

class MediaConvertTest extends TestCase
{
    public function test_mediaconvert_client_connection()
    {
        $mediaConvert = app('mediaconvert');
        
        $this->assertInstanceOf(MediaConvertClient::class, $mediaConvert);
        
        // Test endpoint connection
        $endpoints = $mediaConvert->describeEndpoints();
        $this->assertArrayHasKey('Endpoints', $endpoints->toArray());
    }

    public function test_mediaconvert_job_creation()
    {
        $mediaConvert = app('mediaconvert');
        
        // Test job creation with minimal settings
        $jobSettings = [
            'Role' => env('AWS_MEDIACONVERT_ROLE'),
            'Queue' => env('AWS_MEDIACONVERT_QUEUE'),
            'Settings' => [
                'OutputGroups' => [
                    [
                        'Name' => 'File Group',
                        'Outputs' => [
                            [
                                'Preset' => 'System-Generic_Hd_Mp4_Avc_Aac_16x9_1920x1080p_24Hz_6Mbps',
                                'Extension' => 'mp4',
                                'NameModifier' => '_1080p'
                            ]
                        ],
                        'OutputGroupSettings' => [
                            'Type' => 'FILE_GROUP_SETTINGS',
                            'FileGroupSettings' => [
                                'Destination' => 's3://'.env('AWS_BUCKET').'/test-output/'
                            ]
                        ]
                    ]
                ],
                'Inputs' => [
                    [
                        'FileInput' => 's3://'.env('AWS_BUCKET').'/test-input/test.mp4',
                        'AudioSelectors' => [
                            'Audio Selector 1' => [
                                'DefaultSelection' => 'DEFAULT'
                            ]
                        ],
                        'VideoSelector' => [
                            'ColorSpace' => 'FOLLOW'
                        ]
                    ]
                ]
            ]
        ];

        $job = $mediaConvert->createJob($jobSettings);
        
        $this->assertArrayHasKey('Job', $job->toArray());
        $this->assertArrayHasKey('Id', $job['Job']);
    }
}