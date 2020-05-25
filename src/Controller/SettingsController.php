<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Settings;
use App\Entity\Skin;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Repository\FoodRepository;
use App\Repository\TagRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class SettingsController extends DefaultController {
    /**
     * @var FoodRepository
     */
    private $foodRepository;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    /**
     * FoodsController constructor.
     * @param FoodRepository $foodRepository
     * @param TagRepository $tagRepository
     * @param TypeRepository $typeRepository
     */
    public function __construct(FoodRepository $foodRepository, TagRepository $tagRepository, TypeRepository $typeRepository)
    {
        $this->foodRepository = $foodRepository;
        $this->tagRepository = $tagRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @Route("/settings", name="/settings", methods={"GET", "POST"})
     */
    public function index(){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        // naplnění struktury pro výpis tabulky
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);
        $types = $this->getDoctrine()->getRepository(Type::class)->findBy(['user' => $user]);
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);
        $templates = $this->getDoctrine()->getRepository(Template::class)->findBy(['user' => $user]);
//        dump($templates);
//        exit;

        $skins = $this->getDoctrine()->getRepository(Skin::class)->findAll();

        $xml_menu = $this->get_xml_link();

        $this->manage_flashes();
        return $this->render('pages/settings/settings.html.twig', ['settings' => $settings, 'types' => $types, 'tags' => $tags,
            'templates' => $templates, 'skins' => $skins, "xml_menu" => $xml_menu]);
    }

    /**
     * @return Response
     * @Route("/settings/save/days", methods={"GET", "POST"})
     */
    public function save_days() {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);
        if (!$settings) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        $json = file_get_contents('php://input');
        $days = json_decode ($json);
        $settings->setDays($days);
        //dump($days);

        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Dny byly upraveny.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @return Response
     * @Route("/settings/save/meals", methods={"GET", "POST"})
     */
    public function save_meals() {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        // TODO nemuye byt 2
        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);
        if (!$settings) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        $json = file_get_contents('php://input');
        $meals = json_decode ($json);
        $settings->setMeals($meals);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Jídla byla upravena.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/type/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_type(Request $request, $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $type = $this->getDoctrine()->getRepository(Type::class)->find($id);
        if ($user != $type->getUser()) {
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        if (!$type) {
            $this->addFlash('warning', 'Typ nebyl nalezen.');
            exit;
        }
        if (!$type->getFoods()->isEmpty()) {
            $this->addFlash('warning', 'Typ je přiřazen nějakým jídlům, nelze odstranit.');
            exit;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($type);
        $entityManager->flush();
        $this->addFlash('warning', 'Typ byl smazán.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/template/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_template(Request $request, $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $template = $this->getDoctrine()->getRepository(Template::class)->find($id);
        if ($user != $template->getUser()) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $filesystem = new Filesystem();
        //$filesystem->remove(['symlink', '/path/to/directory', 'activity.log']);
        $filesystem->remove($template->getRealPath());
        $entityManager->remove($template);
        $entityManager->flush();
        $this->addFlash('warning', 'Šablona byla smazána.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/tag/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_tag(Request $request, $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$tag) {
            $this->addFlash('warning', 'Tag nebyl nalezen.');
            exit;
        }
        $msg = 'Tag byl smazán.';
        if (!$tag->getFoods()->isEmpty()) {
            $msg = 'Tag byl smazán, jídla již nemají tento tag.';
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($tag);
        $entityManager->flush();
        $this->addFlash('warning', $msg);
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/tag", methods={"GET", "PATCH"})
     */
    public function edit_add_tag() {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $tag = $entityManager->getRepository(Tag::class)->find($id);

        if (!$tag) {
            $tag = new Tag();
            $tag->setUser($user);
        } else if ($user != $tag->getUser()) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        $name_without_new_line = preg_replace("/[\n\r]/"," ",$data->name);
        $tag->setName($name_without_new_line);

        $this->getDoctrine()->getManager()->persist($tag);
        $entityManager->flush();
        $this->addFlash('success', 'Tag byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/template", methods={"GET", "PATCH"})
     */
    public function edit_template() {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $template = $entityManager->getRepository(Template::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$template) {
            //TODO Error
            $this->addFlash('warning', 'Šablona nenalezena');
            exit;
        }

        if (!$template) {
            $template = new Template();
        }

        $template->setName($data->name);

        $this->getDoctrine()->getManager()->persist($template);
        $entityManager->flush();
        $this->addFlash('success', 'Šablona byla upravena.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/type", methods={"GET", "PATCH"})
     */
    public function edit_add_type() {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $type = $entityManager->getRepository(Type::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$type) {
            $type = new Type();
            $type->setUser($user);
        }

        if ($user != $type->getUser()) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }

        $type->setName($data->name);

        $this->getDoctrine()->getManager()->persist($type);
        $entityManager->flush();
        $this->addFlash('success', 'Typ byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/import", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function import_csv(Request $request) : Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        // Název|Popis|Cena|Typ|Tag|Tag
        $import = $request->getContent();

        // rozdeleni na radky
        $line_separator = "\r\n";
        $line = strtok($import, $line_separator);
        while ($line !== false) {

            $food = new Food();
            $food->setUser($user);

            // rozdeleni na zaznamy
            $arr = explode('|', $line);
            foreach ($arr as $i => $word) {
                switch ($i) {
                    //nazev
                    case 0:
                        $food->setName($word);
                        break;
                    //popis
                    case 1:
                        $food->setDescription($word);
                        break;
                    //cena
                    case 2:
                        $food->setPrice($word);
                        break;
                    //typ
                    case 3:
                        $type = $this->getDoctrine()->getRepository(Type::class)->findOneBy(['user' => $user, 'name' => $word]);
                        if ($type == []) {
                            $type = new Type();
                            $type->setName($word);
                            $type->setUser($this->getUser());
                            $this->typeRepository->save($type);
                            $type = $this->getDoctrine()->getRepository(Type::class)->findOneBy(['user' => $user, 'name' => $word]);
                        }
                        $food->setType($type);
                        break;
                    //tagy
                    default:
                        $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'name' => $word]);
                        if ($tag == []) {
                            $tag = new Tag();
                            $tag->setName($word);
                            $tag->setUser($this->getUser());
                            $this->tagRepository->save($tag);
                            $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'name' => $word]);
                        }
                        $food->addTag($tag);
                        break;
                }
            }
            //dump($food);
            $this->foodRepository->save($food);
            $entityManager->flush();
            //dump($line);

            $line = strtok($line_separator);
        }
        $this->addFlash('success', 'Import proběhl úspěšně.');
        return new Response();
    }
}