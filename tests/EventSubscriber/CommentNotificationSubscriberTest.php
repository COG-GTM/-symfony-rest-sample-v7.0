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

/**
 * Unit test for the comment notification e-mail.
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit tests/EventSubscriber/CommentNotificationSubscriberTest.php
 */
class CommentNotificationSubscriberTest extends TestCase
{
    public function testLinkUsesSiteUriInsteadOfRequestHost(): void
    {
        // the context the router listener builds from a request with a forged Host header
        $requestContext = new RequestContext('', 'POST', 'acme-blog-login.example', 'http');
        $requestContext->setParameter('_locale', 'fr');

        $routes = new RouteCollection();
        $routes->add('blog_post', new Route('/{_locale}/blog/posts/{slug}'));
        $urlGenerator = new UrlGenerator($routes, $requestContext);

        $email = $this->notify($urlGenerator, 'Hello World');

        $this->assertSame(
            'title=Hello World link=https://www.acme-blog.example/fr/blog/posts/hello-world#comment_42',
            $email->getHtmlBody()
        );
        $this->assertSame(['alice@acme-blog.example'], array_map(static fn ($a) => $a->getAddress(), $email->getTo()));
        $this->assertSame($requestContext, $urlGenerator->getContext(), 'The request context is restored.');
    }

    public function testTitleIsEscapedInHtmlBody(): void
    {
        $routes = new RouteCollection();
        $routes->add('blog_post', new Route('/blog/posts/{slug}'));
        $urlGenerator = new UrlGenerator($routes, new RequestContext());

        $email = $this->notify($urlGenerator, '<a href="https://evil.example">Click</a> & "more"');

        $this->assertSame(
            'title=&lt;a href=&quot;https://evil.example&quot;&gt;Click&lt;/a&gt; &amp; &quot;more&quot;'
            .' link=https://www.acme-blog.example/blog/posts/hello-world#comment_42',
            $email->getHtmlBody()
        );
    }

    private function notify(UrlGenerator $urlGenerator, string $postTitle): Email
    {
        $mailer = new class implements MailerInterface {
            public ?RawMessage $message = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->message = $message;
            }
        };

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => [] === $parameters
                ? $id
                : \sprintf('title=%s link=%s', $parameters['%title%'], $parameters['%link%'])
        );

        $subscriber = new CommentNotificationSubscriber(
            $mailer,
            $urlGenerator,
            $translator,
            'noreply@acme-blog.example',
            'https://www.acme-blog.example'
        );

        $author = new User();
        $author->setEmail('alice@acme-blog.example');

        $post = new Post();
        $post->setTitle($postTitle);
        $post->setSlug('hello-world');
        $post->setAuthor($author);

        $comment = new Comment();
        $post->addComment($comment);
        $id = new \ReflectionProperty(Comment::class, 'id');
        $id->setAccessible(true);
        $id->setValue($comment, 42);

        $subscriber->onCommentCreated(new CommentCreatedEvent($comment));

        $this->assertInstanceOf(Email::class, $mailer->message);

        return $mailer->message;
    }
}
