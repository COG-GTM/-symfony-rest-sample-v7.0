<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for the login form and its post-login redirect.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 */
class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageKeepsLocalRedirectTo(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/login?redirect_to=/en/blog/');

        $this->assertResponseIsSuccessful();
        $this->assertSame('/en/blog/', $crawler->filter('input[name="_target_path"]')->attr('value'));
    }

    /**
     * @dataProvider getExternalTargets
     */
    public function testLoginPageDropsExternalRedirectTo(string $target): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/login?'.http_build_query(['redirect_to' => $target]));

        $this->assertResponseIsSuccessful();
        $this->assertSame('', $crawler->filter('input[name="_target_path"]')->attr('value'));
    }

    /**
     * @dataProvider getExternalTargets
     */
    public function testLoginIgnoresExternalTargetPath(string $target): void
    {
        $client = static::createClient();
        $this->submitLoginForm($client, $target);

        $this->assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('http://localhost/', $location);
        $this->assertSame('localhost', parse_url($location, \PHP_URL_HOST));
        $this->assertStringNotContainsString('evil.example', $location);
    }

    public function testLoginRedirectsToLocalTargetPath(): void
    {
        $client = static::createClient();
        $this->submitLoginForm($client, '/en/blog/');

        $this->assertResponseRedirects('http://localhost/en/blog/');
    }

    public function getExternalTargets(): \Generator
    {
        yield ['http:evil.example'];
        yield ['http:/evil.example'];
        yield ['https:evil.example'];
        yield ['https:/evil.example'];
        yield ['https://evil.example'];
        yield ['//evil.example'];
        yield ['/\\evil.example'];
    }

    private function submitLoginForm(KernelBrowser $client, string $targetPath): void
    {
        $crawler = $client->request('GET', '/en/login');
        $form = $crawler->filter('form[action="/en/login"]')->form([
            '_username' => 'jane_admin',
            '_password' => 'kitten',
        ]);
        $form['_target_path']->setValue($targetPath);

        $client->submit($form);
    }
}
