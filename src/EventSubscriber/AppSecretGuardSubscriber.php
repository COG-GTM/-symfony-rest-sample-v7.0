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

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Refuses to serve HTTP requests outside the dev and test environments when
 * APP_SECRET (kernel.secret) is empty or one of the values committed to a
 * public repository. Anyone who knows kernel.secret can forge signed URIs.
 */
class AppSecretGuardSubscriber implements EventSubscriberInterface
{
    public const ENVIRONMENTS_WITH_COMMITTED_SECRETS = ['dev', 'test'];

    public const PUBLIC_SECRETS = [
        // previously committed in .env, also published by the upstream symfony/demo repository
        '2ca64f8d83b9e89f5f19d672841d6bb8',
        // committed in .env.dev
        '2f43c07adc627f12ae97581f93a3fbce',
        // committed in .env.test
        '$ecretf0rt3st',
        // old symfony/framework-bundle recipe default
        'ThisTokenIsNotSoSecretChangeIt',
    ];

    public function __construct(
        private string $secret,
        private string $environment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (\in_array($this->environment, self::ENVIRONMENTS_WITH_COMMITTED_SECRETS, true)) {
            return;
        }

        if ('' === trim($this->secret) || \in_array($this->secret, self::PUBLIC_SECRETS, true)) {
            throw new \RuntimeException(\sprintf('APP_SECRET is empty or publicly known; set a unique random value for the "%s" environment (real environment variable, .env.local or the secrets vault).', $this->environment));
        }
    }
}
