<?php

return [
    'plantnet' => [
        'key' => trim((string) env('PLANTNET_API_KEY', '')),
        'origin' => (string) env('PLANTNET_ORIGIN', 'https://marketingcriativa.com.br/'),
    ],
];
