<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enforces unique post slugs in the database, so concurrent saves can't create
 * two posts with the same URL.
 */
final class Version20261104000000 extends AbstractMigration
{
    private const TABLE = 'symfony_demo_post';
    private const INDEX = 'UNIQ_58A92E65989D9B62';

    public function getDescription(): string
    {
        return 'Add a unique index on symfony_demo_post.slug';
    }

    public function preUp(Schema $schema): void
    {
        $duplicatedSlugs = $this->connection->fetchFirstColumn(
            'SELECT slug FROM '.self::TABLE.' GROUP BY slug HAVING COUNT(*) > 1'
        );

        $this->abortIf(
            [] !== $duplicatedSlugs,
            sprintf('Rename the posts that share these slugs before running this migration: %s', implode(', ', $duplicatedSlugs))
        );
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($schema->getTable(self::TABLE)->hasIndex(self::INDEX), 'The unique index on the post slug already exists.');

        // Plain SQL instead of the Schema API: on SQLite, schema diffs rebuild the whole table.
        $this->addSql(sprintf('CREATE UNIQUE INDEX %s ON %s (slug)', self::INDEX, self::TABLE));
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->platform->getDropIndexSQL(self::INDEX, self::TABLE));
    }
}
