<?php
// Application configuration
return [
    'db' => [
        'host' => '127.0.0.1', // XAMPP default
        'port' => 3306,
        'name' => 'diary_app',
        'user' => 'root', // XAMPP default
        'pass' => '',     // XAMPP default (empty)
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'base_url' => '', // If hosting in subfolder, set like '/CSE311LBaseline'
        'session_name' => 'diaryapp_sess',
        'display_errors' => true
    ],
    'uploads' => [
        'dir' => __DIR__ . '/../uploads',
        'max_size_bytes' => 5 * 1024 * 1024, // 5MB
        'allowed_mime' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'audio/mpeg', 'audio/wav', 'audio/ogg'
        ]
    ]
];
