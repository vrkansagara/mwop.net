<?php

declare(strict_types=1);

namespace Mwop\Blog\Console;

use Mwop\Blog\CreateBlogPostFromDataArrayTrait;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Parser as YamlParser;

use function file_exists;
use function file_get_contents;
use function getcwd;
use function implode;
use function ltrim;
use function realpath;
use function sprintf;
use function strlen;
use function substr;
use function unlink;

class SeedBlogDatabase extends Command
{
    use CreateBlogPostFromDataArrayTrait;

    /** @psalm-var array<string, array<string, string>> */
    private array $authors = [];

    /** @var string[] */
    private array $indices = [
        'CREATE INDEX visible ON posts ( created, draft, public )',
        'CREATE INDEX visible_tags ON posts ( tags, created, draft, public )',
        'CREATE INDEX visible_author ON posts ( author, created, draft, public )',
    ];

    private string $initial = <<<'EOS'
        INSERT INTO posts
        SELECT
            %s AS id,
            %s AS path,
            %d AS created,
            %d AS updated,
            %s AS title,
            %s AS author,
            %d AS draft,
            %d AS public,
            %s AS body,
            %s AS tags
        EOS;

    private string $item = <<<'EOS'
        UNION SELECT
            %s,
            %s,
            %d,
            %d,
            %s,
            %s,
            %d,
            %d,
            %s,
            %s
        EOS;

    private string $searchTable = <<<'EOS'
        CREATE VIRTUAL TABLE search USING FTS4(
            id,
            created,
            title,
            body,
            tags
        )
        EOS;

    private string $searchTrigger = <<<'EOS'
        CREATE TRIGGER after_posts_insert
            AFTER INSERT ON posts
            BEGIN
                INSERT INTO search (
                    id,
                    created,
                    title,
                    body,
                    tags
                )
                VALUES (
                    new.id,
                    new.created,
                    new.title,
                    new.body,
                    new.tags
                );
            END
        EOS;

    private string $table = <<<'EOS'
        CREATE TABLE "posts" (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            path VARCHAR(255) NOT NULL,
            created UNSIGNED INTEGER NOT NULL,
            updated UNSIGNED INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            draft INT(1) NOT NULL,
            public INT(1) NOT NULL,
            body TEXT NOT NULL,
            tags VARCHAR(255)
        )
        EOS;

    protected function configure(): void
    {
        $this->setName('blog:seed-db');
        $this->setDescription('Generate and seed the blog post database.');
        $this->setHelp('Re-create the blog post database from the post entities.');

        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'Base path of the application; defaults to current working directory',
            realpath(getcwd())
        );

        $this->addOption(
            'db-path',
            'd',
            InputOption::VALUE_REQUIRED,
            'Path to the database file, relative to the --path.',
            'data/shared/posts.db'
        );

        $this->addOption(
            'posts-path',
            'e',
            InputOption::VALUE_REQUIRED,
            'Path to the blog posts, relative to the --path.',
            'data/blog'
        );

        $this->addOption(
            'authors-path',
            'a',
            InputOption::VALUE_REQUIRED,
            'Path to the author metadata files, relative to the --path.',
            'data/blog/authors'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io          = new SymfonyStyle($input, $output);
        $basePath    = $input->getOption('path');
        $postsPath   = $input->getOption('posts-path');
        $authorsPath = $input->getOption('authors-path');
        $dbPath      = $input->getOption('db-path');

        $io->title('Generating blog post database');

        $pdo = $this->createDatabase($dbPath);

        $path = sprintf('%s/%s', realpath($basePath), ltrim($postsPath));
        $trim = strlen(realpath($basePath)) + 1;

        $statements = [];
        foreach (new MarkdownFileFilter($path) as $fileInfo) {
            $path     = $fileInfo->getPathname();
            $post     = $this->createBlogPostFromDataArray(['path' => $path]);
            $author   = $this->getAuthor($post->author, $authorsPath);
            $template = empty($statements) ? $this->initial : $this->item;

            $statements[] = sprintf(
                $template,
                $pdo->quote($post->id),
                $pdo->quote(substr($path, $trim)),
                $post->created->getTimestamp(),
                $post->updated->getTimestamp(),
                $pdo->quote($post->title),
                $pdo->quote($author['id']),
                $post->isDraft ? 1 : 0,
                $post->isPublic ? 1 : 0,
                $pdo->quote($post->body),
                $pdo->quote(sprintf('|%s|', implode('|', $post->tags)))
            );
        }

        $pdo->exec(implode("\n", $statements));

        $io->success('Created blog database');

        return 0;
    }

    private function createDatabase(string $path): PDO
    {
        if (file_exists($path)) {
            $path = realpath($path);
            unlink($path);
        }

        if ($path[0] !== '/') {
            $path = realpath(getcwd()) . '/' . $path;
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        $pdo->exec($this->table);
        foreach ($this->indices as $index) {
            $pdo->exec($index);
        }
        $pdo->exec($this->searchTable);
        $pdo->exec($this->searchTrigger);
        $pdo->commit();

        return $pdo;
    }

    /**
     * Retrieve author metadata.
     *
     * @return string[]
     */
    private function getAuthor(string $author, string $authorsPath): array
    {
        if (isset($this->authors[$author])) {
            return $this->authors[$author];
        }

        $path = sprintf('%s/%s.yml', $authorsPath, $author);
        if (! file_exists($path)) {
            $this->authors[$author] = ['id' => $author, 'name' => $author, 'email' => '', 'uri' => ''];
            return $this->authors[$author];
        }

        $this->authors[$author] = (new YamlParser())->parse(file_get_contents($path));
        return $this->authors[$author];
    }
}
