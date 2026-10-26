<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\CommentCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Notifies post's author about new comments.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class CommentNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string $sender,
        private string $siteUri
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CommentCreatedEvent::class => 'onCommentCreated',
        ];
    }

    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        $comment = $event->getComment();
        $post = $comment->getPost();

        $linkToPost = $this->generateSiteUrl('blog_post', [
            'slug' => $post->getSlug(),
            '_fragment' => 'comment_'.$comment->getId(),
        ]);

        $subject = $this->translator->trans('notification.comment_created');
        $body = $this->translator->trans('notification.comment_created.description', [
            '%title%' => htmlspecialchars($post->getTitle(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            '%link%' => htmlspecialchars($linkToPost, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        ]);

        // See https://symfony.com/doc/current/mailer.html
        $email = (new Email())
            ->from($this->sender)
            ->to($post->getAuthor()->getEmail())
            ->subject($subject)
            ->html($body)
        ;

        // In config/packages/dev/mailer.yaml the delivery of messages is disabled.
        // That's why in the development environment you won't actually receive any email.
        // However, you can inspect the contents of those unsent emails using the debug toolbar.
        $this->mailer->send($email);
    }

    /**
     * Generates an absolute URL on the canonical site URI. The current request
     * context can't be used: its host and scheme come from the request's
     * (client controlled) Host header.
     */
    private function generateSiteUrl(string $route, array $parameters): string
    {
        $requestContext = $this->urlGenerator->getContext();
        $siteContext = RequestContext::fromUri($this->siteUri);
        $siteContext->setParameters($requestContext->getParameters());

        $this->urlGenerator->setContext($siteContext);

        try {
            return $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        } finally {
            $this->urlGenerator->setContext($requestContext);
        }
    }
}
