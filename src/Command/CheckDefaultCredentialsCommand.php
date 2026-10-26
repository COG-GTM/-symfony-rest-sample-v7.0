<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\UserRepository;
use App\Security\DefaultCredentials;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * A deploy check that fails when any user still has a well-known demo password:
 *
 *     $ php bin/console app:check-default-credentials
 */
class CheckDefaultCredentialsCommand extends Command
{
    protected static $defaultName = 'app:check-default-credentials';

    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Fails if any user still has a well-known demo password (run it before deploying)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $offenders = [];
        foreach ($this->users->findAll() as $user) {
            foreach (DefaultCredentials::PASSWORDS as $password) {
                if ($this->passwordHasher->isPasswordValid($user, $password)) {
                    $offenders[] = $user->getUserIdentifier();
                    break;
                }
            }
        }

        if ([] !== $offenders) {
            $io->error(sprintf('These users have a well-known demo password and must change it or be removed: %s', implode(', ', $offenders)));

            return Command::FAILURE;
        }

        $io->success('No user has a well-known demo password.');

        return Command::SUCCESS;
    }
}
