<?php

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
     * SHA-256 hashes of APP_SECRET values that were published in this repository.
     */
    private const COMPROMISED_SECRET_HASHES = [
        '60ed83d1b67e5b3b153f3244d2c11aa1770739339775e53a2d661e9793a5da61',
    ];

    public function boot(): void
    {
        $wasBooted = $this->booted;

        parent::boot();

        if (!$wasBooted) {
            $this->ensureSecretIsSafe((string) $this->getContainer()->getParameter('kernel.secret'));
        }
    }

    private function ensureSecretIsSafe(string $secret): void
    {
        if ('' === preg_replace('/\s+/', '', $secret)) {
            throw new \RuntimeException('The APP_SECRET environment variable is empty or blank. Define a random, private value in a real environment variable, in ".env.local" or in the Symfony secrets vault (e.g. php -r "echo bin2hex(random_bytes(32)).PHP_EOL;").');
        }

        if (\in_array(hash('sha256', $secret), self::COMPROMISED_SECRET_HASHES, true)) {
            throw new \RuntimeException('The APP_SECRET value was published in this repository and must not be used. Generate a new random, private value and rotate it on every deployment that used the old one.');
        }
    }
}
