<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\UriSigner;

class FragmentControllerTest extends WebTestCase
{
    public function testSignedFragmentIsDeniedToRemoteClients(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->getSignedFragmentUrl($client), [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertFalse($client->getResponse()->isSuccessful());
        $this->assertContains($client->getResponse()->getStatusCode(), [302, 401, 403]);
    }

    public function testSignedFragmentIsServedToLocalGatewayCache(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->getSignedFragmentUrl($client), [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->assertResponseIsSuccessful();
    }

    public function testSidebarFragmentIsStillRendered(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/blog/', [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('#sidebar')->children()->count());
    }

    private function getSignedFragmentUrl(KernelBrowser $client): string
    {
        $path = http_build_query([
            '_controller' => TemplateController::class.'::templateAction',
            'template' => 'blog/about.html.twig',
            '_format' => 'html',
            '_locale' => 'en',
        ]);
        $signer = new UriSigner((string) $client->getContainer()->getParameter('kernel.secret'));

        return $signer->sign('http://localhost/_fragment?_path='.rawurlencode($path));
    }
}
