<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Refuses to handle requests when kernel.secret is empty or a publicly known
 * value, because anyone knowing it can sign /_fragment URLs and other signed
 * data (remember-me cookies, login links, etc.).
 *
 * Runs before the FragmentListener (priority 48) and the router.
 */
class AppSecretGuardSubscriber implements EventSubscriberInterface
{
    private const PUBLICLY_KNOWN_SECRETS = [
        // previously committed in .env (also published by the upstream symfony/demo project)
        '2ca64f8d83b9e89f5f19d672841d6bb8',
    ];

    public function __construct(
        private string $kernelSecret
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ('' === trim($this->kernelSecret) || \in_array($this->kernelSecret, self::PUBLICLY_KNOWN_SECRETS, true)) {
            throw new \RuntimeException('APP_SECRET is empty or set to a publicly known value. Define a unique random APP_SECRET for this deployment (environment variable, .env.local or the secrets vault).');
        }
    }
}
