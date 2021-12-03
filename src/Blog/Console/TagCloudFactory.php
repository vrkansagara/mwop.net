<?php

declare(strict_types=1);

namespace Mwop\Blog\Console;

use Mwop\Blog\Mapper\MapperInterface;
use Psr\Container\ContainerInterface;

class TagCloudFactory
{
    public function __invoke(ContainerInterface $container): TagCloud
    {
        return new TagCloud($container->get(MapperInterface::class));
    }
}
