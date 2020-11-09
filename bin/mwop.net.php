#!/usr/bin/env php
<?php

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

use function chdir;

chdir(__DIR__ . '/../');
require_once 'vendor/autoload.php';

$container = require 'config/container.php';

$application = new Application('mwop.net');
$application->setCommandLoader(new ContainerCommandLoader($container, [
    'blog:clear-cache'          => Blog\Console\ClearCache::class,
    'blog:feed-generator'       => Blog\Console\FeedGenerator::class,
    'blog:generate-search-data' => Blog\Console\GenerateSearchData::class,
    'blog:seed-db'              => Blog\Console\SeedBlogDatabase::class,
    'blog:tag-cloud'            => Blog\Console\TagCloud::class,
    'clear-cache'               => Console\ClearCache::class,
    'github:fetch-activity'     => Github\Console\Fetch::class,
    'homepage-feeds'            => Console\FeedAggregator::class,
    'instagram-feeds'           => Console\InstagramFeed::class,
]));
$application->setDefaultCommand('list');
$application->run();
