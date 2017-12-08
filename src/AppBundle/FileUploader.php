<?php
namespace AppBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirAbsolute;
    private $targetDir;

    public function __construct($absoluteUploadPath, $targetDir)
    {
        $this->targetDirAbsolute = $absoluteUploadPath . '/' . $targetDir;
        $this->targetDir = $targetDir;
    }

    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();

        $subDir = substr($fileName, 0, 2);
        $fileName = substr($fileName, 2, mb_strlen($fileName) - 2);

        $file->move($this->targetDirAbsolute . '/' . $subDir, $fileName);

        return $this->targetDir . '/' . $subDir . '/' . $fileName;
    }

    public function getAbsoluteTargetDir()
    {
        return $this->targetDirAbsolute;
    }
}