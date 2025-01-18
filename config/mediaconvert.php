<?php

return [
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'endpoint' => env('AWS_MEDIACONVERT_ENDPOINT'),
    'queue' => env('AWS_MEDIACONVERT_QUEUE'),
    'role' => env('AWS_MEDIACONVERT_ROLE'),
    'bucket' => env('AWS_BUCKET'),
    'presets' => [
        'default' => [
            'outputs' => [
                [
                    'Preset' => 'System-Generic_Hd_Mp4_Avc_Aac_16x9_1920x1080p_24Hz_6Mbps',
                    'Extension' => 'mp4',
                    'NameModifier' => '_1080p'
                ],
                [
                    'Preset' => 'System-Generic_Hd_Mp4_Avc_Aac_16x9_1280x720p_24Hz_4.5Mbps',
                    'Extension' => 'mp4',
                    'NameModifier' => '_720p'
                ]
            ]
        ]
    ]
];