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

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Environments whose APP_SECRET is allowed to come from committed .env files.
     */
    public const ENVIRONMENTS_WITH_COMMITTED_SECRET = ['dev', 'test'];

    /**
     * APP_SECRET values that are public (committed to this repository or to
     * the upstream Symfony Demo / recipes) and must never sign anything real.
     */
    public const PUBLIC_SECRETS = [
        '2ca64f8d83b9e89f5f19d672841d6bb8',
        '39251f2475a2a1b8a4397e0de1423274',
        '$ecretf0rt3st',
        'ThisTokenIsNotSoSecretChangeIt',
    ];

    public function boot(): void
    {
        parent::boot();

        if (!\in_array($this->environment, self::ENVIRONMENTS_WITH_COMMITTED_SECRET, true)) {
            self::assertSecretIsSafe((string) $this->container->getParameter('kernel.secret'), $this->environment);
        }
    }

    public static function assertSecretIsSafe(string $secret, string $environment): void
    {
        if ('' === trim($secret) || \in_array($secret, self::PUBLIC_SECRETS, true)) {
            throw new \RuntimeException(\sprintf('APP_SECRET is empty or publicly known. Set a unique random value for the "%s" environment as a real environment variable, in .env.local or in the secrets vault.', $environment));
        }
    }
}
