<?php
namespace AppBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirAbsolute;

    public function __construct($absoluteUploadPath, $targetDir)
    {
        $this->targetDirAbsolute = $absoluteUploadPath.'/'.$targetDir;
    }

    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();

        $subDir = substr($fileName, 0, 2);
        $fileName = substr($fileName, 2, mb_strlen($fileName)-2);

        $file->move($this->targetDirAbsolute.'/'.$subDir, $fileName);

        return $subDir.'/'.$fileName;
    }

    public function getAbsoluteTargetDir(){
        return $this->targetDirAbsolute;
    }
}