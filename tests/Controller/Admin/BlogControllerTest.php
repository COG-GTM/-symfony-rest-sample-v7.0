<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the BlogController used
 * for managing the blog in the backend.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * https://symfony.com/doc/current/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    /**
     * @dataProvider getUrlsForRegularUsers
     */
    public function testAccessDeniedForRegularUsers(string $httpMethod, string $url): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);

        $client->request($httpMethod, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function getUrlsForRegularUsers(): ?\Generator
    {
        yield ['GET', '/en/admin/post/'];
        yield ['GET', '/en/admin/post/1'];
        yield ['GET', '/en/admin/post/1/edit'];
        yield ['POST', '/en/admin/post/1/delete'];
    }

    public function testAdminBackendHomePage(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/admin/post/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            'body#admin_post_index #main tbody tr',
            'The backend homepage displays all the available posts.'
        );
    }

    /**
     * This test changes the database contents by creating a new blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminNewPost(): void
    {
        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/admin/post/new');
        $client->submitForm('Create post', [
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);

        $this->assertResponseRedirects('/en/admin/post/', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */
        $post = static::getContainer()->get(PostRepository::class)->findOneByTitle($postTitle);
        $this->assertNotNull($post);
        $this->assertSame($postSummary, $post->getSummary());
        $this->assertSame($postContent, $post->getContent());
    }

    public function testAdminNewDuplicatedPost(): void
    {
        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/post/new');
        $form = $crawler->selectButton('Create post')->form([
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);
        $client->submit($form);

        // post titles must be unique, so trying to create the same post twice should result in an error
        $client->submit($form);

        $this->assertSelectorTextSame('form .form-group.has-error label', 'Title');
        $this->assertSelectorTextContains('form .form-group.has-error .help-block', 'This title was already used in another blog post, but they must be unique.');
    }

    /**
     * A unique violation that is not about the slug (here, a tag created by a
     * concurrent request) must not be reported as a duplicated title.
     */
    public function testAdminNewPostWithConcurrentlyCreatedTagIsNotReportedAsDuplicatedTitle(): void
    {
        $tagName = 'tag-'.mt_rand();

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->disableReboot();
        $crawler = $client->request('GET', '/en/admin/post/new');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->getEventManager()->addEventListener([Events::onFlush], new class($tagName) {
            public function __construct(private string $tagName)
            {
            }

            public function onFlush(OnFlushEventArgs $args): void
            {
                $args->getEntityManager()->getConnection()->insert('symfony_demo_tag', ['name' => $this->tagName]);
            }
        });

        $client->submit($crawler->selectButton('Create post')->form([
            'post[title]' => 'Blog Post Title '.mt_rand(),
            'post[summary]' => $this->generateRandomString(255),
            'post[content]' => $this->generateRandomString(1024),
            'post[tags]' => $tagName,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertSelectorNotExists('form .form-group.has-error');
    }

    /**
     * Simulates two admins saving posts with the same title at the same time:
     * both pass the UniqueEntity validation and the other post is written right
     * before this one is flushed. The database must reject the duplicated slug
     * and the form must show the validation error instead of a server error.
     */
    public function testAdminNewPostWithConcurrentlyDuplicatedSlug(): void
    {
        $postTitle = 'Blog Post Title '.mt_rand();

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->disableReboot();
        $crawler = $client->request('GET', '/en/admin/post/new');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->getEventManager()->addEventListener([Events::onFlush], new class() {
            public function onFlush(OnFlushEventArgs $args): void
            {
                $entityManager = $args->getEntityManager();
                foreach ($entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
                    if ($entity instanceof Post) {
                        $entityManager->getConnection()->insert('symfony_demo_post', [
                            'author_id' => 2,
                            'title' => (string) $entity->getTitle(),
                            'slug' => (string) $entity->getSlug(),
                            'summary' => 'Concurrent post',
                            'content' => 'Concurrent post content',
                            'published_at' => '2021-01-01 00:00:00',
                        ]);
                    }
                }
            }
        });

        $client->submit($crawler->selectButton('Create post')->form([
            'post[title]' => $postTitle,
            'post[summary]' => $this->generateRandomString(255),
            'post[content]' => $this->generateRandomString(1024),
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('form .form-group.has-error label', 'Title');
        $this->assertSelectorTextContains('form .form-group.has-error .help-block', 'This title was already used in another blog post, but they must be unique.');

        /** @var Connection $connection */
        $connection = static::getContainer()->get('doctrine.dbal.default_connection');
        $this->assertSame(
            ['Concurrent post'],
            $connection->fetchFirstColumn('SELECT summary FROM symfony_demo_post WHERE title = ?', [$postTitle])
        );
    }

    public function testAdminShowPost(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/admin/post/1');

        $this->assertResponseIsSuccessful();
    }

    /**
     * This test changes the database contents by editing a blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminEditPost(): void
    {
        $newBlogPostTitle = 'Blog Post Title '.mt_rand();

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/admin/post/1/edit');
        $client->submitForm('Save changes', [
            'post[title]' => $newBlogPostTitle,
        ]);

        $this->assertResponseRedirects('/en/admin/post/1/edit', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */
        $post = static::getContainer()->get(PostRepository::class)->find(1);
        $this->assertSame($newBlogPostTitle, $post->getTitle());
    }

    /**
     * This test changes the database contents by deleting a blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminDeletePost(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/post/1');
        $client->submit($crawler->filter('#delete-form')->form());

        $this->assertResponseRedirects('/en/admin/post/', Response::HTTP_FOUND);

        $post = static::getContainer()->get(PostRepository::class)->find(1);
        $this->assertNull($post);
    }

    private function generateRandomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return mb_substr(str_shuffle(str_repeat($chars, ceil($length / mb_strlen($chars)))), 1, $length);
    }
}
