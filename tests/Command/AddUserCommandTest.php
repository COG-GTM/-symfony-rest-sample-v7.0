<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\AddUserCommand;
use App\Repository\UserRepository;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class AddUserCommandTest extends AbstractCommandTest
{
    private $userData = [
        'username' => 'chuck_norris',
        'password' => 'foobar',
        'email' => 'chuck@norris.com',
        'full-name' => 'Chuck Norris',
    ];

    protected function setUp(): void
    {
        if ('Windows' === \PHP_OS_FAMILY) {
            $this->markTestSkipped('`stty` is required to test this command.');
        }
    }

    /**
     * @dataProvider isAdminDataProvider
     *
     * This test provides all the arguments required by the command and pipes
     * the password through the standard input, so the command runs
     * non-interactively and it won't ask for any argument.
     */
    public function testCreateUserNonInteractive(bool $isAdmin): void
    {
        $input = $this->getArgumentsWithoutPassword();
        $input['--password-stdin'] = true;
        if ($isAdmin) {
            $input['--admin'] = 1;
        }
        $this->executeCommand($input, [$this->userData['password']], ['interactive' => false]);

        $this->assertUserCreated($isAdmin);
    }

    /**
     * The password must never be accepted as a command-line argument because
     * it would end up in the shell history and in the process list.
     */
    public function testPasswordArgumentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "password" argument does not exist.');

        $this->executeCommand($this->userData, [], ['interactive' => false]);
    }

    /**
     * The legacy "username password email full-name" command line must fail
     * instead of creating a user.
     */
    public function testLegacyPositionalPasswordIsRejected(): void
    {
        self::bootKernel();
        /** @var AddUserCommand $command */
        $command = static::getContainer()->get(AddUserCommand::class);

        $input = new ArgvInput(['bin/console', ...array_values($this->userData)]);
        $input->setInteractive(false);

        try {
            $command->run($input, new NullOutput());
            $this->fail('The command must reject a positional password.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Too many arguments', $e->getMessage());
        }

        $this->assertUserNotCreated();
    }

    /**
     * With the old "username password" syntax the password lands in the email
     * argument: the command must fail without echoing it.
     */
    public function testLegacyPasswordInEmailArgumentIsNotEchoed(): void
    {
        try {
            $this->executeCommand(['username' => $this->userData['username'], 'email' => $this->userData['password']]);
            $this->fail('The command must reject an invalid email argument.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('The email should look like a real email.', $e->getMessage());
        }

        $this->assertUserNotCreated();
    }

    public function testNonInteractiveWithoutPasswordFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('--password-stdin');

        $this->executeCommand($this->getArgumentsWithoutPassword(), [], ['interactive' => false]);
    }

    public function testEmptyPasswordFromStdinIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The password can not be empty.');

        $this->executeCommand($this->getArgumentsWithoutPassword() + ['--password-stdin' => true], [''], ['interactive' => false]);
    }

    /**
     * @dataProvider isAdminDataProvider
     *
     * This test doesn't provide all the arguments required by the command, so
     * the command runs interactively and it will ask for the value of the missing
     * arguments.
     * See https://symfony.com/doc/current/components/console/helpers/questionhelper.html#testing-a-command-that-expects-input
     */
    public function testCreateUserInteractive(bool $isAdmin): void
    {
        $this->executeCommand(
        // these are the arguments (only 1 is passed, the rest are missing)
            $isAdmin ? ['--admin' => 1] : [],
            // these are the responses given to the questions asked by the command
            // to get the value of the missing required arguments
            array_values($this->userData)
        );

        $this->assertUserCreated($isAdmin);
    }

    /**
     * @dataProvider isAdminDataProvider
     *
     * The password is read from the standard input and the other missing
     * arguments are asked interactively.
     */
    public function testCreateUserInteractiveWithPasswordFromStdin(bool $isAdmin): void
    {
        $input = ['username' => $this->userData['username'], '--password-stdin' => true];
        if ($isAdmin) {
            $input['--admin'] = 1;
        }
        $this->executeCommand($input, [$this->userData['password'], $this->userData['email'], $this->userData['full-name']]);

        $this->assertUserCreated($isAdmin);
    }

    /**
     * This is used to execute the same test twice: first for normal users
     * (isAdmin = false) and then for admin users (isAdmin = true).
     */
    public function isAdminDataProvider(): ?\Generator
    {
        yield [false];
        yield [true];
    }

    /**
     * This helper method checks that the user was correctly created and saved
     * in the database.
     */
    private function assertUserCreated(bool $isAdmin): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->getContainer()->get(UserRepository::class)->findOneByEmail($this->userData['email']);
        $this->assertNotNull($user);

        $this->assertSame($this->userData['full-name'], $user->getFullName());
        $this->assertSame($this->userData['username'], $user->getUsername());
        $this->assertTrue($this->getContainer()->get('test.user_password_hasher')->isPasswordValid($user, $this->userData['password']));
        $this->assertSame($isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER'], $user->getRoles());
    }

    private function assertUserNotCreated(): void
    {
        $users = $this->getContainer()->get(UserRepository::class);
        \assert($users instanceof UserRepository);

        $this->assertNull($users->findOneBy(['email' => $this->userData['email']]));
        $this->assertNull($users->findOneBy(['username' => $this->userData['username']]));
    }

    /**
     * @return array<string, string>
     */
    private function getArgumentsWithoutPassword(): array
    {
        $arguments = $this->userData;
        unset($arguments['password']);

        return $arguments;
    }

    protected function getCommandFqcn(): string
    {
        return AddUserCommand::class;
    }
}
