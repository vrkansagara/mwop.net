<?php
/**
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) Matthew Weier O'Phinney
 */

namespace Mwop\Blog;

use Mni\FrontYAML\Bridge\CommonMark\CommonMarkParser;
use Mni\FrontYAML\Parser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class DisplayPostMiddleware implements MiddlewareInterface
{
    private $disqus;

    private $mapper;

    /**
     * Delimiter between post summary and extended body
     *
     * @var string
     */
    private $postDelimiter = '<!--- EXTENDED -->';

    private $router;

    private $template;

    public function __construct(
        MapperInterface $mapper,
        TemplateRendererInterface $template,
        RouterInterface $router,
        array $disqus = []
    ) {
        $this->mapper   = $mapper;
        $this->template = $template;
        $this->router   = $router;
        $this->disqus   = $disqus;
    }

    /**
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $post = $this->mapper->fetch($request->getAttribute('id', false));

        if (! $post) {
            return $handler->handle($request);
        }

        $isAmp = (bool) ($request->getQueryParams()['amp'] ?? false);

        $parser   = new Parser(null, new CommonMarkParser());
        $document = $parser->parse(file_get_contents($post['path']));
        $post     = $document->getYAML();
        $parts    = explode($this->postDelimiter, $document->getContent(), 2);
        $post     = array_merge($post, [
            'body'      => $parts[0],
            'extended'  => isset($parts[1]) ? $parts[1] : '',
            'updated'   => $post['updated'] && $post['updated'] !== $post['created'] ? $post['updated'] : false,
            'tags'      => is_array($post['tags']) ? $post['tags'] : explode('|', trim((string) $post['tags'], '|')),
        ]);

        return new HtmlResponse($this->template->render(
            $isAmp ? 'blog::post.amp' : 'blog::post',
            [
                'post' => $post,
                'disqus' => $this->disqus,
            ]
        ));
    }
}
