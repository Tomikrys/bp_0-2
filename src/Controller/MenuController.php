<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Service\FileUploader;
use http\Exception;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException as CreateTemporaryFileExceptionAlias;
use PhpOffice\PhpWord\TemplateProcessor;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class MenuController extends AbstractController {
    /**
     * @Route("/menu", name="menu", methods={"GET", "POST"})
     */
    public function index(){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        // naplnění struktury pro výpis tabulky
        $types = $this->getDoctrine()->getRepository(Type::class)->findBy(['user' => $user]);
        $foods = null;
        $type = null;
        foreach ($types as $type) {
            $foods_of_type = $type->getFoods();
            // TODO useless??
            foreach ($foods_of_type as $food) {
                if ($food->getUser() != $user) {
                    break;
                }
            }
            $foods[$type->getName()] = $foods_of_type;
        }

        $settings = $this->getDoctrine()->getRepository(Settings::class)->findOneBy(['user' => $user]);
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);

        $templates = $this->getDoctrine()->getRepository(Template::class)->findBy(['user' => $user]);

        return $this->render('pages/menu/menu.html.twig', ['foods' => $foods, 'settings' => $settings, 'tags' => $tags,
            'types' => $types, 'templates' => $templates]);
    }

    /**
     * @Route("/menu/try", methods={"GET", "POST"})
     * @param $menu
     * @param $template_url
     * @return BinaryFileResponse
     * @throws CopyFileException
     * @throws CreateTemporaryFileExceptionAlias
     */
    public function export_as_word($menu, $template_url){
        $template_for_url = new Template();
        $template = new TemplateProcessor($template_for_url->getRealPath($template_url, $this->getUser()));
        dump($template);
//        $days = ["Pondělí", "Úterý", "Středa"];
//        $mealsTypes = ["Polévka", "Hlavní chod"];
//        $meals = ["pivo", "jidlo"];
//        $price = ["35", "60"];

        $template->cloneBlock('block_days', count($menu), true, true);
        $i = 0;
        foreach ($menu as $day) {
            // zkopírování typů jídel dle pole $mealsTypes
            $template->cloneBlock('block_mealType#'.($i+1), count($day["meals"]), true, true);
            // vložení názvů dní
            $template->setValue('day#'.($i+1), htmlspecialchars($day["day"],ENT_COMPAT, 'UTF-8'));
            $template->setValue('day_description#'.($i+1), htmlspecialchars($day["description"],ENT_COMPAT, 'UTF-8'));
            $not_soup_counter = 0;
            $j = 0;
            foreach ($day["meals"] as $type) {
                // zkopírování jídel dle pole $meals
                $template->cloneBlock('block_meals#'.($i+1).'#'.($j+1), count($type["meals"]), true,
                    true);
                // vložení typů jídel
                $template->setValue('mealType#'.($i+1).'#'.($j+1), htmlspecialchars($type["type"],ENT_COMPAT,
                    'UTF-8'));
                $k = 0;
                foreach ($type["meals"] as $meal) {
                    $meal_db = $this->getDoctrine()->getRepository(Food::class)->find($meal["id"]);
                    // not_soup_counter
                    if (htmlspecialchars($type["type"]) != "Polévka") {
                        $template->setValue('not_soup_counter#'.($i+1).'#'.($j+1).'#'.($k+1),
                            ++$not_soup_counter . '.   ');
                    } else {
                        $template->setValue('not_soup_counter#'.($i+1).'#'.($j+1).'#'.($k+1), "");
                    }
                    // vložení jídel
                    $template->setValue('meal#'.($i+1).'#'.($j+1).'#'.($k+1), htmlspecialchars($meal_db->getName(),
                        ENT_COMPAT, 'UTF-8'));
                    // vložení popisu
                    $template->setValue('description#'.($i+1).'#'.($j+1).'#'.($k+1),
                        htmlspecialchars($meal_db->getDescription(),ENT_COMPAT, 'UTF-8'));
                    // vložení cen
                    $template->setValue('price#'.($i+1).'#'.($j+1).'#'.($k+1), htmlspecialchars($meal_db->getPrice(),
                        ENT_COMPAT, 'UTF-8'));
                    $k++;
                }
                $j++;
            }
            $i++;
        }

        $template->saveAs('words/result.docx');

        $generated_menu = new File('words/result.docx');
        return $this->file($generated_menu);
    }

    public function clear_menu_from_empty($menu) {
        $i = 0;
        //dump($menu);
        foreach ($menu as $day) {
            $empty_flag = true;
            $j = 0;
            foreach ($day["meals"] as $type) {
                if ($type["meals"] == []) {
                    unset($type["meals"][$j]);
                } else {
                    $empty_flag = false;
                }
                $j++;
            }
            if ($empty_flag) {
                unset($menu[$i]);
            }
            $i++;
        }
        return $menu;
    }

    /**
     * @param FileUploader $uploader
     * @param $menu
     * @return void
     */
    //public function export_as_xml($menu){
    public function export_as_xml(FileUploader $uploader, $menu){
        //$menu = json_decode ('[{"day":"Pondělí","meals":[{"type":"Polévka","meals":[{"id":"420"},{"id":"438"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"422"},{"id":"426"}]}],"description":"Monday"},{"day":"Úterý","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"422"},{"id":"424"},{"id":"426"}]}],"description":"Tuesday"},{"day":"Středa","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"424"},{"id":"426"}]}],"description":"Wednesday"},{"day":"Čtvrtek","meals":[{"type":"Polévka","meals":[{"id":"420"}]},{"type":"Hlavní chod","meals":[{"id":"422"},{"id":"423"},{"id":"426"}]}],"description":"Thursday"},{"day":"Pátek","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"424"},{"id":"425"}]}],"description":"Friday"}]', true);
        dump($menu);
        $xml = new SimpleXMLElement('<daily_menu_list/>');

       /* for ($i = 1; $i <= 2; ++$i) {
            $day = $xml->addChild('daily_menu');
            $day->addChild('date', "22.3.2998");
            $meal = $day->addChild('meal');
            $meal->addChild('name', "Svíčková");
            $meal->addChild('price', "99");
            $meal->addChild('description', "polícka ze svíček");
        }*/

        foreach ($menu as $menu_day) {
            $day = $xml->addChild('daily_menu');
            $day->addChild('date', "22.3.1998");
            foreach ($menu_day["meals"] as $menu_type) {
                foreach ($menu_type["meals"] as $menu_meal) {
                    $meal = $day->addChild('meal');
                    $meal_db = $this->getDoctrine()->getRepository(Food::class)->find($menu_meal["id"]);
                    $meal->addChild('name', $meal_db->getName());
                    $meal->addChild('price', $meal_db->getPrice());
                    $meal->addChild('description', $meal_db->getDescription());
                    // not_soup_counter
                    if (htmlspecialchars($menu_type["type"]) == "Polévka") {
                        $meal->addChild('highlight', "true");
                    }
                }
            }
        }
        dump($xml);
        dump($xml->asXML());

        $clean_username = $this->getUser()->getCleanUsername();
        if (!is_dir('./xml')) {
            mkdir('./xml');
        }
        if (!is_dir('./xml/' . $clean_username)) {
            mkdir('./xml/' . $clean_username);
        }
        file_put_contents('./xml/' . $clean_username . "/menu.xml", $xml->asXML());

        $clean_username = $this->getUser()->getCleanUsername();
        $path = './xml/' . $clean_username . "/menu.xml";
        try {
            $uploader->aws_upload($path);
        } catch (Exception $e) {

        }
    }

    /**
     * @Route("/menu/generate", methods={"GET", "POST"})
     * @param FileUploader $uploader
     * @return BinaryFileResponse
     * @throws CopyFileException
     * @throws CreateTemporaryFileExceptionAlias
     */
    public function generate(FileUploader $uploader){
        $menu = json_decode ($_GET["json"], true);
        $template = $_GET["template"];
        //dump($template);
        $clear_menu = $this->clear_menu_from_empty($menu);
        $doGenerate = $_GET["generate"];
        $file = $this->export_as_word($clear_menu, $template);
        $this->export_as_xml($uploader, $clear_menu);
       // exit;

        //return $this->render('pages/menu/export.html.twig', ['menu' => $menu, "generate" => $doGenerate]);
        return $file;
    }

    /**
     * @Route("/menu/generate/custom", methods={"GET", "POST"})
     */
    public function generate_custom() {
//        $format_day = "{day}\n\t{meal}\n\t\t{food},{price}";
//        $format_meal = "\n\t{meal}\n\t\t{food},{price}";
//        $format_food = "\n\t\t{food},{price}";

        $format_day = "{day}\n\t{meal}\n\t\t{food},{price}";
        $format_meal = "\n\t{meal}\n\t\t{food},{price}";
        $format_food = "\n\t\t{food},{price}";



        $menu = $this->clear_menu_from_empty(json_decode($_GET["json"], true));
        $day_meals = "";

        foreach ($menu as $day) {
            $meals = str_replace("{day}", $day["day"], $format_day);
            foreach ($day["meals"] as $type) {
                $meals = str_replace("{meal}", $type["type"], $meals);
                foreach ($type["meals"] as $meal) {
                    $meal_db = $this->getDoctrine()->getRepository(Food::class)->find($meal["id"]);
                    $meals = str_replace("{food}", $meal_db->getName(), $meals);
                    $meals = str_replace("{price}", $meal_db->getPrice(), $meals);
                    $meals .= $format_food;
                }
                $meals .= $format_meal;
            }
            $day_meals .= $meals . $format_day;
        }

//        dump($menu);
//        dump($format_day);
//        dump($day_meals);
        exit;
    }

    // Old generating
//    /**
//     * @Route("/menu/generate", methods={"GET", "POST"})
//     */
//    public function generate(){
//        $menu = json_decode ($_GET["json"]);
//        $doGenerate = $_GET["generate"];
////        dump($menu);
////        exit;
//
//        return $this->render('pages/menu/export.html.twig', ['menu' => $menu, "generate" => $doGenerate]);
//    }
}