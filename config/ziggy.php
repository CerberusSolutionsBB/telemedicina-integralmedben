<?php

return [
    // Rotas internas que não precisam ser expostas ao front-end
    'except' => [
        'sanctum.*',
        'storage.*',
        'stancl.*',
    ],
];
