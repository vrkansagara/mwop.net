<?php

declare(strict_types=1);

namespace Mwop\Blog;

use Cache\Hierarchy\HierarchicalPoolInterface;
use Cache\Namespaced\NamespacedCachePool;

/**
 * PSR-6 cache pool implementation for blog posts.
 *
 * Empty extension to allow typehinting/`::class` resolution.
 */
class BlogCachePool extends NamespacedCachePool
{
    public function __construct(HierarchicalPoolInterface $cachePool)
    {
        parent::__construct($cachePool, 'blog');
    }
}
