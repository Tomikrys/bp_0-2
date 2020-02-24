<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * UserRepository constructor.
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }



    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function default_settings($user) {
        $settings = new Settings();
        $days = [];
        $meals = [];
        $settings->setDays($days);
        $settings->setMeals($meals);
        $settings->setUser($user);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @Route("/register", name="register")
     * @param AuthenticationUtils $authenticationUtils
     * @param $request
     * @return Response
     */
    public function register(AuthenticationUtils $authenticationUtils,Request $request): Response
    {
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();

        $formadd = $this->createFormBuilder($user)
            ->add('email', EmailType::class, array(
                'label' => 'Email',
                'attr' => array('class' => 'form-control')
            ))
            ->add('password', PasswordType::class, array(
                'label' => 'Heslo',
                'attr' => array('class' => 'form-control')
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Registrovat',
                'attr' => array('class' => 'btn btn btn-success mt-3')))
            ->getForm();

        // Zpracování add formuláře.
        $formadd->handleRequest($request);
        if ($formadd->isSubmitted()) {
            if ($formadd->isValid()) {
                $user->setRoles(["ROLE_CAPTAIN"]);
                $user->setPassword($this->passwordEncoder->encodePassword(
                    $user,
                    $user->getPassword()
                ));
                try {
                    $this->userRepository->save($user);
                } catch (UniqueConstraintViolationException $e) {
                    $this->addFlash('error', 'Uživatel s emailovou adresou \'' . $user->getEmail() . '\' již existuje.');
                    return $this->redirect($this->generateUrl('register'));
                }
                $this->default_settings($user);
                $this->addFlash('success', 'Uživatel \'' . $user->getEmail() . '\' byl úspěšně přidán.');
                $this->addFlash('info', 'Prosím, přihlaste se.');
                return $this->redirect($this->generateUrl('app_login'));
            } else {
                $this->addFlash('error', 'Uživatel nemohl být přidán, špatně vyplněný formulář.');
            }
        }

        return $this->render('security/register.html.twig', array('last_username' => $lastUsername, 'formadd' => $formadd->createView()));
    }

    /**
     * @Route("/logout", name="app_logout")
     * @throws \Exception
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
