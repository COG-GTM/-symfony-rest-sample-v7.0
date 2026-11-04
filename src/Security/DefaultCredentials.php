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

namespace App\Security;

/**
 * Passwords that were published with earlier versions of this demo (in the
 * fixtures, the committed SQLite database and the login page). They are
 * public knowledge, so they must never grant access to an account.
 */
final class DefaultCredentials
{
    public const PASSWORDS = ['kitten'];

    public static function isDefaultPassword(string $password): bool
    {
        return \in_array($password, self::PASSWORDS, true);
    }
}
