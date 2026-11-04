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

use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testPostWithPastPublicationDateIsPublished(): void
    {
        $post = new Post();
        $post->setPublishedAt(new \DateTime('-1 minute'));

        $this->assertTrue($post->isPublished());
    }

    public function testScheduledPostIsNotPublished(): void
    {
        $post = new Post();
        $post->setPublishedAt(new \DateTime('+1 day'));

        $this->assertFalse($post->isPublished());
        $this->assertTrue($post->isPublished(new \DateTime('+2 days')));
    }
}
