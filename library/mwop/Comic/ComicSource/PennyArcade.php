<?php

namespace mwop\Comic\ComicSource;

use mwop\Comic\Comic,
    Zend\Dom\Query as DomQuery;

class PennyArcade extends AbstractComicSource
{
    protected static $comics = array(
        'pennyarcade' => 'Penny Arcade',
    );

    protected $comicFormat = 'http://penny-arcade.com/comic';

    protected $dailyFormat = 'http://penny-arcade.com/comic/%s';

    public function fetch()
    {
        $url  = sprintf($this->dailyFormat, date('Y/m/d'));
        $page = file_get_contents($url);
        if (!$page) {
            $this->registerError(sprintf(
                'Comic at "%s" is unreachable',
                $url
            ));
            return false;
        }

        $dom  = new DomQuery($page);
        $r    = $dom->execute('div.post.comic img');
        if (!$r->count()) {
            $this->registerError(sprintf(
                'Comic at "%s" is unreachable',
                $url
            ));
            return false;
        }

        $imgUrl = false;
        foreach ($r as $node) {
            if ($node->hasAttribute('src')) {
                $imgUrl = $node->getAttribute('src');
            }
        }

        if (!$imgUrl) {
            $this->registerError(sprintf(
                'Unable to find image source in "%s"',
                $url
            ));
            return false;
        }

        $comic = new Comic(
            /* 'name'  => */ static::$comics['pennyarcade'],
            /* 'link'  => */ $this->comicFormat,
            /* 'daily' => */ $url,
            /* 'image' => */ $imgUrl
        );

        return $comic;
    }
}
