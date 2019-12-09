<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use Intervention\Image\Image;

class AverageColorResolver
{
    public function getAverageColor(Image $image): string
    {
        $colorArray = $this->getAverageColorAsArray($image);
        return sprintf(
            '#%02x%02x%02x',
            $colorArray[0],
            $colorArray[1],
            $colorArray[2]
        );
    }
    /**
     * @param Image $image
     *
     * @return array
     */
    public function getAverageColorAsArray(Image $image): array
    {
        $image->resize(1, 1);
        return $image->pickColor(0, 0);
    }
}
