<?php

return [
    'oci' => [
        'driver' => 'oci',
        'namespace' => env('OCI_NAMESPACE'),
        'region' => env('OCI_REGION'),
        'bucket' => env('OCI_BUCKET'),
        'tenancy_id' => env('OCI_TENANCY_ID'),
        'user_id' => env('OCI_USER_ID'),
        'storage_tier' => env('OCI_STORAGE_TIER'),
        'key_fingerprint' => env('OCI_KEY_FINGERPRINT'),
        'key_path' => env('OCI_KEY_PATH'),
    ],
];