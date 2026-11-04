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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Limits login attempts per username + client IP, and per client IP, within a
 * fixed time window, so passwords can't be guessed at full speed.
 *
 * Every authentication attempt (login form or HTTP Basic) consumes one attempt
 * before the password is checked; a successful login gives it back and resets
 * the username + IP counter. Counters are files updated under an exclusive
 * lock, so parallel requests can't bypass the limit on a single server.
 */
class LoginThrottlingSubscriber implements EventSubscriberInterface
{
    public const MAX_ATTEMPTS = 5;
    public const MAX_ATTEMPTS_PER_IP = 50;
    public const INTERVAL_SECONDS = 60;

    public function __construct(
        private RequestStack $requestStack,
        private string $storageDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // after UserProviderListener (2048) gives the badge its user loader,
            // before the user checker (256) and the password check (0)
            CheckPassportEvent::class => ['onCheckPassport', 1024],
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

        $userAttempts = $this->update($this->userKey($this->canonicalUsername($badge), $request), static fn (int $attempts): int => $attempts + 1);
        $ipAttempts = $this->update($this->ipKey($request), static fn (int $attempts): int => $attempts + 1);

        if ($userAttempts > self::MAX_ATTEMPTS || $ipAttempts > self::MAX_ATTEMPTS_PER_IP) {
            throw new TooManyLoginAttemptsAuthenticationException((int) ceil(self::INTERVAL_SECONDS / 60));
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $this->update($this->userKey($event->getUser()->getUserIdentifier(), $request), static fn (int $attempts): int => 0);
        $this->update($this->ipKey($request), static fn (int $attempts): int => max(0, $attempts - 1));
    }

    /**
     * Atomically applies $change to the attempts counted in the current window
     * and returns the new count.
     *
     * @param callable(int): int $change
     */
    private function update(string $key, callable $change): int
    {
        if (!is_dir($this->storageDir) && !@mkdir($this->storageDir, 0770, true) && !is_dir($this->storageDir)) {
            throw new \RuntimeException(sprintf('Unable to create the "%s" directory.', $this->storageDir));
        }

        $handle = fopen($this->storageDir.'/'.$key, 'c+');
        if (false === $handle || !flock($handle, \LOCK_EX)) {
            throw new \RuntimeException(sprintf('Unable to lock the login throttling counter "%s".', $key));
        }

        try {
            $now = time();
            $start = $now;
            $attempts = 0;

            $stored = json_decode((string) stream_get_contents($handle), true);
            $storedStart = \is_array($stored) ? ($stored['start'] ?? null) : null;
            $storedAttempts = \is_array($stored) ? ($stored['attempts'] ?? null) : null;
            if (\is_int($storedStart) && \is_int($storedAttempts) && $storedStart + self::INTERVAL_SECONDS > $now) {
                $start = $storedStart;
                $attempts = $storedAttempts;
            }

            $attempts = $change($attempts);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode(['start' => $start, 'attempts' => $attempts]));
            fflush($handle);

            return $attempts;
        } finally {
            flock($handle, \LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Counts every spelling that resolves to the same account (e.g. case
     * variants on a case-insensitive lookup) against that account.
     */
    private function canonicalUsername(UserBadge $badge): string
    {
        try {
            return $badge->getUser()->getUserIdentifier();
        } catch (AuthenticationException) {
            return $badge->getUserIdentifier();
        }
    }

    private function userKey(string $username, Request $request): string
    {
        return 'user-'.hash('sha256', mb_convert_case($username, \MB_CASE_FOLD).'|'.$request->getClientIp());
    }

    private function ipKey(Request $request): string
    {
        return 'ip-'.hash('sha256', (string) $request->getClientIp());
    }
}
