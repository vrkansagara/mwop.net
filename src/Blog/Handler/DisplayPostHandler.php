<?php // phpcs:disable Generic.WhiteSpace.ScopeIndent.IncorrectExact

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop\Blog\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mwop\Blog\FetchBlogPostEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DisplayPostHandler implements RequestHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private TemplateRendererInterface $template,
        private RequestHandlerInterface $notFoundHandler,
        private array $disqus = [],
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id', false);

        if (! $id) {
            return $this->notFoundHandler->handle($request);
        }

        // @var \Mwop\Blog\FetchBlogPostEvent $event
        $event = $this->dispatcher->dispatch(new FetchBlogPostEvent($id));

        // @var null|\Mwop\Blog\BlogPost $post
        $post = $event->blogPost();

        if (! $post) {
            return $this->notFoundHandler->handle($request);
        }

        // @var \DateTimeInterface $lastModified
        $lastModified = $post->updated ?: $post->created;

        return new HtmlResponse(
            $this->template->render('blog::post', [
                'post'   => $post,
                'disqus' => $this->disqus,
            ]),
            200,
            [
                'Last-Modified' => $lastModified->format('r'),
            ]
        );
    }
}
