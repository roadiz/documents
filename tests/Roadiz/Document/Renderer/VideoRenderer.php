<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\SimpleFileAware;
use RZ\Roadiz\Document\ArrayDocumentFinder;
use RZ\Roadiz\Document\DocumentFinderInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class VideoRenderer extends atoum
{
    public function testSupports()
    {
        /** @var DocumentInterface $mockValidDocument */
        $mockValidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockValidDocument->setFilename('file.mp4');
        $mockValidDocument->setMimeType('video/mp4');

        /** @var DocumentInterface $mockInvalidDocument */
        $mockInvalidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockInvalidDocument->setFilename('file.jpg');
        $mockInvalidDocument->setMimeType('image/jpeg');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getPackages(),
                $this->getDocumentFinder(),
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($mockValidDocument->getMimeType())
            ->isEqualTo('video/mp4')
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
        $mockDocument->setFilename('file.mp4');
        $mockDocument->setFolder('folder');
        $mockDocument->setMimeType('video/mp4');

        /** @var DocumentInterface $mockDocument2 */
        $mockDocument2 = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockDocument2->setFilename('file2.ogg');
        $mockDocument2->setFolder('folder');
        $mockDocument2->setMimeType('video/ogg');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getPackages(),
                $this->getDocumentFinder(),
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($mockDocument->getMimeType())
            ->isEqualTo('video/mp4')
            ->string($this->htmlTidy($renderer->render($mockDocument, [])))
            ->isEqualTo($this->htmlTidy(<<<EOT
<video controls>
    <source type="video/webm" src="/files/folder/file.webm">
    <source type="video/mp4" src="/files/folder/file.mp4">
    <p>Your browser does not support native video.</p>
</video>
EOT
            ))
            ->string($this->htmlTidy($renderer->render($mockDocument2, [])))
            ->isEqualTo($this->htmlTidy(<<<EOT
<video controls>
    <source type="video/ogg" src="/files/folder/file2.ogg">
    <p>Your browser does not support native video.</p>
</video>
EOT
            ))
            ->string($this->htmlTidy($renderer->render($mockDocument, [
                'controls' => true,
                'loop' => true,
                'autoplay' => true,
            ])))
            ->isEqualTo($this->htmlTidy(<<<EOT
<video controls autoplay playsinline loop>
    <source type="video/webm" src="/files/folder/file.webm">
    <source type="video/mp4" src="/files/folder/file.mp4">
    <p>Your browser does not support native video.</p>
</video>
EOT
            ))
            ->string($this->htmlTidy($renderer->render($mockDocument, [
                'controls' => true,
                'loop' => true,
                'autoplay' => true,
                'muted' => true,
            ])))
            ->isEqualTo($this->htmlTidy(<<<EOT
<video controls autoplay playsinline muted loop>
    <source type="video/webm" src="/files/folder/file.webm">
    <source type="video/mp4" src="/files/folder/file.mp4">
    <p>Your browser does not support native video.</p>
</video>
EOT
            ))
            ->string($this->htmlTidy($renderer->render($mockDocument, [
                'controls' => false
            ])))
            ->isEqualTo($this->htmlTidy(<<<EOT
<video>
    <source type="video/webm" src="/files/folder/file.webm">
    <source type="video/mp4" src="/files/folder/file.mp4">
    <p>Your browser does not support native video.</p>
</video>
EOT
            ))
        ;
    }

    /**
     * @return DocumentFinderInterface
     */
    private function getDocumentFinder(): DocumentFinderInterface
    {
        $finder = new ArrayDocumentFinder();

        $finder->addDocument(
            (new \mock\RZ\Roadiz\Core\Models\SimpleDocument())
                ->setFilename('file.mp4')
                ->setFolder('folder')
                ->setMimeType('video/mp4')
        );
        $finder->addDocument(
            (new \mock\RZ\Roadiz\Core\Models\SimpleDocument())
                ->setFilename('file.webm')
                ->setFolder('folder')
                ->setMimeType('video/webm')
        );
        $finder->addDocument(
            (new \mock\RZ\Roadiz\Core\Models\SimpleDocument())
                ->setFilename('file2.ogg')
                ->setFolder('folder')
                ->setMimeType('video/ogg')
        );

        return $finder;
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
