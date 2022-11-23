<?php
declare(strict_types=1);

namespace RZ\Roadiz\Documents\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Documents\Models\DocumentInterface;
use RZ\Roadiz\Documents\Models\SimpleFileAware;
use RZ\Roadiz\Documents\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InlineSvgRenderer extends atoum
{
    public function testSupports()
    {
        /** @var DocumentInterface $mockValidDocument */
        $mockValidDocument = new \mock\RZ\Roadiz\Documents\Models\SimpleDocument();
        /** @var DocumentInterface $mockInvalidDocument */
        $mockInvalidDocument = new \mock\RZ\Roadiz\Documents\Models\SimpleDocument();
        $mockPackages = $this->getPackages();

        $mockValidDocument->setFilename('file.svg');
        $mockValidDocument->setMimeType('image/svg');

        $mockInvalidDocument->setFilename('file.jpg');
        $mockInvalidDocument->setMimeType('image/jpeg');

        $this
            ->given($renderer = $this->newTestedInstance($mockPackages))
            ->then
            ->string($mockValidDocument->getMimeType())
            ->isEqualTo('image/svg')
            ->boolean($renderer->supports($mockValidDocument, ['inline' => false]))
            ->isEqualTo(false)
            ->boolean($renderer->supports($mockValidDocument, ['inline' => true]))
            ->isEqualTo(true)
            ->string($mockInvalidDocument->getMimeType())
            ->isEqualTo('image/jpeg')
            ->boolean($renderer->supports($mockInvalidDocument, []))
            ->isEqualTo(false);
    }

    public function testRender()
    {
        /** @var DocumentInterface $mockDocument */
        $mockDocument = new \mock\RZ\Roadiz\Documents\Models\SimpleDocument();
        $mockPackages = $this->getPackages();

        $mockDocument->setFilename('file.svg');
        $mockDocument->setFolder('folder');
        $mockDocument->setMimeType('image/svg');

        $this
            ->given($renderer = $this->newTestedInstance($mockPackages))
            ->then
            ->string($mockDocument->getMimeType())
            ->isEqualTo('image/svg')
            ->string($renderer->render($mockDocument, ['inline' => true]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100">
    <rect width="50" height="50" x="25" y="25" fill="green"></rect>
</svg>
EOT
));
    }

    private function htmlTidy(string $body): string
    {
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body);
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
}
