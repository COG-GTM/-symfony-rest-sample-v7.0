<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    private ?string $originalServerSecret;
    private ?string $originalEnvSecret;

    protected function setUp(): void
    {
        $this->originalServerSecret = $_SERVER['APP_SECRET'] ?? null;
        $this->originalEnvSecret = $_ENV['APP_SECRET'] ?? null;
    }

    protected function tearDown(): void
    {
        $this->setSecret($this->originalServerSecret, $this->originalEnvSecret);
    }

    /**
     * @dataProvider getUnsafeSecrets
     */
    public function testBootFailsWithUnsafeSecret(string $secret): void
    {
        $this->setSecret($secret, $secret);
        $kernel = new Kernel('test', true);

        try {
            $kernel->boot();
            $this->fail('The kernel must not boot with an unsafe APP_SECRET.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('APP_SECRET', $e->getMessage());
        }

        $this->expectException(\RuntimeException::class);
        $kernel->boot();
    }

    public function testBootSucceedsWithPrivateSecret(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->setSecret($secret, $secret);
        $kernel = new Kernel('test', true);

        $kernel->boot();

        $this->assertSame($secret, $kernel->getContainer()->getParameter('kernel.secret'));
        $kernel->shutdown();
    }

    public function getUnsafeSecrets(): \Generator
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
        yield 'published in this repository' => ['2ca64f8d83b9e89f5f19d672841d6bb8'];
    }

    private function setSecret(?string $serverValue, ?string $envValue): void
    {
        if (null === $serverValue) {
            unset($_SERVER['APP_SECRET']);
        } else {
            $_SERVER['APP_SECRET'] = $serverValue;
        }

        if (null === $envValue) {
            unset($_ENV['APP_SECRET']);
        } else {
            $_ENV['APP_SECRET'] = $envValue;
        }
    }
}
