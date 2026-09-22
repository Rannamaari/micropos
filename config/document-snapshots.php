<?php

return [
    'signing_key' => env('DOCUMENT_SNAPSHOT_SIGNING_KEY'),
    'signing_key_version' => (int) env('DOCUMENT_SNAPSHOT_SIGNING_KEY_VERSION', 1),
];
