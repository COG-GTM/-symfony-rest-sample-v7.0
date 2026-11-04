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

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console app:add-user
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console app:add-user -vv
 *
 * The password is never accepted as a command-line argument (it would leak
 * through the shell history and the process list). It is asked interactively
 * or read from the standard input with the --password-stdin option.
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class AddUserCommand extends Command
{
    // to make your command lazily loaded, configure the $defaultName static property,
    // so it will be instantiated only when the command is actually called.
    protected static $defaultName = 'app:add-user';

    private SymfonyStyle $io;

    private ?string $plainPassword = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private Validator $validator,
        private UserRepository $users
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Creates users and stores them in the database')
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addArgument('full-name', InputArgument::OPTIONAL, 'The full name of the new user')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator')
            ->addOption('password-stdin', null, InputOption::VALUE_NONE, 'Read the password of the new user from the standard input')
        ;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
        $this->plainPassword = null;

        if ($input->getOption('password-stdin')) {
            $this->plainPassword = $this->readPasswordFromStdin($input);
        }
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('username') && null !== $this->plainPassword && null !== $input->getArgument('email') && null !== $input->getArgument('full-name')) {
            return;
        }

        // fail before echoing anything: with the old "username password email"
        // syntax this argument would contain the plain password
        $email = $input->getArgument('email');
        if (\is_string($email)) {
            $this->validator->validateEmail($email);
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user username email@example.com "Full Name" --password-stdin',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the username if it's not defined
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username</info>: '.$username);
        } else {
            $username = $this->io->ask('Username', null, [$this->validator, 'validateUsername']);
            $input->setArgument('username', $username);
        }

        // Ask for the password if it wasn't read from the standard input
        if (null !== $this->plainPassword) {
            $this->io->text(' > <info>Password</info>: (read from the standard input)');
        } else {
            $password = $this->io->askHidden('Password (your type will be hidden)', [$this->validator, 'validatePassword']);
            \assert(\is_string($password));
            $this->plainPassword = $password;
        }

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, [$this->validator, 'validateEmail']);
            $input->setArgument('email', $email);
        }

        // Ask for the full name if it's not defined
        $fullName = $input->getArgument('full-name');
        if (null !== $fullName) {
            $this->io->text(' > <info>Full Name</info>: '.$fullName);
        } else {
            $fullName = $this->io->ask('Full Name', null, [$this->validator, 'validateFullName']);
            $input->setArgument('full-name', $fullName);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        $username = $input->getArgument('username');
        $plainPassword = $this->plainPassword;
        $email = $input->getArgument('email');
        $fullName = $input->getArgument('full-name');
        $isAdmin = $input->getOption('admin');

        if (null === $plainPassword) {
            throw new RuntimeException('The password is required. For security reasons it is not accepted as a command-line argument: type it when asked or pipe it with the --password-stdin option.');
        }

        // make sure to validate the user data is correct
        $this->validateUserData($username, $plainPassword, $email, $fullName);

        // create the user and hash its password
        $user = new User();
        $user->setFullName($fullName);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles([$isAdmin ? 'ROLE_ADMIN' : 'ROLE_USER']);

        // See https://symfony.com/doc/5.4/security.html#registering-the-user-hashing-passwords
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s (%s)', $isAdmin ? 'Administrator user' : 'User', $user->getUsername(), $user->getEmail()));

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    /**
     * Reads the first line of the standard input (without its line ending), so
     * the password never appears in the command line, the shell history or the
     * process list.
     */
    private function readPasswordFromStdin(InputInterface $input): string
    {
        $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
        $line = fgets($stream ?? \STDIN);

        if (false === $line) {
            throw new RuntimeException('The --password-stdin option was used but no password was received on the standard input.');
        }

        return u($line)->trimEnd("\r\n")->toString();
    }

    private function validateUserData($username, $plainPassword, $email, $fullName): void
    {
        // first check if a user with the same username already exists.
        $existingUser = $this->users->findOneBy(['username' => $username]);

        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" username.', $username));
        }

        // validate password and email if is not this input means interactive.
        $this->validator->validatePassword($plainPassword);
        $this->validator->validateEmail($email);
        $this->validator->validateFullName($fullName);

        // check if a user with the same email already exists.
        $existingEmail = $this->users->findOneBy(['email' => $email]);

        if (null !== $existingEmail) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%</info> command creates new users and saves them in the database:

  <info>php %command.full_name%</info> <comment>username email full-name</comment>

For security reasons the password is never accepted as a command-line argument
(it would be stored in the shell history and visible in the process list). The
command asks for it with hidden input or, in scripts, reads it from the standard
input when the <comment>--password-stdin</comment> option is set:

  <info>printf '%s\n' "$PASSWORD" | php %command.full_name%</info> username email full-name <comment>--password-stdin</comment>

By default the command creates regular users. To create administrator users,
add the <comment>--admin</comment> option:

  <info>php %command.full_name%</info> username email full-name <comment>--admin</comment>

If you omit any of the required arguments, the command will ask you to
provide the missing values:

  # command will ask you for the password and the full name
  <info>php %command.full_name%</info> <comment>username email</comment>

  # command will ask you for the password, email and full name
  <info>php %command.full_name%</info> <comment>username</comment>

  # command will ask you for all arguments
  <info>php %command.full_name%</info>

HELP;
    }
}
