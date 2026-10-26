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

use App\Command\CheckDefaultCredentialsCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;

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
        $user = $container->get(UserRepository::class)->findOneBy(['username' => 'tom_admin']);
        $user->setPassword($container->get('test.user_password_hasher')->hashPassword($user, 'kitten'));
        $container->get(EntityManagerInterface::class)->flush();

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
