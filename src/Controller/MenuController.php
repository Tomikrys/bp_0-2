<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Type;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class MenuController extends AbstractController {
    /**
     * @Route("/menu", methods={"GET", "POST"})
     */
    public function index(){
        // naplnění struktury pro výpis tabulky
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();
        $foods = null;
        $type = null;
        foreach ($types as $type) {
            $foods_of_type = $type->getFoods();
            $foods[$type->getName()] = $foods_of_type;
        }
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);

        return $this->render('pages/menu/menu.html.twig', array('foods' => $foods, 'settings' => $settings));
    }

    /**
     * @Route("/menu/try", methods={"GET", "POST"})
     */
    public function process_word(){
        $template = new TemplateProcessor('words/template.docx');
//        $template->setValue('variableName', 'Tomi je pán');
        $days = ["Pondělí", "Úterý", "Středa"];
        $mealsTypes = ["Polévka", "Hlavní chod"];
        $meals = ["pivo", "jidlo"];
        $price = ["35", "60"];
        // zkopírování dní dle pole $days
        $template->cloneBlock('block_days', count($days), true, true);
        for($i = 0; $i < count($days); $i++) {
            // zkopírování typů jídel dle pole $mealsTypes
            $template->cloneBlock('block_mealType#'.($i+1), count($mealsTypes), true, true);
            // vložení názvů dní
            $template->setValue('day#'.($i+1), htmlspecialchars($days[$i],ENT_COMPAT, 'UTF-8'));
            for($j = 0; $j < count($mealsTypes); $j++) {
                // zkopírování jídel dle pole $meals
                $template->cloneBlock('block_meals#'.($i+1).'#'.($j+1), count($meals), true, true);
                // vložení typů jídel
                $template->setValue('mealType#'.($i+1).'#'.($j+1), htmlspecialchars($mealsTypes[$j],ENT_COMPAT, 'UTF-8'));
                for($k = 0; $k < count($meals); $k++) {
                    // vložení jídel
                    $template->setValue('meal#'.($i+1).'#'.($j+1).'#'.($k+1), htmlspecialchars($meals[$k],ENT_COMPAT, 'UTF-8'));
                    // vložení cen
                    $template->setValue('price#'.($i+1).'#'.($j+1).'#'.($k+1), htmlspecialchars($price[$k],ENT_COMPAT, 'UTF-8'));
                }
            }
        }


        $template->saveAs('words/result.docx');

        $generated_menu = new File('words/result.docx');
        return $this->file($generated_menu);;
    }

    /**
     * @Route("/menu/generate", methods={"GET", "POST"})
     */
    public function generate(){
        $menu = json_decode ($_GET["json"]);
        dump($menu);
        exit;
        $doGenerate = $_GET["generate"];
//        dump($menu);
//        exit;

        return $this->render('pages/menu/export.html.twig', array('menu' => $menu, "generate" => $doGenerate));
    }
}