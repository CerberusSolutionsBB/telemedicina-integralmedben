<?php

return [
    'base_url' => env('SIPROV_API_URL', 'https://acesso.siprov.com.br/siprov-api'),

    'user' => env('SIPROV_USER'),
    'password' => env('SIPROV_PASSWORD'),

    'cod_loja' => env('SIPROV_COD_LOJA', 5578),

    'planos' => [
        'clinica_familiar' => 331385,
        'clinica_individual' => 331384,
        'saude_mental' => 331386,
    ],
];
