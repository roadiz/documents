<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Pimple\Container;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Console\Helper\Helper;

class AssetPackagesHelper extends Helper
{
    /**
     * @var Container
     */
    private $container;

    /**
     * AssetPackagesHelper constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Packages
     */
    public function getPackages()
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
