<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Asset;

use RZ\Roadiz\Core\Exceptions\DocumentWithoutFileException;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\Packages as BasePackages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @package RZ\Roadiz\Utils\Asset
 */
class Packages extends BasePackages
{
    /**
     * Absolute package is for reaching
     * resources at server root.
     */
    const ABSOLUTE = 'absolute';

    /**
     * Document package is for reaching
     * files with relative path to server root.
     */
    const DOCUMENTS = 'doc';

    /**
     * Document package is for reaching
     * files with absolute url with domain-name.
     */
    const ABSOLUTE_DOCUMENTS = 'absolute_doc';

    /**
     * Public path package is for internally reaching
     * public files with absolute path.
     * Be careful, this provides server paths.
     */
    const PUBLIC_PATH = 'public_path';

    /**
     * Private path package is for internally reaching
     * private files with absolute path.
     * Be careful, this provides server paths.
     */
    const PRIVATE_PATH = 'private_path';

    /**
     * Fonts path package is for internally reaching
     * font files with absolute path.
     * Be careful, this provides server paths.
     */
    const FONTS_PATH = 'fonts_path';

    private VersionStrategyInterface $versionStrategy;
    private RequestStack $requestStack;
    private FileAwareInterface $fileAware;
    private string $staticDomain;
    private RequestStackContext $requestStackContext;
    private bool $ready;

    /**
     * Build a new asset packages for Roadiz root and documents.
     *
     * @param VersionStrategyInterface $versionStrategy
     * @param RequestStack $requestStack
     * @param FileAwareInterface $fileAware
     * @param string $staticDomain
     */
    public function __construct(
        VersionStrategyInterface $versionStrategy,
        RequestStack $requestStack,
        FileAwareInterface $fileAware,
        string $staticDomain = ""
    ) {
        parent::__construct();
        $this->requestStackContext = new RequestStackContext($requestStack);
        $this->requestStack = $requestStack;
        $this->fileAware = $fileAware;
        $this->staticDomain = $staticDomain;
        $this->versionStrategy = $versionStrategy;
        $this->ready = false;
    }

    /**
     * Defer creating package collection not to create error
     * when warming up cache on dependency injection.
     * These packages need a valid Request object.
     */
    protected function initializePackages(): void
    {
        $this->setDefaultPackage($this->getDefaultPackage());
        $packages = [
            static::ABSOLUTE => $this->getAbsoluteDefaultPackage(),
            static::DOCUMENTS => $this->getDocumentPackage(),
            static::ABSOLUTE_DOCUMENTS => $this->getAbsoluteDocumentPackage(),
            static::PUBLIC_PATH => $this->getPublicPathPackage(),
            static::PRIVATE_PATH => $this->getPrivatePathPackage(),
            static::FONTS_PATH => $this->getFontsPathPackage(),
        ];
        foreach ($packages as $name => $package) {
            $this->addPackage((string) $name, $package);
        }
        $this->ready = true;
    }

    /**
     * @inheritDoc
     */
    public function getPackage($name = null)
    {
        if (false === $this->ready) {
            $this->initializePackages();
        }

        return parent::getPackage($name);
    }

    /**
     * @return bool
     */
    public function useStaticDomain()
    {
        return $this->staticDomain != "";
    }

