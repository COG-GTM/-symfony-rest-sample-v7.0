<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

/**
 * Decides whether a user-supplied post-login redirect target is a path on this
 * application. Anything with a scheme, a host, a protocol-relative prefix,
 * backslashes or control characters is rejected, because browsers may resolve
 * values such as "http:evil.example", "//evil.example" or "/\evil.example" to
 * another site.
 */
final class LocalTargetPath
{
    public static function isLocal(mixed $path): bool
    {
        if (!\is_string($path) || '' === $path || '/' !== $path[0]) {
            return false;
        }

        if (1 === preg_match('/[\x00-\x20\x7F\\\\]/', $path) || str_starts_with($path, '//')) {
            return false;
        }

        $parts = parse_url($path);

        return false !== $parts && !isset($parts['scheme']) && !isset($parts['host']);
    }

    public static function sanitize(mixed $path): string
    {
        return self::isLocal($path) ? $path : '';
    }
}
