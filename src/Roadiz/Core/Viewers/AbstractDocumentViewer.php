<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Viewers;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\Renderer\RendererInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;
use Twig\Environment;

/**
 * Class DocumentViewer
 * @package RZ\Roadiz\Core\Viewers
 * @deprecated Use RZ\Roadiz\Document\Renderer\ChainRenderer
 */
abstract class AbstractDocumentViewer implements RendererInterface
{
    /**
     * @var null|DocumentInterface
     */
    protected $document;

    /**
     * @var AbstractEmbedFinder|false
     */
    protected $embedFinder;

    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @var RequestStack
     * @deprecated Useless and creates dependency
     */
    protected $requestStack;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var EntityManagerInterface
     * @deprecated Useless and creates dependency
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $documentPlatforms;

    /**
     * @var SymfonyUrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var DocumentUrlGenerator
     */
    private $documentUrlGenerator;

    /**
     * AbstractDocumentViewer constructor.
     *
     * @param RequestStack                 $requestStack
     * @param Environment                  $environment
     * @param EntityManagerInterface       $entityManager
     * @param SymfonyUrlGeneratorInterface $urlGenerator
     * @param DocumentUrlGenerator         $documentUrlGenerator
     * @param Packages                     $packages
     * @param array                        $availablePlatforms
     */
    public function __construct(
        RequestStack $requestStack,
        Environment $environment,
        EntityManagerInterface $entityManager,
        SymfonyUrlGeneratorInterface $urlGenerator,
        DocumentUrlGenerator $documentUrlGenerator,
        Packages $packages,
        $availablePlatforms
    ) {
        $this->packages = $packages;
        $this->requestStack = $requestStack;
        $this->twig = $environment;
        $this->entityManager = $entityManager;
        $this->documentPlatforms = $availablePlatforms;
        $this->urlGenerator = $urlGenerator;
        $this->documentUrlGenerator = $documentUrlGenerator;
    }

    /**
     * @return null|DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param DocumentInterface $document
     * @return AbstractDocumentViewer
     */
    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @return Packages
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param Packages $packages
     * @return AbstractDocumentViewer
     */
    public function setPackages(Packages $packages)
    {
        $this->packages = $packages;
        return $this;
    }

    /**
     * @param array $options
     * @param bool $convertToWebP
     *
     * @return string|bool
     */
    protected function parseSrcSet(array &$options = [], $convertToWebP = false)
    {
        if (null !== $this->document && count($options['srcset']) > 0) {
            $srcset = [];
            foreach ($options['srcset'] as $set) {
                if (isset($set['format']) && isset($set['rule'])) {
                    $this->documentUrlGenerator->setOptions($set['format']);
                    $this->documentUrlGenerator->setDocument($this->document);
                    $path = $this->documentUrlGenerator->getUrl($options['absolute']);
                    if ($convertToWebP) {
                        $path .= '.webp';
                    }
                    $srcset[] = $path . ' ' . $set['rule'];
                }
            }
            return implode(', ', $srcset);
        }

        return false;
    }

    /**
     *
     * @param  array  $options sizes
     * @return string|bool
     */
    protected function parseSizes(array &$options = [])
    {
        if (count($options['sizes']) > 0) {
            return implode(', ', $options['sizes']);
        }

        return false;
    }

    /**
     * @return string
     */
    abstract protected function getDocumentAlt();

    /**
     * @return string
     */
    abstract protected function getTemplatesBasePath();

    /**
     * @param string[] $filenames
     * @return DocumentInterface[]
     */
    abstract protected function getDocumentsByFilenames($filenames): array;

    /**
     * @param string[] $filenames
     * @return DocumentInterface|null
     *
     * @deprecated Use DocumentFinderInterface
     */
    abstract public function getOneDocumentByFilenames($filenames): ?DocumentInterface;

