<?php // phpcs:disable Generic.WhiteSpace.ScopeIndent.IncorrectExact

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop\Blog\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Mwop\Blog\Mapper\MapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_merge;
use function count;
use function iterator_to_array;
use function sprintf;
use function str_replace;

class ListPostsHandler implements RequestHandlerInterface
{
    public function __construct(
        private MapperInterface $mapper,
        private TemplateRendererInterface $template,
        private RouterInterface $router,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tag   = str_replace(['+', '%20'], ' ', $request->getAttribute('tag', ''));
        $path  = $request->getAttribute('originalRequest', $request)->getUri()->getPath();
        $page  = $this->getPageFromRequest($request);
        $posts = $tag ? $this->mapper->fetchAllByTag($tag) : $this->mapper->fetchAll();

        $posts->setItemCountPerPage(10);

        // If the requested page is later than the last, redirect to the last
        if (count($posts) && $page > count($posts)) {
            return new RedirectResponse(sprintf('%s?page=%d', $path, count($posts)));
        }

        $posts->setCurrentPageNumber($page);

        return new HtmlResponse($this->template->render(
            'blog::list',
            $this->prepareView(
                $tag,
                iterator_to_array($posts->getItemsByPage($page)),
                $this->preparePagination($path, $page, $posts->getPages())
            )
        ));
    }

    private function getPageFromRequest(ServerRequestInterface $request): int
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $page = (int) $page;
        return $page < 1 ? 1 : $page;
    }

    private function preparePagination(string $path, int $page, object $pagination): object
    {
        $pagination->base_path = $path;
        $pagination->is_first  = $page === $pagination->first;
        $pagination->is_last   = $page === $pagination->last;

        $pages = [];
        for ($i = $pagination->firstPageInRange; $i <= $pagination->lastPageInRange; $i += 1) {
            $pages[] = [
                'base_path' => $path,
                'number'    => $i,
                'current'   => $page === $i,
            ];
        }
        $pagination->pages = $pages;

        return $pagination;
    }

    /**
     * @param BlogPost[] $entries
     */
    private function prepareView(string $tag, array $entries, object $pagination): array
    {
        $view = $tag ? ['tag' => $tag] : [];
        if ($tag) {
            $view['atom'] = $this->router->generateUri('blog.tag.feed', ['tag' => $tag, 'type' => 'atom']);
            $view['rss']  = $this->router->generateUri('blog.tag.feed', ['tag' => $tag, 'type' => 'rss']);
        }

        return array_merge($view, [
            'title'      => $tag ? 'Tag: ' . $tag : 'Blog Posts',
            'posts'      => $entries,
            'pagination' => $pagination,
        ]);
    }
}
