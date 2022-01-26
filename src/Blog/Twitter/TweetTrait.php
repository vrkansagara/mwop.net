<?php

declare(strict_types=1);

namespace Mwop\Blog\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use Mezzio\Helper\UrlHelper;
use Mwop\Blog\BlogPost;

use function array_map;
use function implode;
use function sprintf;
use function str_replace;

trait TweetTrait
{
    private static string $schemeAndAuthority = 'https://mwop.net';
    private string $logoPath;
    private UrlHelper $urlHelper;

    private function generateStatusFromPost(BlogPost $post, string $template): string
    {
        return str_replace(
            [
                '%title%',
                '%link%',
                '%tags%',
            ],
            [
                $post->title,
                $this->createPostUrl($post),
                implode(' ', array_map(fn (string $tag) => sprintf('#%s', $tag), $post->tags)),
            ],
            $template
        );
    }

    private function createPostUrl(BlogPost $post): string
    {
        return $this::$schemeAndAuthority . $this->urlHelper->generate(
            'blog.post',
            ['id' => $post->id]
        );
    }

    private function generateMediaIDFromLogo(TwitterOAuth $twitter): string
    {
        $media = $twitter->upload('media/upload', ['media' => $this->logoPath]);
        return $media->media_id_string;
    }
}
