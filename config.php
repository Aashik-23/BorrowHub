<?php
return [
    'host' => getenv('BH_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('BH_DB_PORT') ?: '3306',
    'database' => getenv('BH_DB_NAME') ?: 'borrowhub',
    'username' => getenv('BH_DB_USER') ?: 'root',
    'password' => getenv('BH_DB_PASS') ?: '',
    'timezone' => 'Asia/Colombo',
];
