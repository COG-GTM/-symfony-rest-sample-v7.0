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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Limits login attempts per username + client IP, and per client IP, within a
 * fixed time window, so passwords can't be guessed at full speed.
 *
 * Every authentication attempt (login form or HTTP Basic) consumes one attempt;
 * a successful login resets the username + IP counter.
 */
class LoginThrottlingSubscriber implements EventSubscriberInterface
{
    public const MAX_ATTEMPTS = 5;
    public const MAX_ATTEMPTS_PER_IP = 50;
    public const INTERVAL_SECONDS = 60;

    public function __construct(
        private CacheItemPoolInterface $cache,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // runs before the password is checked (and before the user is loaded)
            CheckPassportEvent::class => ['onCheckPassport', 2080],
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(UserBadge::class)) {
            return;
        }

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        $userAttempts = $this->consume($this->userKey($badge->getUserIdentifier(), $request));
        $ipAttempts = $this->consume($this->ipKey($request));

        if ($userAttempts > self::MAX_ATTEMPTS || $ipAttempts > self::MAX_ATTEMPTS_PER_IP) {
            throw new TooManyLoginAttemptsAuthenticationException((int) ceil(self::INTERVAL_SECONDS / 60));
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->cache->deleteItem($this->userKey($event->getUser()->getUserIdentifier(), $event->getRequest()));
    }

    private function consume(string $key): int
    {
        $now = time();
        $start = $now;
        $attempts = 0;

        $item = $this->cache->getItem($key);
        $stored = $item->isHit() ? $item->get() : null;
        $storedStart = \is_array($stored) ? ($stored['start'] ?? null) : null;
        $storedAttempts = \is_array($stored) ? ($stored['attempts'] ?? null) : null;
        if (\is_int($storedStart) && \is_int($storedAttempts) && $storedStart + self::INTERVAL_SECONDS > $now) {
            $start = $storedStart;
            $attempts = $storedAttempts;
        }

        ++$attempts;
        $item->set(['start' => $start, 'attempts' => $attempts]);
        $item->expiresAfter($start + self::INTERVAL_SECONDS - $now);
        $this->cache->save($item);

        return $attempts;
    }

    private function userKey(string $username, Request $request): string
    {
        return 'login_throttling.user.'.hash('sha256', mb_strtolower($username).'|'.$request->getClientIp());
    }

    private function ipKey(Request $request): string
    {
        return 'login_throttling.ip.'.hash('sha256', (string) $request->getClientIp());
    }
}
