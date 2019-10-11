<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer\tests\units;

use atoum;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\Renderer;
use RZ\Roadiz\Core\Models\SimpleFileAware;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
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

        /** @var DocumentInterface $mockDocumentYoutube */
        $mockDocumentYoutube = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockDocumentYoutube->setFilename('poster.jpg');
        $mockDocumentYoutube->setEmbedId('xxxxxxx');
        $mockDocumentYoutube->setEmbedPlatform('youtube');
        $mockDocumentYoutube->setMimeType('image/jpeg');

        /** @var DocumentInterface $mockPictureDocument */
        $mockPictureDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockPictureDocument->setFilename('file.jpg');
        $mockPictureDocument->setFolder('folder');
        $mockPictureDocument->setMimeType('image/jpeg');

        $renderers = [
            new Renderer\InlineSvgRenderer($this->getPackages()),
            new Renderer\SvgRenderer($this->getPackages()),
            new Renderer\PdfRenderer($this->getEnvironment(), $this->getUrlGenerator()),
            new Renderer\ImageRenderer($this->getEmbedFinderFactory(), $this->getEnvironment(), $this->getUrlGenerator()),
            new Renderer\PictureRenderer($this->getEmbedFinderFactory(), $this->getEnvironment(), $this->getUrlGenerator()),
            new Renderer\EmbedRenderer($this->getEmbedFinderFactory()),
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
            ->boolean($mockDocumentYoutube->isEmbed())
            ->isEqualTo(true)
            ->string($renderer->render($mockDocumentYoutube, ['embed' => true]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<iframe src="https://www.youtube.com/embed/xxxxxxx?rel=0&html5=1&wmode=transparent&loop=0&controls=1&fs=1&modestbranding=1&showinfo=0&enablejsapi=1&mute=0" 
        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" 
        allowFullScreen></iframe>
EOT
            ))
            ->string($renderer->render($mockPictureDocument, [
                'width' => 300,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
<source srcset="/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
<source srcset="/assets/w300-q90/folder/file.jpg" type="image/jpeg">
<img alt="file.jpg" src="/assets/w300-q90/folder/file.jpg" width="300" />
</picture>
EOT
            ))
        ;
    }

    private function htmlTidy(string $body): string
    {
        $body = preg_replace('#[\n\r\t\s]{2,}#', ' ', $body);
        return preg_replace('#\>[\n\r\t\s]+\<#', '><', $body);
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

    /**
     * @return EmbedFinderFactory
     */
    private function getEmbedFinderFactory(): EmbedFinderFactory
    {
        return new EmbedFinderFactory([
            'youtube' => \mock\RZ\Roadiz\Utils\MediaFinders\AbstractYoutubeEmbedFinder::class,
            'vimeo' => \mock\RZ\Roadiz\Utils\MediaFinders\AbstractVimeoEmbedFinder::class,
            'dailymotion' => \mock\RZ\Roadiz\Utils\MediaFinders\AbstractDailymotionEmbedFinder::class,
            'soundcloud' => \mock\RZ\Roadiz\Utils\MediaFinders\AbstractSoundcloudEmbedFinder::class,
        ]);
    }
}
