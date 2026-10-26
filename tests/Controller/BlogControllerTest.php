<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Pagination\Paginator;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for the controllers defined inside BlogController.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/blog/');

        $this->assertResponseIsSuccessful();

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('article.post'),
            'The homepage displays the right number of posts.'
        );
    }

    public function testRss(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/blog/rss.xml');

        $this->assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('item'),
            'The xml file displays the right number of posts.'
        );
    }

    /**
     * This test changes the database contents by creating a new comment. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testNewComment(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->followRedirects();

        // Find first blog post
        $crawler = $client->request('GET', '/en/blog/');
        $postLink = $crawler->filter('article.post > h2 a')->link();

        $client->click($postLink);
        $crawler = $client->submitForm('Publish comment', [
            'comment[content]' => 'Hi, Symfony!',
        ]);

        $newComment = $crawler->filter('.post-comment')->first()->filter('div > p')->text();

        $this->assertSame('Hi, Symfony!', $newComment);
    }

    public function testAjaxSearch(): void
    {
        $client = static::createClient();
        $client->xmlHttpRequest('GET', '/en/blog/search', ['q' => 'lorem']);

        $results = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertCount(1, $results);
        $this->assertSame('Lorem ipsum dolor sit amet consectetur adipiscing elit', $results[0]['title']);
        $this->assertSame('Jane Doe', $results[0]['author']);
    }

    public function testAjaxSearchMatchesAnyTerm(): void
    {
        $client = static::createClient();
        $client->xmlHttpRequest('GET', '/en/blog/search', ['q' => 'lorem pellentesque']);

        $results = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $results);
    }

    public function testAjaxSearchHidesScheduledPosts(): void
    {
        $client = static::createClient();
        $this->scheduleLoremPost();

        $client->xmlHttpRequest('GET', '/en/blog/search', ['q' => 'lorem']);

        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testScheduledPostIsNotFoundForAnonymousUsers(): void
    {
        $client = static::createClient();
        $slug = $this->scheduleLoremPost();

        $client->request('GET', '/en/blog/posts/'.$slug);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testScheduledPostIsNotFoundForOtherUsers(): void
    {
        $client = $this->createAuthenticatedClient('john_user');
        $slug = $this->scheduleLoremPost();

        $client->request('GET', '/en/blog/posts/'.$slug);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testScheduledPostIsVisibleToItsAuthor(): void
    {
        $client = $this->createAuthenticatedClient('jane_admin');
        $slug = $this->scheduleLoremPost();

        $client->request('GET', '/en/blog/posts/'.$slug);

        $this->assertResponseIsSuccessful();
    }

    public function testCommentOnScheduledPostIsNotFound(): void
    {
        $client = $this->createAuthenticatedClient('john_user');
        $slug = $this->scheduleLoremPost();

        $client->request('POST', '/en/blog/comment/'.$slug.'/new', [
            'comment' => ['content' => 'Hi, Symfony!'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    private function createAuthenticatedClient(string $username): KernelBrowser
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => 'kitten',
        ]);
    }

    /**
     * Moves the post authored by "jane_admin" one week into the future and returns its slug.
     */
    private function scheduleLoremPost(): string
    {
        /** @var PostRepository $postRepository */
        $postRepository = static::getContainer()->get(PostRepository::class);
        /** @var Post $post */
        $post = $postRepository->findOneBy(['title' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit']);
        $post->setPublishedAt(new \DateTime('+1 week'));

        static::getContainer()->get(EntityManagerInterface::class)->flush();

        return $post->getSlug();
    }
}
