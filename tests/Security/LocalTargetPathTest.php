<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Security\LocalTargetPath;
use PHPUnit\Framework\TestCase;

class LocalTargetPathTest extends TestCase
{
    /**
     * @dataProvider getLocalPaths
     */
    public function testLocalPathsAreAccepted(string $path): void
    {
        $this->assertTrue(LocalTargetPath::isLocal($path));
        $this->assertSame($path, LocalTargetPath::sanitize($path));
    }

    public function getLocalPaths(): \Generator
    {
        yield ['/'];
        yield ['/en/blog/'];
        yield ['/en/blog/posts/lorem-ipsum-dolor-sit-amet'];
        yield ['/en/blog/search?q=symfony&l=10'];
        yield ['/en/admin/post/#top'];
    }

    /**
     * @dataProvider getExternalTargets
     */
    public function testExternalTargetsAreRejected(mixed $path): void
    {
        $this->assertFalse(LocalTargetPath::isLocal($path));
        $this->assertSame('', LocalTargetPath::sanitize($path));
    }

    public function getExternalTargets(): \Generator
    {
        yield [null];
        yield [''];
        yield [['/en/blog/']];
        yield ['http:evil.example'];
        yield ['http:/evil.example'];
        yield ['https:evil.example'];
        yield ['https:/evil.example'];
        yield ['https://evil.example'];
        yield ['//evil.example'];
        yield ['///evil.example'];
        yield ['/\\evil.example'];
        yield ['/\\/evil.example'];
        yield ["/\t/evil.example"];
        yield ["/\n/evil.example"];
        yield ['javascript:alert(1)'];
        yield ['evil.example'];
        yield ['blog_index'];
    }
}
