<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'holerite',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'company' => [
        'name' => 'XYZ Ltda.',
        'brand_name' => 'JP Fábrica de Salgados',
        'document' => '00.000.000/0001-00',
        'address' => 'Rua Exemplo, 123',
        'city' => 'São Paulo',
        'state' => 'SP',
        'zip_code' => '01000-000',
        'phone' => '(11) 0000-0000',
        'email' => 'contato@xyz.com.br',
        'header_logo_path' => 'img/logo.png',
        'theme_mode' => 'light',
        'color_palette' => 'dark-red',
        'auto_backup_enabled' => true,
        'auto_backup_interval_minutes' => 1440,
    ],
    'holidays' => [
        'include_national' => true,
        'include_optional' => true,
        'include_municipal' => true,
        'state' => 'SP',
        'city' => 'São Paulo',
        'municipal_holidays' => [
            ['name' => 'Aniversário da cidade', 'month' => 1, 'day' => 25],
        ],
    ],
    'services' => [
        'contributions_api' => [
            'endpoint' => '',
            'method' => 'GET',
            'token' => '',
            'token_header' => 'Authorization',
            'token_prefix' => 'Bearer ',
            'headers' => [],
            'timeout' => 10,
            'provider' => 'Portal Gov.br',
            'body' => null,
        ],
    ],
];
