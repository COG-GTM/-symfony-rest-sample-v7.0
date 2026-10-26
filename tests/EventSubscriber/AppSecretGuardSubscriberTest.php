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

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\AppSecretGuardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppSecretGuardSubscriberTest extends TestCase
{
    /**
     * @dataProvider getUnsafeSecrets
     */
    public function testRejectsUnsafeSecretInProd(string $secret): void
    {
        $subscriber = new AppSecretGuardSubscriber($secret, 'prod');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_SECRET is empty or publicly known');

        $subscriber->onKernelRequest($this->createEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    public function getUnsafeSecrets(): \Generator
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['  '];

        foreach (AppSecretGuardSubscriber::PUBLIC_SECRETS as $secret) {
            yield $secret => [$secret];
        }
    }

    public function testAllowsUniqueSecretInProd(): void
    {
        $subscriber = new AppSecretGuardSubscriber(bin2hex(random_bytes(16)), 'prod');
        $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresDevAndTestEnvironments(): void
    {
        foreach (AppSecretGuardSubscriber::ENVIRONMENTS_WITH_COMMITTED_SECRETS as $environment) {
            $subscriber = new AppSecretGuardSubscriber('', $environment);
            $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST);

            $subscriber->onKernelRequest($event);

            $this->assertFalse($event->hasResponse());
        }
    }

    public function testIgnoresSubRequests(): void
    {
        $subscriber = new AppSecretGuardSubscriber('', 'prod');
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    private function createEvent(int $requestType): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create('/'), $requestType);
    }
}
