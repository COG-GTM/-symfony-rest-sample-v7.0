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
use App\Entity\User;
use App\Pagination\Paginator;
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

    public function testUnpublishedPostIsNotShown(): void
    {
        $client = static::createClient();
        $post = $this->createScheduledPost($client);

        $client->request('GET', '/en/blog/posts/'.$post->getSlug());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnpublishedPostIsNotReturnedBySearch(): void
    {
        $client = static::createClient();
        $this->createScheduledPost($client);

        $client->xmlHttpRequest('GET', '/en/blog/search', ['q' => 'Scheduled announcement']);

        $results = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame([], $results);
    }

    public function testUnpublishedPostCannotBeCommented(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $post = $this->createScheduledPost($client);

        $client->request('POST', '/en/blog/comment/'.$post->getSlug().'/new', [
            'comment' => ['content' => 'Hi, Symfony!'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    private function createScheduledPost(KernelBrowser $client): Post
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);

        /** @var User $author */
        $author = $entityManager->getRepository(User::class)->findOneBy(['username' => 'jane_admin']);

        $post = new Post();
        $post->setTitle('Scheduled announcement');
        $post->setSlug('scheduled-announcement');
        $post->setSummary('Not public yet');
        $post->setContent('Secret content of a post scheduled for future publication.');
        $post->setPublishedAt(new \DateTime('+1 week'));
        $post->setAuthor($author);

        $entityManager->persist($post);
        $entityManager->flush();

        return $post;
    }
}
