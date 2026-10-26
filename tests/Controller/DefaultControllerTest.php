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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * Functional test that implements a "smoke test" of all the public and secure
 * URLs of the application.
 * See https://symfony.com/doc/current/best_practices.html#smoke-test-your-urls.
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class DefaultControllerTest extends WebTestCase
{
    /**
     * PHPUnit's data providers allow to execute the same tests repeated times
     * using a different set of data each time.
     * See https://symfony.com/doc/current/testing.html#testing-against-different-sets-of-data.
     *
     * @dataProvider getPublicUrls
     */
    public function testPublicUrls(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful(sprintf('The %s public URL loads correctly.', $url));
    }

    /**
     * A good practice for tests is to not use the service container, to make
     * them more robust. However, in this example we must access to the container
     * to get the entity manager and make a database query. The reason is that
     * blog post fixtures are randomly generated and there's no guarantee that
     * a given blog post slug will be available.
     */
    public function testPublicBlogPost(): void
    {
        $client = static::createClient();
        // the service container is always available via the test client
        $blogPost = $client->getContainer()->get('doctrine')->getRepository(Post::class)->find(1);
        $client->request('GET', sprintf('/en/blog/posts/%s', $blogPost->getSlug()));

        $this->assertResponseIsSuccessful();
    }

    /**
     * The application contains a lot of secure URLs which shouldn't be
     * publicly accessible. This tests ensures that whenever a user tries to
     * access one of those pages, a redirection to the login form is performed.
     *
     * @dataProvider getSecureUrls
     */
    public function testSecureUrls(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects(
            'http://localhost/en/login',
            Response::HTTP_FOUND,
            sprintf('The %s secure URL redirects to the login form.', $url)
        );
    }

    /**
     * The /_fragment endpoint executes the controller named in its signed
     * "_path" query parameter, so it must not be reachable from remote clients
     * even when the request carries a valid signature.
     */
    public function testSignedFragmentIsNotPubliclyAccessible(): void
    {
        $client = static::createClient();
        $signer = static::getContainer()->get('uri_signer');
        $this->assertInstanceOf(UriSigner::class, $signer);
        $uri = $signer->sign('http://localhost/_fragment?_path='.rawurlencode('_controller=phpinfo'));

        $client->request('GET', $uri, [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertResponseRedirects('http://localhost/en/login');
        $this->assertStringNotContainsString('phpinfo()', (string) $client->getResponse()->getContent());
    }

    /**
     * Fragment URLs signed with the APP_SECRET that used to be committed in
     * .env must be rejected.
     */
    public function testFragmentSignedWithPreviouslyCommittedSecretIsRejected(): void
    {
        $client = static::createClient();
        $uri = (new UriSigner('2ca64f8d83b9e89f5f19d672841d6bb8'))->sign('http://localhost/_fragment?_path='.rawurlencode('_controller=phpinfo'));

        $client->request('GET', $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function getPublicUrls(): ?\Generator
    {
        yield ['/'];
        yield ['/en/blog/'];
        yield ['/en/login'];
    }

    public function getSecureUrls(): ?\Generator
    {
        yield ['/en/admin/post/'];
        yield ['/en/admin/post/new'];
        yield ['/en/admin/post/1'];
        yield ['/en/admin/post/1/edit'];
    }
}
