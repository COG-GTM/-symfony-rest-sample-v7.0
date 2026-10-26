<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\SourceCodeExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class SourceCodeExtensionTest extends TestCase
{
    public function testNothingIsRenderedWhenDebugIsOff(): void
    {
        $extension = new SourceCodeExtension(false);
        $extension->setController([$this, 'sampleController']);

        $html = $this->createTwig($extension)->render('page.html.twig');

        $this->assertSame('<aside></aside>', $html);
    }

    public function testExtensionIsDisabledByDefault(): void
    {
        $extension = new SourceCodeExtension();
        $extension->setController([$this, 'sampleController']);

        $this->assertSame('', $extension->showSourceCode($this->createTwig($extension), 'page.html.twig'));
    }

    public function testSourceCodeIsRenderedWhenDebugIsOn(): void
    {
        $extension = new SourceCodeExtension(true);
        $extension->setController([$this, 'sampleController']);

        $html = $this->createTwig($extension)->render('page.html.twig');

        $this->assertStringContainsString('controller_path='.__FILE__, $html);
        $this->assertStringContainsString('function sampleController', $html);
        $this->assertStringContainsString('{{ show_source_code(_self) }}', $html);
    }

    public function sampleController(): string
    {
        return 'sample';
    }

    private function createTwig(SourceCodeExtension $extension): Environment
    {
        $twig = new Environment(new ArrayLoader([
            'debug/source_code.html.twig' => 'controller_path={{ controller.file_path }} {{ controller.source_code }} {{ template.source_code }}',
            'page.html.twig' => '<aside>{{ show_source_code(_self) }}</aside>',
        ]), ['strict_variables' => true, 'autoescape' => false]);
        $twig->addExtension($extension);

        return $twig;
    }
}
