<?php
// config/app.php (template for GitHub, do not include real credentials)
return [
    'base_url' => 'https://yourdomain.com',
    'qr_code_directory' => 'qrcodes/',
    'redirect_script' => 'redirect.php',
    'database' => [
        'dsn' => '', // e.g. 'mysql:host=localhost;dbname=qr_code_db;charset=utf8'
        'username' => '',
        'password' => '',
    ],
    'environments' => [
        'development' => [
            'database' => [
                'dsn' => '',
                'username' => '',
                'password' => '',
            ],
        ],
        'production' => [
            'database' => [
                'dsn' => '',
                'username' => '',
                'password' => '',
            ],
        ],
    ],
];
