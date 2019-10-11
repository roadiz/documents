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

class PictureRenderer extends atoum
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
            ->isEqualTo(false)
            ->boolean($renderer->supports($mockValidDocument, ['picture' => true]))
            ->isEqualTo(true)
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

        /** @var DocumentInterface $mockWebpDocument */
        $mockWebpDocument = new \mock\RZ\Roadiz\Core\Models\SimpleDocument();
        $mockWebpDocument->setFilename('file.webp');
        $mockWebpDocument->setFolder('folder');
        $mockWebpDocument->setMimeType('image/webp');

        $this
            ->given($renderer = $this->newTestedInstance(
                $this->getEnvironment(),
                $this->getUrlGenerator()
            ))
            ->then
            ->string($renderer->render($mockDocument, [
                'noProcess' => true,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
<source srcset="/files/folder/file.jpg.webp" type="image/webp">
<source srcset="/files/folder/file.jpg" type="image/jpeg">
<img alt="file.jpg" src="/files/folder/file.jpg" />
</picture>
EOT
            ))
            ->string($renderer->render($mockWebpDocument, [
                'noProcess' => true,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
<source srcset="/files/folder/file.webp" type="image/webp">
<img alt="file.webp" src="/files/folder/file.webp" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'absolute' => true,
                'noProcess' => true,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
<source srcset="http://dummy.test/files/folder/file.jpg.webp" type="image/webp">
<source srcset="http://dummy.test/files/folder/file.jpg" type="image/jpeg">
<img alt="file.jpg" src="http://dummy.test/files/folder/file.jpg" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'absolute' => true,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
<source srcset="http://dummy.test/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
<source srcset="http://dummy.test/assets/w300-q90/folder/file.jpg" type="image/jpeg">
<img alt="file.jpg" src="http://dummy.test/assets/w300-q90/folder/file.jpg" width="300" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'class' => 'awesome-image responsive',
                'absolute' => true,
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture class="awesome-image responsive">
<source srcset="http://dummy.test/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
<source srcset="http://dummy.test/assets/w300-q90/folder/file.jpg" type="image/jpeg">
<img alt="file.jpg" src="http://dummy.test/assets/w300-q90/folder/file.jpg" width="300" class="awesome-image responsive" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true,
                'picture' => true
            ]))
            ->endWith('</noscript>')
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/w300-q90/folder/file.jpg.webp" 
            type="image/webp">
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/w300-q90/folder/file.jpg" 
            type="image/jpeg">
    <img alt="file.jpg" 
         data-src="/assets/w300-q90/folder/file.jpg" 
         src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
         width="300" 
         class="lazyload" />
</picture>
<noscript>
    <picture>
        <source srcset="/assets/w300-q90/folder/file.jpg.webp" 
                type="image/webp">
        <source srcset="/assets/w300-q90/folder/file.jpg" 
                type="image/jpeg">
        <img alt="file.jpg" 
             src="/assets/w300-q90/folder/file.jpg" 
             width="300" />
    </picture>
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true,
                'picture' => true,
                'fallback' => 'https://test.test/fallback.png'
            ]))
            ->endWith('</noscript>')
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="https://test.test/fallback.png" data-srcset="/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
    <source srcset="https://test.test/fallback.png" data-srcset="/assets/w300-q90/folder/file.jpg" type="image/jpeg">
    <img alt="file.jpg" 
         data-src="/assets/w300-q90/folder/file.jpg" 
         src="https://test.test/fallback.png" 
         width="300" 
         class="lazyload" />
</picture>
<noscript>
    <picture>
        <source srcset="/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
        <source srcset="/assets/w300-q90/folder/file.jpg" type="image/jpeg">
        <img alt="file.jpg"  
            src="/assets/w300-q90/folder/file.jpg" 
            width="300" />
    </picture>
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'fallback' => 'https://test.test/fallback.png',
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="/assets/w300-q90/folder/file.jpg.webp" type="image/webp">
    <source srcset="/assets/w300-q90/folder/file.jpg" type="image/jpeg">
    <img alt="file.jpg" 
        src="/assets/w300-q90/folder/file.jpg" 
        width="300" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'width' => 300,
                'lazyload' => true,
                'class' => 'awesome-image responsive',
                'picture' => true
            ]))
            ->endWith('</noscript>')
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture class="awesome-image responsive">
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/w300-q90/folder/file.jpg.webp" 
            type="image/webp">
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/w300-q90/folder/file.jpg" 
            type="image/jpeg">
    <img alt="file.jpg" 
         data-src="/assets/w300-q90/folder/file.jpg" 
         src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
         width="300" 
         class="awesome-image responsive lazyload" />
</picture>
<noscript>
    <picture class="awesome-image responsive">
        <source srcset="/assets/w300-q90/folder/file.jpg.webp" 
                type="image/webp">
        <source srcset="/assets/w300-q90/folder/file.jpg" 
                type="image/jpeg">
        <img alt="file.jpg" 
             src="/assets/w300-q90/folder/file.jpg" 
             width="300" 
             class="awesome-image responsive" />
    </picture>
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
                ]],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="/assets/w300-q90/folder/file.jpg.webp 1x, /assets/w600-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
        src="/assets/w300-q90/folder/file.jpg" 
        srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
        width="300" />
</picture>
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
                ],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/w300-q90/folder/file.jpg.webp 1x, /assets/w600-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
        src="/assets/w300-q90/folder/file.jpg" 
        srcset="/assets/w300-q90/folder/file.jpg 1x, /assets/w600-q90/folder/file.jpg 2x" 
        sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" />