    /**
     * @return string
     */
    protected function getStaticDomainAndPort()
    {
        /*
         * Add non-default port to static domain.
         */
        $staticDomainAndPort = $this->staticDomain;
        $request = $this->getRequest();
        if (null !== $request &&
            (($this->requestStackContext->isSecure() && $request->getPort() != 443) ||
            (!$this->requestStackContext->isSecure() && $request->getPort() != 80))) {
            $staticDomainAndPort .= ':' . $request->getPort();
        }

        /*
         * If no protocol, use https as default
         */
        if (!preg_match("~^//~i", $staticDomainAndPort) &&
            !preg_match("~^(?:f|ht)tps?://~i", $staticDomainAndPort)) {
            $staticDomainAndPort = "https://" . $staticDomainAndPort;
        }

        return $staticDomainAndPort;
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getDefaultPackage()
    {
        if ($this->useStaticDomain()) {
            return new UrlPackage(
                $this->getStaticDomainAndPort(),
                $this->versionStrategy
            );
        }

        return new PathPackage(
            '/',
            $this->versionStrategy,
            $this->requestStackContext
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getAbsoluteDefaultPackage()
    {
        if ($this->useStaticDomain()) {
            return $this->getDefaultPackage();
        }
        $scheme = '';
        if (null !== $this->getRequest()) {
            $scheme = $this->getRequest()->getSchemeAndHttpHost();
        }
        return new UrlPackage(
            $scheme . $this->requestStackContext->getBasePath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getDocumentPackage()
    {
        if ($this->useStaticDomain()) {
            return new UrlPackage(
                $this->getStaticDomainAndPort() . $this->fileAware->getPublicFilesBasePath(),
                $this->versionStrategy
            );
        }

        return new PathPackage(
            $this->fileAware->getPublicFilesBasePath(),
            $this->versionStrategy,
            $this->requestStackContext
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getAbsoluteDocumentPackage()
    {
        if ($this->useStaticDomain()) {
            return $this->getDocumentPackage();
        }
        $scheme = '';
        if (null !== $this->getRequest()) {
            $scheme = $this->getRequest()->getSchemeAndHttpHost();
        }
        return new UrlPackage(
            $scheme . $this->requestStackContext->getBasePath() . $this->fileAware->getPublicFilesBasePath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getPublicPathPackage()
    {
        return new PathPackage(
            $this->fileAware->getPublicFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getPrivatePathPackage()
    {
        return new PathPackage(
            $this->fileAware->getPrivateFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getFontsPathPackage()
    {
        return new PathPackage(
            $this->fileAware->getFontsFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::FONTS_PATH).
     *
     * @param string $relativePath
     * @return string
     */
    public function getFontsPath($relativePath)
    {
        return $this->getUrl($relativePath, static::FONTS_PATH);
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::PUBLIC_PATH).
     *
     * @param string $relativePath
     * @return string
     */
    public function getPublicFilesPath($relativePath)
    {
        return $this->getUrl($relativePath, static::PUBLIC_PATH);
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::PRIVATE_PATH).
     *
     * @param string $relativePath
     * @return string
     */
    public function getPrivateFilesPath($relativePath)
    {
        return $this->getUrl($relativePath, static::PRIVATE_PATH);
    }

    /**
     * @param DocumentInterface $document
     * @return string Document file absolute path according if document is private or not.
     * @throws DocumentWithoutFileException
     */
    public function getDocumentFilePath(DocumentInterface $document)
    {
        if (!$document->isLocal()) {
            throw new DocumentWithoutFileException($document);
        }
        if ($document->isPrivate()) {
            return $this->getPrivateFilesPath($document->getRelativePath() ?? '');
        }
        return $this->getPublicFilesPath($document->getRelativePath() ?? '');
    }

    /**
     * @param DocumentInterface $document
     * @return string Document folder absolute path according if document is private or not.
     * @throws DocumentWithoutFileException
     */
    public function getDocumentFolderPath(DocumentInterface $document)
    {
        if (!$document->isLocal()) {
            throw new DocumentWithoutFileException($document);
        }
        if ($document->isPrivate()) {
            return $this->getPrivateFilesPath($document->getFolder());
        }
        return $this->getPublicFilesPath($document->getFolder());
    }

    /**
     * @return string
     */
    public function getStaticDomain()
    {
        return $this->staticDomain;
    }

    /**
     * @param string $staticDomain
     * @return Packages
     */
    public function setStaticDomain($staticDomain)
    {
        $this->staticDomain = $staticDomain;
        return $this;
    }

    /**
     * @return null|Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getMasterRequest();
    }
}
