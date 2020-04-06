<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Entity\Food;
use App\Entity\History;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserController extends AbstractController {
    /**
     * @Route("/user", name="/user", methods={"GET", "POST"})
     */
    public function index(){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        return $this->render('pages/user/user.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/user/delete_account", name="/user/delete_account", methods={"GET", "POST", "DELETE"})
     * @param AuthorizationCheckerInterface $authChecker
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete_account(AuthorizationCheckerInterface $authChecker){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $foods = $this->getDoctrine()->getRepository(Food::class)->findBy(['user' => $user]);
        $histories = $this->getDoctrine()->getRepository(History::class)->findBy(['user' => $user]);
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findBy(['user' => $user]);
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);
        $templates = $this->getDoctrine()->getRepository(Template::class)->findBy(['user' => $user]);
        $types = $this->getDoctrine()->getRepository(Type::class)->findBy(['user' => $user]);

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($foods as $food) { $entityManager->remove($food); }
        foreach ($histories as $history) { $entityManager->remove($history); }
        foreach ($settings as $setting) { $entityManager->remove($setting); }
        foreach ($tags as $tag) { $entityManager->remove($tag); }
        foreach ($templates as $template) { $entityManager->remove($template); }
        foreach ($types as $type) { $entityManager->remove($type); }

        $session = $this->get('session');
        $session = new Session();
        $session->invalidate();

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('index');
    }
}