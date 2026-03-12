<?php

return [

    'rede_parcerias' => [
        'base_url'      => env('REDEPARCERIAS_BASE_URL', 'https://api.staging.clubeparcerias.com.br/api-client/v1'),
        'client_id'     => env('REDEPARCERIAS_CLIENT_ID', ''),
        'client_secret' => env('REDEPARCERIAS_CLIENT_SECRET', ''),
    ],

    'telemedicina' => [
        'base_url' => env('TELEMEDICINA_BASE_URL', ''),
        'api_key'  => env('TELEMEDICINA_API_KEY', ''),
    ],

];
