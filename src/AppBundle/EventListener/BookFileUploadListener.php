<?php
// src/AppBundle/EventListener/BrochureUploadListener.php
namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use AppBundle\Entity\Book;
use AppBundle\FileUploader;


class BookFileUploadListener
{
    private $coverUploader;
    private $bookUploader;

    public function __construct(FileUploader $coverUploader, FileUploader $bookUploader)
    {
        $this->coverUploader = $coverUploader;
        $this->bookUploader = $bookUploader;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->uploadFiles($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->uploadFiles($entity);
    }

    private function uploadFiles($entity)
    {
        if (!$entity instanceof Book) {
            return;
        }

        $file = $entity->getFile();

        // only upload new files
        if ($file instanceof UploadedFile) {
            $fileName = $this->bookUploader->upload($file);
            $entity->setFile($fileName);
        }

        $cover = $entity->getCover();

        // only upload new files
        if ($cover instanceof UploadedFile) {
            $fileName = $this->coverUploader->upload($cover);
            $entity->setCover($fileName);
        }
    }

    /*public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Book) {
            return;
        }

        $fileName = $entity->getFile();
        $entity->setFile(new File($this->bookUploader->getAbsoluteTargetDir().'/'.$fileName));

        $fileName = $entity->getCover();
        $entity->setCover(new File($this->coverUploader->getAbsoluteTargetDir().'/'.$fileName));
    }*/
}