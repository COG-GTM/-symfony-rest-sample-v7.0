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
     * @dataProvider getRssLinkInjectionUrls
     */
    public function testRssLinkIgnoresRouteParametersFromQueryString(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.section.rss a[href="/en/blog/rss.xml"]');
    }

    public function getRssLinkInjectionUrls(): \Generator
    {
        yield 'post page, protocol-relative _locale' => ['/en/blog/posts/lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit?_locale=//evil.example'];
        yield 'index page, protocol-relative _locale' => ['/en/blog/?_locale=//evil.example'];
        yield 'index page, other route parameters' => ['/en/blog/?_format=json&page=99'];
    }

    public function testRssLinkKeepsTagFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/blog/?tag=lorem&_locale=//evil.example');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.section.rss a[href="/en/blog/rss.xml?tag=lorem"]');
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
}
