<?php

declare(strict_types=1);

namespace Mwop\Blog\Mapper;

use PDO;
use Psr\Container\ContainerInterface;

class MapperFactory
{
    public function __invoke(ContainerInterface $container): PdoMapper
    {
        $config = $container->get('config-blog');
        $pdo    = new PDO($config['db'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return new PdoMapper($pdo);
    }
}
