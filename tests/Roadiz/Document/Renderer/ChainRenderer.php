<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\Renderer;
use RZ\Roadiz\Core\Models\SimpleFileAware;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ChainRenderer extends atoum
{
    public function testRender()
    {
        /** @var DocumentInterface $mockSvgDocument */
        $mockSvgDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockSvgDocument->setFilename('file.svg');
        $mockSvgDocument->setFolder('folder');
        $mockSvgDocument->setMimeType('image/svg');

        /** @var DocumentInterface $mockPdfDocument */
        $mockPdfDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockPdfDocument->setFilename('file.pdf');
        $mockPdfDocument->setFolder('folder');
        $mockPdfDocument->setMimeType('application/pdf');

        $renderers = [
            new Renderer\InlineSvgRenderer($this->getPackages()),
            new Renderer\SvgRenderer($this->getPackages()),
            new Renderer\PdfRenderer($this->getEnvironment(), $this->getUrlGenerator()),
            new Renderer\ImageRenderer($this->getEnvironment(), $this->getUrlGenerator()),
            new Renderer\PictureRenderer($this->getEnvironment(), $this->getUrlGenerator()),
        ];

        $this
            ->given($renderer = $this->newTestedInstance($renderers))
            ->then
            ->string($renderer->render($mockPdfDocument, []))
            ->isEqualTo('<object type="application/pdf" data="/files/folder/file.pdf"><p>Your browser does not support PDF native viewer.</p></object>')
            ->string($renderer->render($mockPdfDocument, ['absolute' => true]))
            ->isEqualTo('<object type="application/pdf" data="http://dummy.test/files/folder/file.pdf"><p>Your browser does not support PDF native viewer.</p></object>')
            ->string($renderer->render($mockSvgDocument, []))
            ->isEqualTo(<<<EOT
<object type="image/svg+xml" data="/files/folder/file.svg"></object>
EOT
            )
            ->string($renderer->render($mockSvgDocument, ['inline' => true]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
    <rect width="50" height="50" x="25" y="25" fill="green"></rect>
</svg>
EOT
            ))
        ;
    }

    private function htmlTidy(string $body): string
    {
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body);
    }

    /**
     * @return DocumentUrlGeneratorInterface
     */
    private function getUrlGenerator(): DocumentUrlGeneratorInterface
    {
        return new \mock\RZ\Roadiz\Utils\UrlGenerators\DummyDocumentUrlGenerator($this->getPackages());
    }

    private function getPackages(): Packages
    {
        return new Packages(
            new EmptyVersionStrategy(),
            $this->getDummyRequestStack(),
            new SimpleFileAware(dirname(__DIR__) . '/../../../')
        );
    }

    private function getDummyRequestStack(): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create('http://dummy.test'));
        return $stack;
    }

    private function getEnvironment(): Environment
    {
        $loader = new FilesystemLoader([
            dirname(__DIR__) . '/../../../src/Roadiz/Resources/views'
        ]);
        return new Environment($loader, [
            'autoescape' => false
        ]);
    }
}
