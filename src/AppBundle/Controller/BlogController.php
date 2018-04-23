<?php

namespace AppBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Post;
use AppBundle\Entity\Comment;
use AppBundle\Form\CommentType;

/**
 * Class BlogController
 *
 * @Route("/news")
 *
 * @package AppBundle\Controller
 */
class BlogController extends Controller
{
    /**
     * @Route("/", name="nao_blog")
     */
    public function indexAction(Request $request)
    {
        $articles = $this->getDoctrine()
            ->getRepository(Post::class)
            ->findBy(
                array('status' => array(Post::PUBLISHED, Post::FEATURED)),
                array('status' => 'DESC', 'publishedAt' => 'DESC'),
                10,
                0
            );

        return $this->render('nao/blog/index.html.twig', array(
            'articles' => $articles
        ));
    }

    /**
     * @Route("/detail/{slug}", name="nao_blog_details")
     * @param Request $request
     * @param $slug
     * @return Response
     */
    public function blogDetailsAction(Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        $article = $em
            ->getRepository(Post::class)
            ->findOneBy(
                array('slug' => $slug)
            );

        if (!$article) {
            throw $this->createNotFoundException('L\'article n\'existe pas !');
        }

        $id = $article->getId();

        $prevArticle = $em
            ->getRepository(Post::class)
            ->find($id-1);

        $nextArticle = $em
            ->getRepository(Post::class)
            ->find($id+1);

        // START Charge les commentaires validés
        $comments = $em
            ->getRepository(Comment::class)
            ->findBy(
                array('post' => $id, 'status' => Comment::PUBLISHED),
                array('createdAt' => 'DESC')
            );
        // END Charge les commentaires validés

        // START Ajout d'un commentaire
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setStatus(Comment::PENDING);
            $comment->setPost($article);

            $em->persist($comment);
            dump($em->flush());

            $this->addFlash("success", "Votre commentaire a été ajouté, il est en attente de validation.");

            return $this->redirectToRoute('nao_blog_details', array('slug' => $article->getSlug()) );
        }
        // END Ajout d'un commentaire

        return $this->render('nao/blog/details.html.twig', array(
            'article' => $article,
            'prevArticle' => $prevArticle,
            'nextArticle' => $nextArticle,
            'comments' => $comments,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/supprimer/{id}", name="nao_blog_post_supprimer")
     * Method({"GET", "POST"})
     */
    public function supprimerAction(Request $request, $id)
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($post);
        $entityManager->flush();

        $response = new Response();
        $response->send();

        return $this->redirectToRoute('nao_blog');
    }

}