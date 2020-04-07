<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Skin;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Entity\Food;
use App\Entity\History;
use App\Entity\User;
use App\Repository\SkinRepository;
use App\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserController extends AbstractController {

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * UserController constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/user", name="/user", methods={"GET", "POST"})
     */
    public function index(){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $skins = $this->getDoctrine()->getRepository(Skin::class)->findAll();
        return $this->render('pages/user/user.html.twig', ['user' => $user, 'skins' => $skins]);
    }

    /**
     * @Route("/user/delete_account", name="/user/delete_account", methods={"GET", "POST", "DELETE"})
     * @param AuthorizationCheckerInterface $authChecker
     * @return RedirectResponse
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


    /**
     * @Route("/user/skin/{id}", name="/user/skin", methods={"GET", "PATCH"})
     * @return RedirectResponse
     * @param $id
     */
    public function change_skin($id)
    {
        $skin = $this->getDoctrine()->getRepository(Skin::class)->find($id);
        $username = $this->getUser()->getUsername();
        dump($username);
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["email" => $username]);
        dump($user);
        $user->setSkin($skin);
        $this->userRepository->save($user);

        return new Response();
    }
}