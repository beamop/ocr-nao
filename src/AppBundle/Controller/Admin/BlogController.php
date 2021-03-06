<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Comment;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Post;
use AppBundle\Entity\Observation;
use AppBundle\Form\PostType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class BlogController
 *
 * @Route("/gestion/blog")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @package AppBundle\Controller\Admin
 */
class BlogController extends Controller
{

    /**
     * @Route("/comment", name="admin_comment")
     */
    public function listeCommentaireAction()
    {
        $observationsOnHold = $this->getDoctrine()
            ->getRepository(Observation::class)
            ->findAllNoValidatedBirds();

        $commentsOnHold = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->findAllNoValidatedComments();

        $comments = $this->getDoctrine()->getManager()
            ->getRepository(Comment::class)
            ->findAll();

        return $this->render('naouser/admin/blog/comment.html.twig', array(
            'observationsOnHold' => $observationsOnHold,
            'commentsOnHold' => $commentsOnHold,
            'comments' => $comments
        ));
    }

    /**
     * Lister les articles
     *
     * @Route("/", name="admin_blog")
     */
    public function indexAction(Request $request)
    {
        $observationsOnHold = $this->getDoctrine()
            ->getRepository(Observation::class)
            ->findAllNoValidatedBirds();

        $commentsOnHold = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->findAllNoValidatedComments();

        $articles = $this->getDoctrine()
            ->getRepository(Post::class)
            ->findBy(
                array(),
                array('createdAt' => 'DESC'),
                10,
                0
            );

        return $this->render('naouser/admin/blog/liste.html.twig', array(
            'observationsOnHold' => $observationsOnHold,
            'commentsOnHold' => $commentsOnHold,
            'articles' => $articles
        ));
    }

    /**
     * Ajouter article
     *
     * @Route("/ajouter", name="admin_post_ajouter")
     * @Method({"GET","POST"})
     *
     * @param Request $request
     * @return mixed
     */
    public function ajouterAction(Request $request)
    {
        $observationsOnHold = $this->getDoctrine()
            ->getRepository(Observation::class)
            ->findAllNoValidatedBirds();

        $commentsOnHold = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->findAllNoValidatedComments();

        $em = $this->getDoctrine()->getManager();

        $post = new Post();

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var UploadedFile $image
             */
            $image = $post->getImage();

            if ($image instanceof UploadedFile) {
                $nom_image = $this->generateUniqueFilename().'.'.$image->guessExtension();

                $image->move(
                    $this->getParameter('upload_blog_directory'),
                    $nom_image
                );

                $post->setImage($nom_image);
            } else {
                $post->setImage('nao_blog_default.jpeg');
            }

            if ($post->getStatus()===$post::PUBLISHED || $post->getStatus()===$post::FEATURED) {
                $post->setPublishedAt(new \DateTime());
            }

            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('nao_blog');
        }

        return $this->render('naouser/admin/blog/ajouter.html.twig', array(
            'observationsOnHold' => $observationsOnHold,
            'commentsOnHold' => $commentsOnHold,
            'form' => $form->createView(),
            'bouton' => 'Ajouter'
        ));
    }

    /**
     * Editer article
     *
     * @Route("/edit/{id}", name="admin_post_editer", requirements={"id": "\d+"})
     * @Method({"GET","POST"})
     *
     * @param Request $request
     * @return mixed
     */
    public function modifierAction(Request $request, $id)
    {
        $observationsOnHold = $this->getDoctrine()
            ->getRepository(Observation::class)
            ->findAllNoValidatedBirds();

        $commentsOnHold = $this->getDoctrine()
            ->getRepository(Comment::class)
            ->findAllNoValidatedComments();

        $em = $this->getDoctrine()->getManager();

        $post = $em
            ->getRepository(Post::class)
            ->find($id);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var UploadedFile $image
             */
            $image = $post->getImage();

            if ($image instanceof UploadedFile) {
                $nom_image = $this->generateUniqueFilename().'.'.$image->guessExtension();

                $image->move(
                    $this->getParameter('upload_blog_directory'),
                    $nom_image
                );

                $post->setImage($nom_image);
            }

            if ($post->getStatus()===$post::PUBLISHED || $post->getStatus()===$post::FEATURED) {
                $post->setPublishedAt(new \DateTime());
            }
            $em->flush();

            $this->addFlash("success", "L'article a bien été modifié");

            return $this->redirectToRoute('admin_blog');
        }

        return $this->render('naouser/admin/blog/modifier.html.twig', array(
            'observationsOnHold' => $observationsOnHold,
            'commentsOnHold' => $commentsOnHold,
            'form' => $form->createView()
        ));
    }

    /**
     * Mettre à jour le statut de l'article
     *
     * @Route("/a/status/{id}/{status}", name="admin_blog_status", requirements={"id": "\d+", "status": "\d+"})
     * Method({"GET"})
     */
    public function statusArticleAction(Request $request, $id, $status)
    {
        $em = $this->getDoctrine()->getManager();

        $post = $em
            ->getRepository(Post::class)
            ->find($id);

        $oldStatus = $post->getStatus();

        $post->setStatus($status);

        if ( $oldStatus === null && ($post->getStatus()===$post::PUBLISHED || $post->getStatus()===$post::FEATURED) ) {
            $post->setPublishedAt(new \DateTime());
        }

        $em->flush();

        $this->addFlash("success", "Le statut de l'article a bien été mis à jour.");

        return $this->redirectToRoute('admin_blog');
    }

    /**
     * Mettre à jour le statut du commentaire
     *
     * @Route("/c/status/{id}/{status}", name="admin_comment_status", requirements={"id": "\d+", "status": "\d+"})
     * Method({"GET"})
     */
    public function statusCommentAction(Request $request, $id, $status)
    {
        $em = $this->getDoctrine()->getManager();

        $comment = $em
            ->getRepository(Comment::class)
            ->find($id);

        $oldStatus = $comment->getStatus();

        $comment->setStatus($status);

        if ( $oldStatus === null && ($comment->getStatus()===$comment::PUBLISHED || $comment->getStatus()===$comment::PENDING) ) {
            $comment->setPublishedAt(new \DateTime());
        }

        $em->flush();

        $this->addFlash("success", "Le statut du commentaire a bien été mis à jour.");

        return $this->redirectToRoute('admin_comment');
    }

    /**
     * @Route("/supprimer/{id}", name="admin_blog_supprimer", requirements={"id": "\d+"})
     * Method({"GET"})
     */
    public function supprimerAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $post = $em
            ->getRepository(Post::class)
            ->find($id);

        $em->remove($post);
        $em->flush();

        $this->addFlash("success", "L'article a bien été supprimé");

        return $this->redirectToRoute('admin_blog');
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}