    /**
     * Output a document HTML tag according to its Mime type and
     * the arguments array.
     *
     * ## HTML output options
     *
     * - embed (true|false), display an embed as iframe instead of its thumbnail
     * - identifier
     * - class
     * - **alt**: If not filled, it will get the document name, then the document filename
     *
     * ## Images resampling options
     *
     * - width
     * - height
     * - lazyload (true | false) set src in data-src
     * - lazyload_class : default "lazyload"
     * - crop ({w}x{h}, for example : 100x200)
     * - fit ({w}x{h}, for example : 100x200)
     * - rotate (1-359 degrees, for example : 90)
     * - fallback (string)
     * - loading ('auto', 'eager', 'lazy')
     * - grayscale (boolean)
     * - quality (1-100)
     * - blur (1-100)
     * - sharpen (1-100)
     * - contrast (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     * - noProcess (boolean) : Disable image resample
     * - inline : For SVG, display SVG code in Html instead of using <object>
     * - srcset : Array
     *     [
     *         - format: Array (same options as image)
     *         - rule
     *     ]
     * - media : Array
     *     [
     *         - srcset: Array (same options as image)
     *         - rule
     *     ]
     * - sizes : Array
     *     [
     *         - "size1"
     *         - "size2"
     *     ]
     *  - picture: (false | true) Use picture tag to benefit from WebP
     *
     * ## Audio / Video options
     *
     * - autoplay
     * - loop
     * - controls
     * - custom_poster
     *
     * For videos, a poster can be set if you name a document after your video filename (without extension).
     *
     * @param array $options
     *
     * @return string|false HTML output
     *
     * @deprecated
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getDocumentByArray(array $options = [])
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        if (null === $this->document) {
            return false;
        }

        $this->documentUrlGenerator->setOptions($options);
        $this->documentUrlGenerator->setDocument($this->document);

        $assignation = [
            'document' => $this->document,
            'mimetype' => $this->document->getMimeType(),
            'isWebp' => $this->document->isWebp(),
            'url' => $this->documentUrlGenerator->getUrl($options['absolute']),
        ];

        $assignation['lazyload'] = $options['lazyload'];
        $assignation['lazyload_class'] = $options['lazyload_class'];
        $assignation['autoplay'] = $options['autoplay'];
        $assignation['loop'] = $options['loop'];
        $assignation['muted'] = $options['muted'];
        $assignation['controls'] = $options['controls'];
        $assignation['fallback'] = $options['fallback'];

        if ($options['width'] > 0) {
            $assignation['width'] = $options['width'];
        }
        if ($options['height'] > 0) {
            $assignation['height'] = $options['height'];
        }

        if (!empty($options['identifier'])) {
            $assignation['identifier'] = $options['identifier'];
            $assignation['id'] = $options['identifier'];
        }

        if (!empty($options['class'])) {
            $assignation['class'] = $options['class'];
        }

        if (null !== $options['loading']) {
            $assignation['loading'] = $options['loading'];
        }

        $assignation['alt'] = !empty($options['alt']) ? $options['alt'] : $this->document->getAlternativeText();

        if ($options['embed'] &&
            $this->isEmbedPlatformSupported()) {
            return $this->getEmbedByArray($options);
        } elseif ($this->document->isSvg()) {
            try {
                /** @var Packages $packages */
                $packages = $this->getPackages();
                $asObject = !$options['inline'];
                $viewer = new SvgDocumentViewer(
                    $packages->getDocumentFilePath($this->document),
                    $assignation,
                    $asObject,
                    $packages->getUrl($this->document->getRelativePath() ?? '', Packages::DOCUMENTS)
                );
                return $viewer->getContent();
            } catch (FileNotFoundException $e) {
                return false;
            }
        } elseif ($this->document->isImage() && $options['picture'] === true) {
            $assignation['sizes'] = $this->parseSizes($options);
            $assignation['srcset'] = $this->parseSrcSet($options);
            $assignation['webp_srcset'] = $this->parseSrcSet($options, true);
            return $this->twig->render($this->getTemplatesBasePath() . '/picture.html.twig', $assignation);
        } elseif ($this->document->isImage() && $options['picture'] === false) {
            $assignation['srcset'] = $this->parseSrcSet($options);
            $assignation['sizes'] = $this->parseSizes($options);
            return $this->twig->render($this->getTemplatesBasePath() . '/image.html.twig', $assignation);
        } elseif ($this->document->isVideo()) {
            $assignation['sources'] = $this->getSourcesFiles();

            /*
             * Use a user defined poster url
             */
            if (!empty($options['custom_poster'])) {
                $assignation['custom_poster'] = trim(strip_tags($options['custom_poster']));
            } else {
                /*
                 * Look for poster with the same args as the video.
                 */
                $assignation['poster'] = $this->getPosterFile($options, $options['absolute']);
            }
            return $this->twig->render($this->getTemplatesBasePath() . '/video.html.twig', $assignation);
        } elseif ($this->document->isAudio()) {
            $assignation['sources'] = $this->getSourcesFiles();
            return $this->twig->render($this->getTemplatesBasePath() . '/audio.html.twig', $assignation);
        } elseif ($this->document->isPdf()) {
            return $this->twig->render($this->getTemplatesBasePath() . '/pdf.html.twig', $assignation);
        } else {
            return 'document.format.unknown';
        }
    }

    /**
     * @return bool
     */
    public function isEmbedPlatformSupported()
    {
        if (null !== $this->document &&
            $this->document->isEmbed() &&
            in_array(
                $this->document->getEmbedPlatform(),
                array_keys($this->documentPlatforms)
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return AbstractEmbedFinder|false
     */
    public function getEmbedFinder()
    {
        if (null === $this->embedFinder) {
            if (null !== $this->document && $this->isEmbedPlatformSupported()) {
                $class = $this->documentPlatforms[$this->document->getEmbedPlatform()];
                $this->embedFinder = new $class($this->document->getEmbedId());
            } else {
                $this->embedFinder = false;
            }
        }

        return $this->embedFinder;
    }

    /**
     * Output an external media with an iframe according to the arguments array.
     *
     * @param array $options
     *
     * @return string|false
     * @see \RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder::getIFrame
     */
    protected function getEmbedByArray(array $options = [])
    {
        try {
            if (null !== $this->document && $this->isEmbedPlatformSupported()) {
                $finder = $this->getEmbedFinder();
                return $finder ? $finder->getIFrame($options) : false;
            } else {
                return false;
            }
        } catch (InvalidEmbedId $e) {
            return '<p>' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Get sources files formats for audio and video documents.
     *
     * This method will search for document which filename is the same
     * except the extension. If you choose an MP4 file, it will look for a OGV and WEBM file.
     *
     * @return array|false
     */
    protected function getSourcesFiles()
    {
        if (null === $this->document) {
            return false;
        }
        $basename = pathinfo($this->document->getFilename());
        $basename = $basename['filename'];

        $sources = [];

        if ($this->document->isVideo()) {
            $sourcesDocsName = [
                $basename . '.ogg',
                $basename . '.ogv',
                $basename . '.mp4',
                $basename . '.mov',
                $basename . '.webm',
            ];
        } elseif ($this->document->isAudio()) {
            $sourcesDocsName = [
                $basename . '.mp3',
                $basename . '.ogg',
                $basename . '.wav',
            ];
        } else {
            return false;
        }

        $sourcesDocs = $this->getDocumentsByFilenames($sourcesDocsName);

        /** @var DocumentInterface $source */
        foreach ($sourcesDocs as $source) {
            $sources[$source->getMimeType()] = [
                'mime' => $source->getMimeType(),
                'url' => $this->getPackages()->getUrl($source->getRelativePath() ?? '', Packages::DOCUMENTS),
            ];
        }

        krsort($sources);

        return $sources;
    }

    /**
     * @param array $options
     * @param bool $absolute
     * @return array|false
     */
    protected function getPosterFile($options = [], $absolute = false)
    {
        if (null !== $this->document && $this->document->isVideo()) {
            $basename = pathinfo($this->document->getFilename());
            $basename = $basename['filename'];

            $sourcesDocsName = [
                $basename . '.jpg',
                $basename . '.gif',
                $basename . '.png',
                $basename . '.jpeg',
                $basename . '.webp',
            ];

            $sourcesDoc = $this->getOneDocumentByFilenames($sourcesDocsName);

            if (null !== $sourcesDoc && $sourcesDoc instanceof DocumentInterface) {
                $this->documentUrlGenerator->setOptions($options);
                $this->documentUrlGenerator->setDocument($sourcesDoc);
                return [
                    'mime' => $sourcesDoc->getMimeType(),
                    'url' => $this->documentUrlGenerator->getUrl($absolute),
                ];
            }
        }

        return false;
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return bool
     * @deprecated
     */
    public function supports(DocumentInterface $document, array $options): bool
    {
        return true;
    }

    /**
     * Down compatibility method.
     *
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @deprecated
     */
    public function render(DocumentInterface $document, array $options): string
    {
        $this->setDocument($document);
        return $this->getDocumentByArray($options) ?: '';
    }
}
