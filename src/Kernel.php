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
        if ($this->booted) {
            parent::boot();

            return;
        }

        parent::boot();

        $secret = $this->getContainer()->getParameter('kernel.secret');
        $secret = \is_string($secret) ? $secret : '';

        if ('' === trim($secret)) {
            $this->shutdown();

            throw new \RuntimeException('APP_SECRET is empty. Define a unique, private value in a real environment variable, in ".env.local" or in the secrets vault (e.g. php -r "echo bin2hex(random_bytes(32));").');
        }

        if (\in_array(hash('sha256', $secret), self::COMPROMISED_SECRET_HASHES, true)) {
            $this->shutdown();

            throw new \RuntimeException('APP_SECRET is set to a value that was published in this repository. Generate a new unique, private value and rotate it on every deployment that used the old one.');
        }
    }
}
