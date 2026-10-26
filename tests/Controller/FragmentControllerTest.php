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

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * A signed /_fragment request runs whatever controller (or PHP function) it
 * names, so the endpoint must not be exposed even to correctly signed URIs.
 */
class FragmentControllerTest extends WebTestCase
{
    /**
     * @dataProvider getSigningSecrets
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

    public function testSidebarIsStillRendered(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/blog/');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('#sidebar .section.about')->count(), 'The sidebar "about" fragment is rendered inline.');
    }

    public function getSigningSecrets(): \Generator
    {
        yield 'kernel secret' => [null];
        yield 'previously committed secret' => [Kernel::PUBLIC_SECRETS[0]];
    }
}
