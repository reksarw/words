<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'upload_directory' => __DIR__ . '/../public/resources/uploads', // upload directory
        
        // Monolog settings
        'logger' => [
            'name' => 'words',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database Settings
        'db' => [
            'host' => 'localhost',
            'user' => 'root',
            'pass' => 'reksarw',
            'dbname' => 'words',
            'driver' => 'mysql'
        ],
    ],
];
