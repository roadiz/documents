<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\SimpleFileAware;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PdfRenderer extends atoum
{
    public function testSupports()
    {
        /** @var DocumentInterface $mockValidDocument */
        $mockValidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockValidDocument->setFilename('file.pdf');
        $mockValidDocument->setMimeType('application/pdf');

        /** @var DocumentInterface $mockInvalidDocument */
        $mockInvalidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockInvalidDocument->setFilename('file.jpg');
        $mockInvalidDocument->setMimeType('image/jpeg');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($mockValidDocument->getMimeType())
            ->isEqualTo('application/pdf')
            ->boolean($renderer->supports($mockValidDocument, []))
            ->isEqualTo(true)
            ->string($mockInvalidDocument->getMimeType())
            ->isEqualTo('image/jpeg')
            ->boolean($renderer->supports($mockInvalidDocument, []))
            ->isEqualTo(false);
    }

    public function testRender()
    {
        /** @var DocumentInterface $mockDocument */
        $mockDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockDocument->setFilename('file.pdf');
        $mockDocument->setFolder('folder');
        $mockDocument->setMimeType('application/pdf');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($mockDocument->getMimeType())
            ->isEqualTo('application/pdf')
            ->string($this->htmlTidy($renderer->render($mockDocument, [])))
            ->isEqualTo($this->htmlTidy(
                '<object type="application/pdf" data="/files/folder/file.pdf"><p>Your browser does not support PDF native viewer.</p></object>'
            ));
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

    private function htmlTidy(string $body): string
    {
        $body = preg_replace('#[\n\r\s]{2,}#', ' ', $body);
        $body = str_replace("&#x2F;", '/', $body);
        $body = html_entity_decode($body);
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body);
    }
}
