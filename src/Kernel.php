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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private bool $secretChecked = false;

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        if (!$this->secretChecked) {
            $this->boot();

            // Refuse to serve HTTP requests with an empty kernel.secret instead of
            // silently signing URLs and tokens with a predictable key.
            if ('' === trim((string) $this->getContainer()->getParameter('kernel.secret'))) {
                throw new \LogicException('APP_SECRET is not set. Define a strong random value in the real environment, .env.local or the secrets vault.');
            }

            $this->secretChecked = true;
        }

        return parent::handle($request, $type, $catch);
    }
}
