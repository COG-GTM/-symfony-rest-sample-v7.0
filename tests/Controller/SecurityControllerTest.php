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

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageDoesNotDiscloseCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form input[name="_password"]'));
        $this->assertCount(0, $crawler->filter('#login-help table'));

        $content = (string) $client->getResponse()->getContent();
        foreach (['kitten', $_SERVER['APP_FIXTURES_PASSWORD'], 'jane_admin', 'john_user', 'tom_admin'] as $secret) {
            $this->assertStringNotContainsString($secret, $content);
        }
    }

    public function testConfiguredFixturePasswordCanSignIn(): void
    {
        $client = static::createClient();
        $this->submitLoginForm($client, 'jane_admin', $_SERVER['APP_FIXTURES_PASSWORD']);

        $this->assertResponseRedirects();
        $this->assertStringNotContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    /**
     * @dataProvider getDemoAccounts
     */
    public function testDemoPasswordIsRejectedOnLoginForm(string $username): void
    {
        $client = static::createClient();
        $this->storeDemoPassword($username);

        $this->submitLoginForm($client, $username, 'kitten');

        $this->assertResponseRedirects('http://localhost/en/login', Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testDemoPasswordIsRejectedOnHttpBasic(): void
    {
        $client = static::createClient();
        $this->storeDemoPassword('jane_admin');

        $client->request('GET', '/en/admin/post/', [], [], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function getDemoAccounts(): \Generator
    {
        yield ['jane_admin'];
        yield ['john_user'];
    }

    private function submitLoginForm(KernelBrowser $client, string $username, string $password): void
    {
        $client->request('GET', '/en/login');
        $client->submitForm('Sign in', [
            '_username' => $username,
            '_password' => $password,
        ]);
    }

    /**
     * Simulates a database created with an older version of the fixtures.
     */
    private function storeDemoPassword(string $username): void
    {
        $container = static::getContainer();
        $user = $container->get(UserRepository::class)->findOneBy(['username' => $username]);
        $this->assertNotNull($user);

        $user->setPassword($container->get('test.user_password_hasher')->hashPassword($user, 'kitten'));
        $container->get(EntityManagerInterface::class)->flush();
    }
}
