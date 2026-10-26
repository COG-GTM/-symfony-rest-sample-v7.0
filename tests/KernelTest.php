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

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Outside dev/test the kernel must refuse to boot with an empty or public
 * APP_SECRET, because anyone who knows it can forge signed URIs.
 */
class KernelTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $originalServer;

    /** @var array<string, mixed> */
    private array $originalEnv;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;
    }

    /**
     * @dataProvider getUnsafeSecrets
     */
    public function testProdKernelRefusesToBootWithUnsafeSecret(string $secret): void
    {
        $kernel = $this->createProdKernel($secret);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_SECRET is empty or publicly known');

        try {
            $kernel->boot();
        } finally {
            $kernel->shutdown();
        }
    }

    public function testProdKernelBootsWithUniqueSecret(): void
    {
        $secret = bin2hex(random_bytes(32));
        $kernel = $this->createProdKernel($secret);

        $kernel->boot();

        $this->assertSame($secret, $kernel->getContainer()->getParameter('kernel.secret'));
        $kernel->shutdown();
    }

    /**
     * @dataProvider getUnsafeSecrets
     */
    public function testAssertSecretIsSafeRejectsUnsafeSecrets(string $secret): void
    {
        $this->expectException(\RuntimeException::class);

        Kernel::assertSecretIsSafe($secret, 'prod');
    }

    public function testCommittedSecretsAreListedAsPublic(): void
    {
        $projectDir = \dirname(__DIR__);

        foreach (['.env', '.env.dev', '.env.test'] as $file) {
            $values = (new Dotenv())->parse((string) file_get_contents($projectDir.'/'.$file), $file);
            $secret = $values['APP_SECRET'] ?? '';

            if ('' !== $secret) {
                $this->assertContains($secret, Kernel::PUBLIC_SECRETS, \sprintf('The APP_SECRET committed in %s must be listed in Kernel::PUBLIC_SECRETS.', $file));
            }
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public function getUnsafeSecrets(): \Generator
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];

        foreach (Kernel::PUBLIC_SECRETS as $secret) {
            yield $secret => [$secret];
        }
    }

    private function createProdKernel(string $secret): Kernel
    {
        $_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = $secret;

        return new Kernel('prod', false);
    }
}
