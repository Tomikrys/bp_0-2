<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class SettingsController extends AbstractController {

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
        return $this->render('pages/settings/settings.html.twig', array('settings' => $settings, 'types' => $types, 'tags' => $tags,
            'templates' => $templates));
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
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
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
        $type = $this->getDoctrine()->getRepository(Tag::class)->find($id);
        if ($user != $type->getUser()) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
            exit;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($type);
        $entityManager->flush();
        $this->addFlash('warning', 'Tag byl smazán.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/tag", methods={"GET", "POST"})
     */
    public function edit_tag() {
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

        $tag->setName($data->name);

        $this->getDoctrine()->getManager()->persist($tag);
        $entityManager->flush();
        $this->addFlash('success', 'Tag byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/template", methods={"GET", "POST"})
     */
    public function edit_template() {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $template = $entityManager->getRepository(Template::class)->find($id);
        if ($user != $template->getUser()) {
            //TODO Error
            $this->addFlash('warning', 'Neoprávněný přístup.');
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
     * @Route("/settings/edit/type", methods={"GET", "POST"})
     */
    public function edit_type() {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $type = $entityManager->getRepository(Type::class)->find($id);
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
        $this->addFlash('success', 'Type byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

}