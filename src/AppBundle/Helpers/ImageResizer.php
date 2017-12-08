<?php
namespace AppBundle\Helpers;

use Symfony\Component\Filesystem\Filesystem;

class ImageResizer
{
    const RESIZE_PATH = 'uploads/resized/';

    /**
     * Resize an image
     *
     * @param string $image (The full image path with filename and extension)
     * @param string $newPath (The new path to where the image needs to be stored)
     * @param int $height (The new height to resize the image to)
     * @param int $width (The new width to resize the image to)
     * @return string (The new path to the reized image)
     */
    public static function resizeImage($image, $height = 0, $width = 0, $proportional = true)
    {

        // Get current dimensions
        $imageDetails = self::getImageDetails($image);
        $name = $imageDetails->name;
        $heightOrig = $imageDetails->height;
        $widthOrig = $imageDetails->width;
        $fileExtention = $imageDetails->extension;
        $ratio = $imageDetails->ratio;
        $jpegQuality = 100;
        //Resize dimensions are bigger than original image, stop processing
        if ($width > $widthOrig && $height > $heightOrig) {
            return $image;
        }

        if($proportional) {
            if ($height > 0) {
                $width = $height * $ratio;
            } else if ($width > 0) {
                $height = $width / $ratio;
            }
            $width = round($width);
            $height = round($height);
        }

        $gdImageDest = imagecreatetruecolor($width, $height);
        $gdImageSrc = null;
        switch ($fileExtention) {
            case 'png' :
                $gdImageSrc = imagecreatefrompng($image);
                imagealphablending($gdImageDest, false);
                imagesavealpha($gdImageDest, true);
                break;
            case 'jpeg':
            case 'jpg':
                $gdImageSrc = imagecreatefromjpeg($image);
                break;
            case 'gif' :
                $gdImageSrc = imagecreatefromgif($image);
                break;
            default:
                break;
        }

        imagecopyresampled($gdImageDest, $gdImageSrc, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);

        $filesystem = new Filesystem();
        $filesystem->mkdir(self::RESIZE_PATH, 0744);
        $newFileName = self::RESIZE_PATH . $name . "_" . $width . "x" . $height . "." . $fileExtention;

        switch ($fileExtention) {
            case 'png' :
                imagepng($gdImageDest, $newFileName);
                break;
            case 'jpeg' :
            case 'jpg' :
                imagejpeg($gdImageDest, $newFileName, $jpegQuality);
                break;
            case 'gif' :
                imagegif($gdImageDest, $newFileName);
                break;
            default:
                break;
        }

        return $newFileName;
    }

    /**
     *
     * Gets image details such as the extension, sizes and filename and returns them as a standard object.
     *
     * @param $imageWithPath
     * @return \stdClass
     */
    private static function getImageDetails($imageWithPath)
    {
        $size = getimagesize($imageWithPath);

        $imgParts = explode("/", $imageWithPath);
        $lastPart = $imgParts[count($imgParts) - 1];

        if (stristr("?", $lastPart)) {
            $lastPart = substr($lastPart, 0, stripos("?", $lastPart));
        }
        if (stristr("#", $lastPart)) {
            $lastPart = substr($lastPart, 0, stripos("#", $lastPart));
        }

        $dotPos = stripos($lastPart, ".");
        $name = substr($lastPart, 0, $dotPos);
        $extension = substr($lastPart, $dotPos + 1);

        $details = new \stdClass();
        $details->height = $size[1];
        $details->width = $size[0];
        $details->ratio = $size[0] / $size[1];
        $details->extension = $extension;
        $details->name = $name;

        return $details;
    }
}