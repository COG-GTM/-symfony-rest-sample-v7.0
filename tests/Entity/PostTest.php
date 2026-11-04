<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostTest extends KernelTestCase
{
    public function testDatabaseRejectsDuplicatedSlugs(): void
    {
        /** @var Connection $connection */
        $connection = static::getContainer()->get('doctrine.dbal.default_connection');
        $existingSlug = $connection->fetchOne('SELECT slug FROM symfony_demo_post WHERE id = 1');

        $this->expectException(UniqueConstraintViolationException::class);

        $connection->insert('symfony_demo_post', [
            'author_id' => 2,
            'title' => 'Duplicated slug',
            'slug' => $existingSlug,
            'summary' => 'Duplicated slug',
            'content' => 'Duplicated slug content',
            'published_at' => '2021-01-01 00:00:00',
        ]);
    }
}
