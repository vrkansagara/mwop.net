<?php

declare(strict_types=1);

namespace Mwop\App\Handler;

use Mezzio\MiddlewareFactory;
use Mezzio\Session\SessionMiddleware;
use Mwop\OAuth2\Middleware\CheckAuthenticationMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

class ComicsPageHandlerAuthDelegator
{
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $callback
    ): MiddlewareInterface {
        $factory = $container->get(MiddlewareFactory::class);
        return $factory->pipeline(
            $container->get(SessionMiddleware::class),
            $container->get(CheckAuthenticationMiddleware::class),
            $callback()
        );
    }
}
