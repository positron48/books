<?php
namespace AppBundle\Helpers;
use Symfony\Component\Filesystem\Filesystem;

class ImageResizer {
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
    public static function resizeImage($image, $height=0, $width=0){

        // Get current dimensions
        $ImageDetails = self::getImageDetails($image);
        $name = $ImageDetails->name;
        $height_orig = $ImageDetails->height;
        $width_orig = $ImageDetails->width;
        $fileExtention = $ImageDetails->extension;
        $ratio = $ImageDetails->ratio;
        $jpegQuality = 100;

        //Resize dimensions are bigger than original image, stop processing
        if ($width > $width_orig && $height > $height_orig){
            return false;
        }

        if($height > 0){
            $width = $height * $ratio;
        } else if($width > 0){
            $height = $width / $ratio;
        }
        $width = round($width);
        $height = round($height);

        $gd_image_dest = imagecreatetruecolor($width, $height);
        $gd_image_src = null;
        switch( $fileExtention ){
            case 'png' :
                $gd_image_src = imagecreatefrompng($image);
                imagealphablending( $gd_image_dest, false );
                imagesavealpha( $gd_image_dest, true );
                break;
            case 'jpeg': case 'jpg': $gd_image_src = imagecreatefromjpeg($image);
            break;
            case 'gif' : $gd_image_src = imagecreatefromgif($image);
                break;
            default: break;
        }

        imagecopyresampled($gd_image_dest, $gd_image_src, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        $filesystem = new Filesystem();
        $filesystem->mkdir(self::RESIZE_PATH, 0744);
        $newFileName = self::RESIZE_PATH . $name . "_".$width."x".$height . "." . $fileExtention;

        switch( $fileExtention ){
            case 'png' : imagepng($gd_image_dest, $newFileName); break;
            case 'jpeg' : case 'jpg' : imagejpeg($gd_image_dest, $newFileName, $jpegQuality); break;
            case 'gif' : imagegif($gd_image_dest, $newFileName); break;
            default: break;
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
    private static function getImageDetails($imageWithPath){
        $size = getimagesize($imageWithPath);

        $imgParts = explode("/",$imageWithPath);
        $lastPart = $imgParts[count($imgParts)-1];

        if(stristr("?",$lastPart)){
            $lastPart = substr($lastPart,0,stripos("?",$lastPart));
        }
        if(stristr("#",$lastPart)){
            $lastPart = substr($lastPart,0,stripos("#",$lastPart));
        }

        $dotPos     = stripos($lastPart,".");
        $name         = substr($lastPart,0,$dotPos);
        $extension     = substr($lastPart,$dotPos+1);

        $Details = new \stdClass();
        $Details->height    = $size[1];
        $Details->width        = $size[0];
        $Details->ratio        = $size[0] / $size[1];
        $Details->extension = $extension;
        $Details->name         = $name;

        return $Details;
    }
}