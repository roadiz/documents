<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\SimpleFileAware;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SvgRenderer extends atoum
{
    public function testSupports()
    {
        /** @var DocumentInterface $mockValidDocument */
        $mockValidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        /** @var DocumentInterface $mockInvalidDocument */
        $mockInvalidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockPackages = $this->getPackages();

        $mockValidDocument->setFilename('file.svg');
        $mockValidDocument->setMimeType('image/svg+xml');

        $mockInvalidDocument->setFilename('file.jpg');
        $mockInvalidDocument->setMimeType('image/jpeg');

        $this
            ->given($renderer = $this->newTestedInstance($mockPackages))
            ->then
            ->string($mockValidDocument->getMimeType())
            ->isEqualTo('image/svg+xml')
            ->boolean($renderer->supports($mockValidDocument, []))
            ->isEqualTo(true)
            ->boolean($renderer->supports($mockValidDocument, ['inline' => false]))
            ->isEqualTo(true)
            ->boolean($renderer->supports($mockValidDocument, ['inline' => true]))
            ->isEqualTo(false)
            ->string($mockInvalidDocument->getMimeType())
            ->isEqualTo('image/jpeg')
            ->boolean($renderer->supports($mockInvalidDocument, []))
            ->isEqualTo(false);
    }

    public function testRender()
    {
        /** @var DocumentInterface $mockDocument */
        $mockDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockPackages = $this->getPackages();

        $mockDocument->setFilename('file2.svg');
        $mockDocument->setFolder('folder');
        $mockDocument->setMimeType('image/svg+xml');

        $this
            ->given($renderer = $this->newTestedInstance($mockPackages))
            ->then
            ->string($mockDocument->getMimeType())
            ->isEqualTo('image/svg+xml')
            ->string($renderer->render($mockDocument, []))
            ->isEqualTo(<<<EOT
<object type="image/svg+xml" data="/files/folder/file2.svg"></object>
EOT
);
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
