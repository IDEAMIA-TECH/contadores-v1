<?php
return [
    'session' => [
        'name' => 'IDEAMIA_SESSION',
        'lifetime' => 7200, // 2 horas
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    'csrf' => [
        'token_length' => 32,
        'token_lifetime' => 7200 // 2 horas
    ],
    'password' => [
        'algo' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]
    ]
]; 