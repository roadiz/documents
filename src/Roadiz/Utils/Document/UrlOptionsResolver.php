<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UrlOptionsResolver
 * @package RZ\Roadiz\Utils\Document
 */
class UrlOptionsResolver extends OptionsResolver
{
    public function __construct()
    {
        $this->setDefaults([
            'crop' => null,
            'fit' => null,
            'align' => null,
            'background' => null,
            'absolute' => false,
            'grayscale' => false,
            'progressive' => false,
            'noProcess' => false,
            'interlace' => false,
            'width' => 0,
            'flip' => null,
            'height' => 0,
            'quality' => 90,
            'blur' => 0,
            'sharpen' => 0,
            'contrast' => 0,
            'rotate' => 0,
        ]);
        $this->setAllowedTypes('width', ['int']);
        $this->setAllowedTypes('height', ['int']);
        $this->setAllowedTypes('crop', ['null', 'string']);
        $this->setAllowedTypes('fit', ['null', 'string']);
        $this->setAllowedTypes('flip', ['null', 'string']);
        $this->setAllowedTypes('align', ['null', 'string']);
        $this->setAllowedValues('align', [
            null,
            'top-left',
            'top',
            'top-right',
            'left',
            'center',
            'right',
            'bottom-left',
            'bottom',
            'bottom-right',
        ]);
        $this->setAllowedTypes('background', ['null', 'string']);
        $this->setAllowedTypes('quality', ['int']);
        $this->setAllowedTypes('blur', ['int']);
        $this->setAllowedTypes('sharpen', ['int']);
        $this->setAllowedTypes('contrast', ['int']);
        $this->setAllowedTypes('rotate', ['int']);
        $this->setAllowedTypes('absolute', ['boolean']);
        $this->setAllowedTypes('grayscale', ['boolean']);
        $this->setAllowedTypes('progressive', ['boolean']);
        $this->setAllowedTypes('noProcess', ['boolean']);
        $this->setAllowedTypes('interlace', ['boolean']);

        /*
         * Guess width and height options from fit
         */
        $this->setDefault('width', function (Options $options) {
            $compositing = $options['crop'] ?? $options['fit'] ?? '';
            if (1 === preg_match('#(?<width>[0-9]+)[x:\.](?<height>[0-9]+)#', $compositing, $matches)) {
                return (int) $matches['width'];
            }
            return 0;
        });
        $this->setDefault('height', function (Options $options) {
            $compositing = $options['crop'] ?? $options['fit'] ?? '';
            if (1 === preg_match('#(?<width>[0-9]+)[x:\.](?<height>[0-9]+)#', $compositing, $matches)) {
                return (int) $matches['height'];
            }
            return 0;
        });
    }
}