</picture>
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
                ],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
        src="/assets/f600x400-q90/folder/file.jpg" 
        srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
        sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" />
</picture>
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
                ],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
            srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
        src="/assets/f600x400-q90/folder/file.jpg" 
        srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
        sizes="(max-width: 767px) 300px, (min-width: 768px) 400px" 
        loading="lazy" />
</picture>
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
                'picture' => true
            ]))
            ->endWith('</noscript>')
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
            data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
         data-src="/assets/f600x400-q90/folder/file.jpg" 
         src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNcvGDBfwAGtQLk4581vAAAAABJRU5ErkJggg==" 
         data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
         width="600" height="400"
         class="lazyload" />
</picture>
<noscript>
    <picture>
        <source srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
                type="image/webp">
        <source srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
                type="image/jpeg">
        <img alt="file.jpg" 
             src="/assets/f600x400-q90/folder/file.jpg" 
             srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
             width="600" height="400" />
    </picture>
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'lazyload' => true,
                'fallback' => 'https://test.test/fallback.png',
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
                'picture' => true
            ]))
            ->endWith('</noscript>')
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source srcset="https://test.test/fallback.png" 
            data-srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source srcset="https://test.test/fallback.png" 
            data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
         data-src="/assets/f600x400-q90/folder/file.jpg" 
         src="https://test.test/fallback.png" 
         data-srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
         width="600" height="400"
         class="lazyload" />
</picture>
<noscript>
    <picture>
        <source srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
                type="image/webp">
        <source srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
                type="image/jpeg">
        <img alt="file.jpg" 
            src="/assets/f600x400-q90/folder/file.jpg" 
            srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            width="600" 
            height="400" />
    </picture>
</noscript>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'media' => [[
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
                    'rule' => '(min-width: 600px)'
                ]],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source media="(min-width: 600px)" 
            srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source media="(min-width: 600px)"  
            srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
    <img alt="file.jpg" 
         src="/assets/f600x400-q90/folder/file.jpg" 
         width="600" height="400" />
</picture>
EOT
            ))
            ->string($renderer->render($mockDocument, [
                'fit' => '600x400',
                'media' => [[
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
                    'rule' => '(min-width: 600px)'
                ],[
                    'srcset' => [[
                        'format' => [
                            'fit' => '1200x800',
                        ],
                        'rule' => '1x'
                    ],[
                        'format' => [
                            'fit' => '2400x1600',
                        ],
                        'rule' => '2x'
                    ]],
                    'rule' => '(min-width: 1200px)'
                ]],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source media="(min-width: 600px)"
            srcset="/assets/f600x400-q90/folder/file.jpg.webp 1x, /assets/f1200x800-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source media="(min-width: 600px)" 
            srcset="/assets/f600x400-q90/folder/file.jpg 1x, /assets/f1200x800-q90/folder/file.jpg 2x" 
            type="image/jpeg">
            
    <source media="(min-width: 1200px)"
            srcset="/assets/f1200x800-q90/folder/file.jpg.webp 1x, /assets/f2400x1600-q90/folder/file.jpg.webp 2x" 
            type="image/webp">
    <source media="(min-width: 1200px)" 
            srcset="/assets/f1200x800-q90/folder/file.jpg 1x, /assets/f2400x1600-q90/folder/file.jpg 2x" 
            type="image/jpeg">
            
    <img alt="file.jpg" 
         src="/assets/f600x400-q90/folder/file.jpg" 
         width="600" height="400" />
</picture>
EOT
            ))
            ->string($renderer->render($mockWebpDocument, [
                'fit' => '600x400',
                'lazyload' => true,
                'fallback' => 'FALLBACK',
                'media' => [[
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
                    'rule' => '(min-width: 600px)'
                ],[
                    'srcset' => [[
                        'format' => [
                            'fit' => '1200x800',
                        ],
                        'rule' => '1x'
                    ],[
                        'format' => [
                            'fit' => '2400x1600',
                        ],
                        'rule' => '2x'
                    ]],
                    'rule' => '(min-width: 1200px)'
                ]],
                'picture' => true
            ]))
            ->isEqualTo($this->htmlTidy(<<<EOT
<picture>
    <source media="(min-width: 600px)"
            srcset="FALLBACK" 
            data-srcset="/assets/f600x400-q90/folder/file.webp 1x, /assets/f1200x800-q90/folder/file.webp 2x" 
            type="image/webp">
            
    <source media="(min-width: 1200px)"
            srcset="FALLBACK" 
            data-srcset="/assets/f1200x800-q90/folder/file.webp 1x, /assets/f2400x1600-q90/folder/file.webp 2x" 
            type="image/webp">
            
    <img alt="file.webp" 
         data-src="/assets/f600x400-q90/folder/file.webp"
         src="FALLBACK" 
         width="600" height="400"
         class="lazyload" />
</picture>
<noscript>
    <picture>
        <source media="(min-width: 600px)"
                srcset="/assets/f600x400-q90/folder/file.webp 1x, /assets/f1200x800-q90/folder/file.webp 2x" 
                type="image/webp">
                
        <source media="(min-width: 1200px)"
                srcset="/assets/f1200x800-q90/folder/file.webp 1x, /assets/f2400x1600-q90/folder/file.webp 2x" 
                type="image/webp">
                
        <img alt="file.webp" 
             src="/assets/f600x400-q90/folder/file.webp" 
             width="600" height="400" />
    </picture>
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
        $body = preg_replace('#[\n\r\t\s]{2,}#', ' ', $body);
        return preg_replace('#\>[\n\r\t\s]+\<#', '><', $body);
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
