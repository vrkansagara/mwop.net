<?php

declare(strict_types=1);

namespace Mwop\OAuth2;

use Mezzio\Application;
use Mezzio\Session\SessionMiddleware;
use Phly\ConfigFactory\ConfigFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'oauth'        => [],
            'debug'        => false,
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplateConfig(),
        ];
    }

    public function getDependencies(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'factories' => [
                'config-oauth2'                                 => ConfigFactory::class,
                Handler\CallbackHandler::class                  => Handler\CallbackHandlerFactory::class,
                Handler\RequestAuthenticationHandler::class     => Handler\RequestAuthenticationHandlerFactory::class,
                Middleware\CheckAuthenticationMiddleware::class => Middleware\CheckAuthenticationMiddlewareFactory::class,
                Provider\ProviderFactory::class                 => Provider\ProviderFactoryFactory::class,
            ],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    public function getTemplateConfig(): array
    {
        return [
            'paths' => [
                'oauth2' => [__DIR__ . '/templates'],
            ],
        ];
    }

    public function registerRoutes(Application $app, string $basePath = '/blog'): void
    {
        // OAuth2 authentication response
        $app->get($basePath . '/{provider:debug|github|google}/oauth2callback', [
            SessionMiddleware::class,
            Handler\CallbackHandler::class,
        ]);

        // OAuth2 authentication request
        $app->get($basePath . '/{provider:debug|github|google}', [
            SessionMiddleware::class,
            Handler\RequestAuthenticationHandler::class,
        ]);
    }
}
