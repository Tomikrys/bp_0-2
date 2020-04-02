<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\History;
use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
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
        if ($this->getUser()) {
             return $this->redirectToRoute('menu');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/settings/initialize", methods={"GET", "POST"})
     * @param $user
     * @return Response
     */
    public function default_settings($user) {
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);
        if (!$settings) {
            $settings = new Settings();
        }
        $days = [["Pondělí", "Monday"], ["Úterý", "Tuesday"], ["Středa", "Wednesday"], ["Čtvrtek", "Thursday"], ["Pátek", "Friday"]];
        $meals = ["Polévka", "Hlavní chod"];
        $settings->setDays($days);
        $settings->setMeals($meals);
        $settings->setUser($user);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();

        $types = ["polévka", "jídlo", "salát"];
        foreach ($types as $type) {
            $new_type = new Type();
            $new_type->setName($type);
            $new_type->setUser($user);
            $this->getDoctrine()->getManager()->persist($new_type);
            $this->getDoctrine()->getManager()->flush();
        }

        $tags = ["vege", "spicy", "chicken", "pork", "beef", "fish"];
        foreach ($tags as $tag) {
            $new_tag = new Tag();
            $new_tag->setName($tag);
            $new_tag->setUser($user);
            $this->getDoctrine()->getManager()->persist($new_tag);
            $this->getDoctrine()->getManager()->flush();
        }

        return new Response();
    }

    /**
     * @param $user
     * @Route("/settings/default_foods", methods={"GET", "POST"})
     */
    public function fill_default_foods($user) {
        $this->add_new_food("Česneková polévka", "Se sýrem a opečeným chlebem",
            "20", "polévka", ["vege"], $user);
        $this->add_new_food("Kuřecí vývar", "Poctivý český vývar, bez bujónu",
            "20", "polévka", ["chicken"], $user);
        $this->add_new_food("Vepřové kostky po myslivecku", "Podávané s opékanými bramborami a tatarkou",
            "99", "jídlo", ["pork", "spicy"], $user);
        $this->add_new_food("Grilovaný vepřový steak na žampionech", "Podávané s hranolkami a smetanovým dipem",
            "99", "jídlo", ["pork", "spicy"], $user);
        $this->add_new_food("Grilovaný vepřový steak na žampionech", "Podávané s hranolkami a smetanovým dipem",
            "99", "jídlo", ["pork", "spicy"], $user);
        $this->add_new_food("Tagliatelle s kuřecím ragů", "Dlouhé ploché těstoviny, sypané parmezánem",
            "99", "jídlo", ["chicken", "spicy"], $user);
        $this->add_new_food("Čerstvý zeleninový salát", "S anglickým roasbeefem a kaparovým dresinkem, kaiserka",
            "125", "salát", ["beef"], $user);
        $this->add_new_food("Salát s tuňákem", "Zelenina, tuňák, vejce a smetanovým dresing, kaiserka",
            "125", "salát", ["fish"], $user);
    }

    /**
     * @param $user
     * @Route("/settings/history_record", methods={"GET", "POST"})
     * @throws \Exception
     */
    public function add_random_history($user) {
        $history = new History();
        $history->setUser($user);
        // Kvůli posunutí časových zon, jinak to dá pondělí
        $next_monday = strtotime(date('d-m-Y', strtotime('next week Tuesday')));
        $date = new DateTime("@" . intval($next_monday));
        $history->setDateFrom($date);
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);

        $foodRepository = $this->getDoctrine()->getRepository(Food::class);
        $typeRepository = $this->getDoctrine()->getRepository(Type::class);
        $type = $typeRepository->findOneBy(['user' => $user,'name' => 'polévka']);
        $soups = $foodRepository->findBy(['user' => $user,'type' => $type]);
        $type = $typeRepository->findOneBy(['user' => $user,'name' => 'jídlo']);
        $main = $foodRepository->findBy(['user' => $user,'type' => $type]);
        $type = $typeRepository->findOneBy(['user' => $user,'name' => 'salát']);
        $salads = $foodRepository->findBy(['user' => $user,'type' => $type]);

        $data = [];
        foreach ($settings->getDays() as $day) {
            $data_day["day"] = $day[0];
            $data_day["description"] = $day[1];
            $data_day["meals"] = [];
            $data_meal["meals"] = [];
            foreach ($settings->getMeals() as $meal) {
               $data_meal["type"] = $meal;
               $meals = [];
               if ($meal == 'Polévka') {
                   $randIndex = array_rand($soups);
                   $meals = [["id" => $soups[$randIndex]->getId()]];
               } else {
                   $randIndex1 = array_rand($main, 2);
                   $randIndex2 = array_rand($salads);
                   $meals = [["id" => $main[$randIndex1[0]]->getId()],
                             ["id" => $main[$randIndex1[1]]->getId()],
                             ["id" => $salads[$randIndex2]->getId()]];
               }
               $data_meal["meals"] = $meals;
               array_push($data_day["meals"], $data_meal);
            }
            //$data_day["meals"] = $data_meal;
            array_push($data, $data_day);
        }

        $history->setJson($data);
        $this->getDoctrine()->getManager()->persist($history);
        $this->getDoctrine()->getManager()->flush();
    }


    public function add_new_food($name, $description, $price, $type, $tags, $user) {
        $sample_food = new Food();
        $sample_food->setName($name);
        $sample_food->setDescription($description);
        $sample_food->setPrice($price);
        $entityManager = $this->getDoctrine()->getManager();
        $sample_food->setTypeByString($type, $entityManager, $user);
        foreach ($tags as $tag_string) {
            $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'name' => $tag_string]);
            if ($tag) {
                $sample_food->addTag($tag);
            } else {
                $this->addFlash('error', 'Tag \'' . $tag_string . '\' nenalezen.');
            }
        }
        $sample_food->setUser($user);
        $this->getDoctrine()->getManager()->persist($sample_food);
        $this->getDoctrine()->getManager()->flush();
    }


    /**
     * @Route("/settings/default_templates", methods={"GET", "POST"})
     * @param $user
     * @param FileUploader $uploader
     */
    public function add_default_templates($user, FileUploader $uploader) {
        $clean_username = $user->getCleanUsername();
        $filesystem = new Filesystem();

        $filesystem->mkdir('words/' . $clean_username);

        $path = 'words/' . $clean_username . '/template.docx';
        $filesystem->copy('words/template.docx', $path,
            true);
        $this->add_new_template("menu", "template.docx", $user);
        $uploader->aws_upload($path);

        $path = 'words/' . $clean_username . '/zomato.docx';
        $filesystem->copy('words/zomato.docx', $path,
            true);
        $this->add_new_template("zomato", "zomato.docx", $user);
        $uploader->aws_upload($path);

        $path = 'words/' . $clean_username . '/facebook.docx';
        $filesystem->copy('words/facebook.docx', $path,
            true);
        $this->add_new_template("facebook", "facebook.docx", $user);
        $uploader->aws_upload($path);

        $path = 'words/' . $clean_username . '/webovky.docx';
        $filesystem->copy('words/webovky.docx', $path,
            true);
        $this->add_new_template("web", "webovky.docx", $user);
        $uploader->aws_upload($path);

    }

    public function add_new_template($name, $path, $user) {
        $template = new Template();
        $template->setName($name);
        $template->setPath($path);
        $template->setUser($user);

        $this->getDoctrine()->getManager()->persist($template);
        $this->getDoctrine()->getManager()->flush();
    }

    public function initialize_settings($user) {
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
     * @param Request $request
     * @param FileUploader $uploader
     * @return Response
     * @throws \Exception
     */
    public function register(AuthenticationUtils $authenticationUtils, Request $request, FileUploader $uploader): Response
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
                $this->initialize_settings($user);

                $this->default_settings($user);
                $this->fill_default_foods($user);
                $this->add_random_history($user);
                $this->add_default_templates($user, $uploader);

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
     */
    public function logout()
    {
     //   throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
