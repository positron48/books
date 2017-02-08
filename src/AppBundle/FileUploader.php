<?php
namespace AppBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDir;

    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();

        $subDir = substr($fileName, 0, 2);
        $fileName = substr($fileName, 2, mb_strlen($fileName)-2);

        $file->move($this->targetDir.'/'.$subDir, $fileName);

        return $subDir.'/'.$fileName;
    }
}