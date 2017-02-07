<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Author;
use AppBundle\Form\AuthorType;

class AuthorController extends Controller
{
    /**
     * @Route("/authors/", name="authors")
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Author');
        $authors = $repository->findAll();

        return $this->render('default/authors.html.twig', ['authors' => $authors]);
    }

    /**
     * @Route("/authors/new/", name="new_author")
     */
    public function newAction(Request $request)
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Author');
            $authors = $repository->findBy(array('name' => $author->getName()));
            if(!empty($authors)){
                return $this->render('default/authors.new.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'library.authors.exists'
                ]);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($author);
            $em->flush();

            return $this->redirectToRoute('authors');
        }

        return $this->render('default/authors.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }
}
