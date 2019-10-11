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

class ImageRenderer extends atoum
{
    public function testSupports()
    {
        /** @var DocumentInterface $mockValidDocument */
        $mockValidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockValidDocument->setFilename('file.jpg');
        $mockValidDocument->setMimeType('image/jpeg');

        /** @var DocumentInterface $mockInvalidDocument */
        $mockInvalidDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockInvalidDocument->setFilename('file.psd');
        $mockInvalidDocument->setMimeType('image/vnd.adobe.photoshop');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($mockValidDocument->getMimeType())
            ->isEqualTo('image/jpeg')
            ->boolean($renderer->supports($mockValidDocument, []))
            ->isEqualTo(true)
            ->boolean($renderer->supports($mockValidDocument, ['picture' => true]))
            ->isEqualTo(false)
            ->string($mockInvalidDocument->getMimeType())
            ->isEqualTo('image/vnd.adobe.photoshop')
            ->boolean($renderer->supports($mockInvalidDocument, []))
            ->isEqualTo(false);
    }

    public function testRender()
    {
        /** @var DocumentInterface $mockDocument */
        $mockDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockDocument->setFilename('file.jpg');
        $mockDocument->setFolder('folder');
        $mockDocument->setMimeType('image/jpeg');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($renderer->render($mockDocument, ['noProcess' => true]))
            ->isEqualTo(<<<EOT
<img alt="file.jpg" src="/files/folder/file.jpg" />
EOT
            )
            ->string($renderer->render($mockDocument, ['absolute' => true, 'noProcess' => true]))
            ->isEqualTo(<<<EOT
<img alt="file.jpg" src="http://dummy.test/files/folder/file.jpg" />
EOT
            )
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'absolute' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" src="http://dummy.test/assets/w300-q90/folder/file.jpg" width="300" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'class' => 'awesome-image responsive',
                'absolute' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
    src="http://dummy.test/assets/w300-q90/folder/file.jpg" 
    width="300" 
    class="awesome-image responsive" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true
            ]))
            ->contains('noscript')
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
    data-src="/assets/w300-q90/folder/file.jpg" 
    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
    width="300" 
    class="lazyload" />
<noscript>
    <img alt="file.jpg" 
        src="/assets/w300-q90/folder/file.jpg" 
        width="300" />
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true,
                'fallback' => 'https://test.test/fallback.png'
            ]))
            ->contains('noscript')
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
    data-src="/assets/w300-q90/folder/file.jpg" 
    src="https://test.test/fallback.png" 
    width="300" 
    class="lazyload" />
<noscript>
    <img alt="file.jpg" 
         src="/assets/w300-q90/folder/file.jpg" 
         width="300" />
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'fallback' => 'https://test.test/fallback.png'
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
src="/assets/w300-q90/folder/file.jpg" 
width="300" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'quality' => 70
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     src="/assets/f600x400-q70/folder/file.jpg" 
     width="600" 
     height="400" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true,
                'class' => 'awesome-image responsive',
            ]))
            ->contains('noscript')
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
data-src="/assets/w300-q90/folder/file.jpg" 
src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
width="300" 
class="awesome-image responsive lazyload" />
<noscript>
    <img alt="file.jpg" 
    src="/assets/w300-q90/folder/file.jpg" 
    width="300" 
    class="awesome-image responsive" />
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'srcset' => [[
                    'format' => [
                        'width' => 300
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'width' => 600
                    ],
                    'rule' => '2x'
                ]]
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     src="/assets/w300-q90/folder/file.jpg" 
     srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
     width="300" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'srcset' => [[
                    'format' => [
                        'width' => 300
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'width' => 600
                    ],
                    'rule' => '2x'
                ]],
                'sizes' => [
                    '(max-width: 767px) 300px',
                    '(min-width: 768px) 400px'
                ]
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     src="/assets/w300-q90/folder/file.jpg" 
     srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
     sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'srcset' => [[
                    'format' => [
                        'fit' => '600x400',
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'fit' => '1200x800',
                    ],
                    'rule' => '2x'
                ]],
                'sizes' => [
                    '(max-width: 767px) 300px',
                    '(min-width: 768px) 400px'
                ]
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     src="/assets/f600x400-q90/folder/file.jpg" 
     srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
     sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" />
EOT
            ))

            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'loading' => 'lazy',
                'srcset' => [[
                    'format' => [
                        'fit' => '600x400',
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'fit' => '1200x800',
                    ],
                    'rule' => '2x'
                ]],
                'sizes' => [
                    '(max-width: 767px) 300px',
                    '(min-width: 768px) 400px'
                ]
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     src="/assets/f600x400-q90/folder/file.jpg" 
     srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
     sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
     loading="lazy" />
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'lazyload' => true,
                'srcset' => [[
                    'format' => [
                        'fit' => '600x400',
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'fit' => '1200x800',
                    ],
                    'rule' => '2x'
                ]],
                'sizes' => [
                    '(max-width: 767px) 300px',
                    '(min-width: 768px) 400px'
                ]
            ]))
            ->contains('noscript')
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     data-src="/assets/f600x400-q90/folder/file.jpg" 
     src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
     data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
     sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
     class="lazyload" />
<noscript>
    <img alt="file.jpg" 
         src="/assets/f600x400-q90/folder/file.jpg" 
         srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
         sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" />
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'lazyload' => true,
                'loading' => 'lazy',
                'srcset' => [[
                    'format' => [
                        'fit' => '600x400',
                    ],
                    'rule' => '1x'
                ],[
                    'format' => [
                        'fit' => '1200x800',
                    ],
                    'rule' => '2x'
                ]],
                'sizes' => [
                    '(max-width: 767px) 300px',
                    '(min-width: 768px) 400px'
                ]
            ]))
            ->contains('noscript')
            ->isEqualTo($this->htmlTidy(<<<EOT
<img alt="file.jpg" 
     data-src="/assets/f600x400-q90/folder/file.jpg" 
     src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
     data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
     sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
     loading="lazy" 
     class="lazyload" />
<noscript>
    <img alt="file.jpg" 
         src="/assets/f600x400-q90/folder/file.jpg" 
         srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
         sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
         loading="lazy" />
</noscript>
EOT
            ))
        ;
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

    private function htmlTidy(string $body): string
    {
        $body = preg_replace('#[\n\r\s]{2,}#', ' ', $body);
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body);
    }

    private function getEnvironment(): Environment
    {
        $loader = new FilesystemLoader([
            dirname(__DIR__) . '/../../../src/Roadiz/Resources/views'
        ]);
        return new Environment($loader, [
            'autoescape' => false,
            'debug' => true
        ]);
    }
}
