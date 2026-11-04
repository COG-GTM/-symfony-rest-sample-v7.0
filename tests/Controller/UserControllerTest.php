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

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the UserController used
 * for managing the current logged user.
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
class UserControllerTest extends WebTestCase
{
    /**
     * @dataProvider getUrlsForAnonymousUsers
     */
    public function testAccessDeniedForAnonymousUsers(string $httpMethod, string $url): void
    {
        $client = static::createClient();
        $client->request($httpMethod, $url);

        $this->assertResponseRedirects(
            'http://localhost/en/login',
            Response::HTTP_FOUND,
            sprintf('The %s secure URL redirects to the login form.', $url)
        );
    }

    public function getUrlsForAnonymousUsers(): ?\Generator
    {
        yield ['GET', '/en/profile/edit'];
        yield ['GET', '/en/profile/change-password'];
    }

    public function testEditUser(): void
    {
        $newUserEmail = 'admin_jane@symfony.com';

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/profile/edit');
        $client->submitForm('Save changes', [
            'user[email]' => $newUserEmail,
        ]);

        $this->assertResponseRedirects('/en/profile/edit', Response::HTTP_FOUND);

        /** @var User $user */
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail($newUserEmail);

        $this->assertNotNull($user);
        $this->assertSame($newUserEmail, $user->getEmail());
    }

    public function testChangePassword(): void
    {
        $newUserPassword = 'new-password';

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/profile/change-password');
        $client->submitForm('Save changes', [
            'change_password[currentPassword]' => 'kitten',
            'change_password[newPassword][first]' => $newUserPassword,
            'change_password[newPassword][second]' => $newUserPassword,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND, 'Changing password logout the user.');
        $this->assertStringStartsWith(
            '/en/logout?_csrf_token=',
            (string) $client->getResponse()->headers->get('Location'),
            'Changing password redirects to the CSRF-protected logout URL.'
        );
    }

    public function testLogoutWithoutCsrfTokenKeepsUserLoggedIn(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('jane_admin'));

        $client->request('GET', '/en/logout');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/en/profile/edit');
        $this->assertResponseIsSuccessful('The user is still logged in after a logout request without CSRF token.');
    }

    public function testLogoutWithInvalidCsrfTokenKeepsUserLoggedIn(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('jane_admin'));

        $client->request('GET', '/en/logout?_csrf_token=invalid');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/en/profile/edit');
        $this->assertResponseIsSuccessful('The user is still logged in after a logout request with an invalid CSRF token.');
    }

    public function testLogoutLinkWithCsrfTokenLogsUserOut(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('jane_admin'));

        $crawler = $client->request('GET', '/en/profile/edit');
        $logoutUrl = (string) $crawler->filter('a[href*="/logout"]')->attr('href');
        $this->assertStringContainsString('_csrf_token=', $logoutUrl, 'The logout link carries a CSRF token.');

        $client->request('GET', $logoutUrl);
        $this->assertResponseRedirects();

        $client->request('GET', '/en/profile/edit');
        $this->assertResponseRedirects('http://localhost/en/login', Response::HTTP_FOUND, 'The user is logged out.');
    }

    private function getUser(string $username): User
    {
        /** @var UserRepository $repository */
        $repository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $repository->findOneBy(['username' => $username]);

        return $user;
    }
}
