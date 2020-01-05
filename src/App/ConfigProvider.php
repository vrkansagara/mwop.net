<?php
/**
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) Matthew Weier O'Phinney
 */

namespace Mwop\App;

use League\Plates\Engine;
use Middlewares\Csp;
use Mwop\Blog\Handler\DisplayPostHandler;
use Phly\Expressive\ConfigFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Swift_AWSTransport;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Mezzio\Application;
use Mezzio\Session\SessionMiddleware;
use Laminas\Feed\Reader\Http\ClientInterface as FeedReaderHttpClientInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies'            => $this->getDependencies(),
            'cache'                   => $this->getCacheConfig(),
            'content-security-policy' => [],
            'homepage'                => $this->getHomePageConfig(),
            'mail'                    => $this->getMailConfig(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'invokables' => [
                Middleware\RedirectsMiddleware::class       => Middleware\RedirectsMiddleware::class,
                Middleware\XClacksOverheadMiddleware::class => Middleware\XClacksOverheadMiddleware::class,
                Middleware\XPoweredByMiddleware::class      => Middleware\XPoweredByMiddleware::class,
                RequestFactoryInterface::class              => RequestFactory::class,
                ResponseFactoryInterface::class             => ResponseFactory::class,
            ],
            // @codingStandardsIgnoreStart
            // phpcs:disable
            'factories' => [
                'config-cache'                               => ConfigFactory::class,
                'config-content-security-policy'             => ConfigFactory::class,
                'config-homepage'                            => ConfigFactory::class,
                'config-homepage.posts'                      => ConfigFactory::class,
                'config-instagram.feed'                      => ConfigFactory::class,
                'config-mail.transport'                      => ConfigFactory::class,
                Csp::class                                   => Middleware\ContentSecurityPolicyMiddlewareFactory::class,
                CacheItemPoolInterface::class                => Factory\CachePoolFactory::class,
                EventDispatcherInterface::class              => Factory\EventDispatcherFactory::class,
                FeedReaderHttpClientInterface::class         => Feed\HttpPlugClientFactory::class,
                Handler\ComicsPageHandler::class             => Handler\ComicsPageHandlerFactory::class,
                Handler\HomePageHandler::class               => Handler\HomePageHandlerFactory::class,
                Handler\ResumePageHandler::class             => Handler\PageHandlerFactory::class,
                Handler\ResumePageHandler::class             => Handler\PageHandlerFactory::class,
                'mail.transport'                             => Factory\MailTransport::class,
                Middleware\RedirectAmpPagesMiddleware::class => Middleware\RedirectAmpPagesMiddlewareFactory::class,
                SessionCachePool::class                      => SessionCachePoolFactory::class,
            ],
            // phpcs:enable
            // @codingStandardsIgnoreEnd
            'delegators' => [
                DisplayPostHandler::class => [
                    Middleware\DisplayBlogPostHandlerDelegator::class,
                ],
                Engine::class => [
                    Factory\PlatesFunctionsDelegator::class,
                ],
            ],
        ];
    }

    public function getCacheConfig() : array
    {
        return [
            'connection-parameters' => [
                'scheme' => 'tcp',
                'host' => 'redis',
                'port' => 6379,
            ],
        ];
    }

    public function getHomePageConfig() : array
    {
        return [
            'feed-count' => 10,
            'feeds' => [
                [
                    'url' => realpath(getcwd()) . '/data/feeds/rss.xml',
                    'sitename' => 'mwop.net',
                    'siteurl' => 'https://mwop.net/blog',
                ],
                [
                    'url' => 'https://framework.zend.com/blog/feed-rss.xml',
                    'favicon' => 'https://framework.zend.com/ico/favicon.ico',
                    'sitename' => 'Zend Framework Blog',
                    'siteurl' => 'https://framework.zend.com/blog/',
                    'filters' => [
                        function ($entry) {
                            return (false !== strpos($entry->getAuthor()['name'], 'Phinney'));
                        },
                    ],
                ],
            ],
            'posts' => [],
        ];
    }

    public function getMailConfig() : array
    {
        return [
            'transport' => [
                'class'    => Swift_AWSTransport::class,
                'username' => '',
                'password' => '',
            ],
        ];
    }

    public function registerRoutes(Application $app) : void
    {
        $app->get('/', Handler\HomePageHandler::class, 'home');
        $app->get('/comics', Handler\ComicsPageHandler::class, 'comics');
        $app->get('/resume', Handler\ResumePageHandler::class, 'resume');

        // Logout
        $app->get('/logout', [
            SessionMiddleware::class,
            Handler\LogoutHandler::class
        ], 'logout');
    }
}
