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

namespace App\Tests\Command;

use App\Command\CheckDefaultCredentialsCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CheckDefaultCredentialsCommandTest extends AbstractCommandTest
{
    public function testSucceedsWhenNoUserHasADemoPassword(): void
    {
        $tester = $this->executeCommand([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testFailsWhenAUserStillHasADemoPassword(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $users = $container->get(UserRepository::class);
        $this->assertInstanceOf(UserRepository::class, $users);
        $user = $users->findOneBy(['username' => 'tom_admin']);
        $this->assertInstanceOf(User::class, $user);

        $hasher = $container->get('test.user_password_hasher');
        $this->assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hashPassword($user, 'kitten'));

        $entityManager = $container->get(EntityManagerInterface::class);
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->flush();

        $tester = $this->executeCommand([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('tom_admin', $tester->getDisplay());
        $this->assertStringNotContainsString('jane_admin', $tester->getDisplay());
    }

    protected function getCommandFqcn(): string
    {
        return CheckDefaultCredentialsCommand::class;
    }
}
