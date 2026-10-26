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

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Event\CommentCreatedEvent;
use App\EventSubscriber\CommentNotificationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommentNotificationSubscriberTest extends TestCase
{
    public function testLinkUsesConfiguredSiteUriInsteadOfRequestHost(): void
    {
        $routes = new RouteCollection();
        $routes->add('blog_post', new Route('/{_locale}/blog/posts/{slug}'));

        // Context as populated by the router listener from a request with a forged Host header
        $requestContext = new RequestContext('', 'POST', 'acme-blog.evil.example', 'http');
        $requestContext->setParameter('_locale', 'fr');
        $urlGenerator = new UrlGenerator($routes, $requestContext);

        $mailer = new class() implements MailerInterface {
            public ?RawMessage $message = null;

            public function send(RawMessage $message, Envelope $envelope = null): void
            {
                $this->message = $message;
            }
        };

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => $parameters['%link%'] ?? $id
        );

        $subscriber = new CommentNotificationSubscriber(
            $mailer,
            $urlGenerator,
            $translator,
            'noreply@acme-blog.example',
            'https://www.acme-blog.example'
        );

        $subscriber->onCommentCreated(new CommentCreatedEvent($this->createComment()));

        $this->assertInstanceOf(Email::class, $mailer->message);
        $this->assertSame(
            'https://www.acme-blog.example/fr/blog/posts/hello-world#comment_42',
            $mailer->message->getHtmlBody()
        );
        $this->assertSame($requestContext, $urlGenerator->getContext(), 'The request context is restored.');
        $this->assertSame('acme-blog.evil.example', $urlGenerator->getContext()->getHost());
    }

    private function createComment(): Comment
    {
        $author = new User();
        $author->setEmail('alice@acme-blog.example');

        $post = new Post();
        $post->setTitle('Hello World');
        $post->setSlug('hello-world');
        $post->setAuthor($author);

        $comment = new Comment();
        $post->addComment($comment);

        $id = new \ReflectionProperty(Comment::class, 'id');
        $id->setAccessible(true);
        $id->setValue($comment, 42);

        return $comment;
    }
}
