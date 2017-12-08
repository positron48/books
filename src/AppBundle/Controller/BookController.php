<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Book;
use AppBundle\Form\BookType;
use JMS\Serializer\Expression\ExpressionEvaluator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;


class BookController extends Controller
{

    /**
     * @Route("/", name="books")
     */
    public function indexAction(Request $request)
    {
        $books = $this->get('app.books_worker')->getBooksList();
        return $this->render('default/index.html.twig', ['books' => $books]);
    }

    /**
     * @Route("/book/new/", name="new_book")
     */
    public function newAction(Request $request)
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findBy(array('name' => $book->getName()));
            if (!empty($books)) {
                return $this->render('default/books.new.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'library.books.exists'
                ]);
            }

            $this->get('app.books_worker')->saveBook($book);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }

    /**
     * @Route("/book/edit/{id}/", name="edit_book")
     */
    public function editAction(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        if (empty($book)) {
            return $this->render('errors/404.html.twig');
        } else {
            $filePath = $book->getFile();
            if ($filePath) {
                try {
                    $file = new File($this->container->getParameter('absolute_dir') . '/' . $filePath);
                    $book->setFile($file);
                } catch (FileException $ex) {
                    $book->setFile(null);
                }
            } else {
                $book->setFile(null);
            }

            $coverPath = $book->getCover();
            if ($coverPath) {
                try {
                    $file = new File($this->container->getParameter('absolute_dir') . '/' . $coverPath);
                    $book->setCover($file);
                } catch (FileException $ex) {
                    $book->setCover(null);
                }
            } else {
                $book->setCover(null);
            }
        }

        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //если ничего не менялось - файл пуст, а служебное поле заполнено - устанавливаем текущие данные книги
            $coverTitle = $request->get('cover_title');
            if (!$book->getCover() instanceof UploadedFile && !empty($coverTitle)) {
                $book->setCover($coverPath);
            }

            $fileTitle = $request->get('file_title');
            if (!$book->getFile() instanceof UploadedFile && !empty($fileTitle)) {
                $book->setFile($filePath);
            }

            $this->get('app.books_worker')->saveBook($book);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }

    /**
     * @Route("/book/delete/{id}/", name="delete_book")
     */
    public function deleteAction(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        $this->get('app.books_worker')->deleteBook($book);

        return $this->redirectToRoute('books');
    }
}
