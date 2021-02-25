<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Pimple\Container;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Console\Helper\Helper;

final class AssetPackagesHelper extends Helper
{
    private Container $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Packages
     */
    public function getPackages(): Packages
    {
        return $this->container->offsetGet('assetPackages');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'assetPackages';
    }
}
