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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractCommandTest extends KernelTestCase
{
    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a command.
     *
     * @param array $arguments All the arguments passed when executing the command
     * @param array $inputs    The (optional) answers given to the command when it asks for the value of the missing arguments
     * @param array<string, mixed> $options The (optional) CommandTester options (e.g. ['interactive' => false])
     */
    protected function executeCommand(array $arguments, array $inputs = [], array $options = []): CommandTester
    {
        self::bootKernel();

        // this uses a special testing container that allows you to fetch private services
        $command = static::getContainer()->get($this->getCommandFqcn());
        $command->setApplication(new Application(self::$kernel));

        $commandTester = new CommandTester($command);
        $commandTester->setInputs($inputs);
        $commandTester->execute($arguments, $options);

        return $commandTester;
    }

    abstract protected function getCommandFqcn(): string;
}
