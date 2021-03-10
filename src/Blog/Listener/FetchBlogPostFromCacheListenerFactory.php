<?php // phpcs:disable Generic.PHP.DiscourageGoto.Found

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop\Blog\Listener;

use Mwop\Blog\BlogCachePool;
use Psr\Container\ContainerInterface;

class FetchBlogPostFromCacheListenerFactory
{
    public function __invoke(ContainerInterface $container): FetchBlogPostFromCacheListener
    {
        $config = $container->get('config-blog.cache');

        return new FetchBlogPostFromCacheListener(
            cache: $container->get(BlogCachePool::class),
            enabled: $config['enabled'] ?? false,
        );
    }
}
