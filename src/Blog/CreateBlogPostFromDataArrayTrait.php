<?php // phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop\Blog;

use DateTime;
use DateTimeZone;
use Mni\FrontYAML\Bridge\CommonMark\CommonMarkParser;
use Mni\FrontYAML\Parser;
use RuntimeException;

use function explode;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function sprintf;
use function trim;

trait CreateBlogPostFromDataArrayTrait
{
    /** @var Parser */
    private $parser;

    /**
     * Delimiter between post summary and extended body
     *
     * @var string
     */
    private $postDelimiter = '<!--- EXTENDED -->';

    private function getParser(): Parser
    {
        if (! $this->parser) {
            $this->parser = new Parser(null, new CommonMarkParser());
        }

        return $this->parser;
    }

    private function createBlogPostFromDataArray(array $post): BlogPost
    {
        $path     = $post['path'] ?? throw new RuntimeException(sprintf(
            'Blog data provided does not include a "path" element; cannot create %s instance',
            BlogPost::class
        ));
        $parser   = $this->getParser();
        $document = $parser->parse(file_get_contents($path));
        $post     = $document->getYAML();
        $parts    = explode($this->postDelimiter, $document->getContent(), 2);
        $created  = $this->createDateTimeFromString($post['created']);
        $updated  = $post['updated'] && $post['updated'] !== $post['created']
            ? $this->createDateTimeFromString($post['updated'])
            : $created;

        return new BlogPost(
            $post['id'],
            $post['title'],
            $post['author'],
            $created,
            $updated,
            is_array($post['tags'])
                ? $post['tags']
                : explode('|', trim((string) $post['tags'], '|')),
            $parts[0],
            $parts[1] ?? '',
            (bool) $post['draft'],
            (bool) $post['public']
        );
    }

    private function createDateTimeFromString(string $dateString): DateTime
    {
        return is_numeric($dateString)
            ? new DateTime('@' . $dateString, new DateTimeZone('America/Chicago'))
            : new DateTime($dateString);
    }
}
