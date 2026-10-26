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

use App\Security\DefaultCredentials;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Refuses any login attempt made with a well-known demo password, even when
 * an existing database still stores that password for some user.
 */
class RejectDefaultCredentialsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // runs before Symfony's CheckCredentialsListener (priority 0)
        return [CheckPassportEvent::class => ['onCheckPassport', 256]];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        /** @var PasswordCredentials $credentials */
        $credentials = $passport->getBadge(PasswordCredentials::class);
        if ($credentials->isResolved()) {
            return;
        }

        if (DefaultCredentials::isDefaultPassword($credentials->getPassword())) {
            throw new BadCredentialsException('A well-known demo password was used.');
        }
    }
}
