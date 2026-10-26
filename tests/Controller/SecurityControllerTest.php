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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageDoesNotDisclosePasswords(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form input[name="_password"]'));
        $this->assertCount(0, $crawler->filter('#login-help table'));

        $content = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('kitten', $content);
        $this->assertStringNotContainsString($_SERVER['APP_FIXTURES_PASSWORD'], $content);
        $this->assertStringNotContainsString('jane_admin', $content);
        $this->assertStringNotContainsString('john_user', $content);
    }

    public function testFormerDemoPasswordIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/login');
        $client->submitForm('Sign in', [
            '_username' => 'jane_admin',
            '_password' => 'kitten',
        ]);

        $this->assertResponseRedirects('/en/login', Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }
}
