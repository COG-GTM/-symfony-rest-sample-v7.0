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

namespace App\Tests\Controller;

use App\EventSubscriber\AppSecretGuardSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * Signed /_fragment requests execute arbitrary controllers, so the route must
 * not be exposed (framework.fragments is disabled).
 */
class FragmentRouteTest extends WebTestCase
{
    /**
     * @dataProvider getSecrets
     */
    public function testSignedFragmentRequestIsNotExecuted(?string $secret): void
    {
        $client = static::createClient();
        $secret ??= (string) static::getContainer()->getParameter('kernel.secret');

        $uri = (new UriSigner($secret))->sign('http://localhost/_fragment?_path='.rawurlencode('_controller=phpinfo'));
        $client->request('GET', $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringNotContainsString('PHP Version', (string) $client->getResponse()->getContent());
    }

    public function getSecrets(): \Generator
    {
        yield 'kernel secret' => [null];
        yield 'previously committed secret' => [AppSecretGuardSubscriber::PUBLIC_SECRETS[0]];
    }
}
