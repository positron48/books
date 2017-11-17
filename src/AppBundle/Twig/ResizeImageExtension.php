<?php
namespace AppBundle\Twig;
use AppBundle\Helpers\ImageResizer;

class ResizeImageExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('resizeImage', array($this, 'getResizedImageSRC')),
        );
    }

    public function getResizedImageSRC($imageSrc, $height = 0, $width = 0)
    {
        return ImageResizer::resizeImage($imageSrc, $height, $width);
    }
}