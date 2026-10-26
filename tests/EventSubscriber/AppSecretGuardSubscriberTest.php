<?php

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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppSecretGuardSubscriberTest extends TestCase
{
    /**
     * @dataProvider getInsecureSecrets
     */
    public function testInsecureSecretIsRejected(string $secret): void
    {
        $this->expectException(\RuntimeException::class);

        (new AppSecretGuardSubscriber($secret))->onKernelRequest($this->createEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return \Generator<int, array{string}>
     */
    public function getInsecureSecrets(): \Generator
    {
        yield [''];
        yield ['   '];
        yield ['2ca64f8d83b9e89f5f19d672841d6bb8'];
        yield ['b94ad73141130db3ce219a4968a7b7a2'];
    }

    public function testUniqueSecretIsAccepted(): void
    {
        $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST);
        (new AppSecretGuardSubscriber(bin2hex(random_bytes(16))))->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testSubRequestsAreIgnored(): void
    {
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);
        (new AppSecretGuardSubscriber(''))->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    private function createEvent(int $requestType): RequestEvent
    {
        $kernel = new class() implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        return new RequestEvent($kernel, Request::create('/'), $requestType);
    }
}
