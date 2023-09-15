<?php

use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;

return [
    Mustache_Engine::class => function () {
        $options = [
            // Path to templates
            'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../templates'),

            // Use UTF-8 html encoding
            'escape' => function ($value) {
                return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },

            // Enable cache in production
            //'cache' => __DIR__ . '/../tmp/templates',
            //'cache_file_mode' => 0755,
        ];
        return new Mustache_Engine($options);
    },

    SessionManagerInterface::class => function (ContainerInterface $container) {
        return $container->get(SessionInterface::class);
    },

    SessionInterface::class => function () {
        $options = [
            'name' => 'app',
            'lifetime' => 0,
            'path' => null,
            'domain' => null,
            // SameSite-Cookie
            // Protection against cross-site request forgery attacks
            'cookie_samesite' => 'Lax',
            // Protection against session hijacking and session fixation
            // Uses a secure connection (HTTPS) if possible
            'secure' => true,
            'httponly' => true,
            'cache_limiter' => 'nocache',
        ];

        return new PhpSession($options);
    },
];