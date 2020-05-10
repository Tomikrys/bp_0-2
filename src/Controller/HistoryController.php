<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\History;
use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Repository\HistoryRepository;
use App\Service\FileUploader;
use DateTime;
use http\Exception;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HistoryController
 * @package App\Controller
 */
class HistoryController extends DefaultController {

    /**
     * @var HistoryRepository
     */
    private $historyRepository;

    /**
     * HistoryController constructor.
     * @param HistoryRepository $historyRepository
     */
    public function __construct(HistoryRepository $historyRepository)
    {
        $this->historyRepository = $historyRepository;
    }

    /**
     * @Route("/history", methods={"GET", "POST"})
     */
    public function index(){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $history = $this->getDoctrine()->getRepository(History::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $prepared_history = [];
        $i = 0;
        if ($history != null) {
            foreach ($history as $week) {
                $prepared_history[$i]['id'] = $week->getId();
                $prepared_history[$i]['sortdate'] = $week->getDateFrom();
                $prepared_history[$i]['date'] = $week->getDateFromFormatted();
                $prepared_history[$i]['url'] = '/menu?date=' . $week->getDateFromFormatted() . '&json=' . json_encode($week->getJson());
                $prepared_history[$i]['table'] = $this->make_table_form_json($week->getJson());
                $i++;
            }
        }
        //usort($prepared_history, function($a, $b) {return $a['sortdate']->getTimestamp() - $b['sortdate']->getTimestamp();});
        //dump($prepared_history);
        return $this->render('pages/history/history.html.twig', ['history' => $prepared_history]);
    }

    public function find_food_by_id($foods, $id) {
        return $id;
    }

    public function make_table_form_json($json) {
        $table = null;
        $user = $this->getUser();
        // TODO optimaliyovat
        //$foods = $this->getDoctrine()->getRepository(Food::class)->findBy(['user' => $user]);
        $food_repository = $this->getDoctrine()->getRepository(Food::class);
        //dump($json);
        $days = [];
        $week_meals = [];
        foreach ($json as $day) {
            array_push($days, $day['day']);
            $day_meals = [];
            foreach ($day['meals'] as $types) {
                foreach ($types['meals'] as $meals) {
                    // TODO optimaliyace?
                    //array_push($day_meals, $this->find_food_by_id($foods, $meals[0]['id']));
                    array_push($day_meals, $food_repository->findBy(['user' => $user, 'id' => $meals['id']]));
                }
            }
            //dump($day_meals);
            array_push($week_meals, $day_meals);
        }
        //dump($week_meals);
        return [
            'days' => $days,
            'meals' => $this->invert_array_of_array($week_meals)
        ];
    }
    /**
     * @param FileUploader $uploader
     * @param $menu
     * @return void
     */
    //public function export_as_xml($menu){
    public function export_as_xml(FileUploader $uploader, $menu){
        //$menu = json_decode ('[{"day":"Pondělí","meals":[{"type":"Polévka","meals":[{"id":"420"},{"id":"438"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"422"},{"id":"426"}]}],"description":"Monday"},{"day":"Úterý","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"422"},{"id":"424"},{"id":"426"}]}],"description":"Tuesday"},{"day":"Středa","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"424"},{"id":"426"}]}],"description":"Wednesday"},{"day":"Čtvrtek","meals":[{"type":"Polévka","meals":[{"id":"420"}]},{"type":"Hlavní chod","meals":[{"id":"422"},{"id":"423"},{"id":"426"}]}],"description":"Thursday"},{"day":"Pátek","meals":[{"type":"Polévka","meals":[{"id":"419"}]},{"type":"Hlavní chod","meals":[{"id":"421"},{"id":"424"},{"id":"425"}]}],"description":"Friday"}]', true);
        //dump($menu);
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
        //dump($xml);
        //dump($xml->asXML());

        $clean_username = $this->getUser()->getCleanUsername();
        if (!is_dir('./xml')) {
            mkdir('./xml');
        }
        if (!is_dir('./xml/' . $clean_username)) {
            mkdir('./xml/' . $clean_username);
        }
        touch('./xml/' . $clean_username . "/menu.xml");
        $fp = fopen('./xml/' . $clean_username . "/menu.xml", 'w');
        fwrite($fp, $xml->asXML());
        fclose($fp);
        //file_put_contents('./xml/' . $clean_username . "/menu.xml", $xml->asXML());

        $clean_username = $this->getUser()->getCleanUsername();
        $path = './xml/' . $clean_username . "/menu.xml";
        try {
            $uploader->aws_upload($path);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * @Route("/history/add", methods={"GET", "POST"})
     * @throws \Exception
     */
    public function add(FileUploader $uploader) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $json = file_get_contents('php://input');
        $data = json_decode ($json, true);
        $history = new History();
        $date = new DateTime("@" . $data['date'] / 1000);
        $history->setDateFrom($date);
        $history->setJson($data['json']);
        $history->setUser($this->getUser());
        $this->historyRepository->save($history);

        $menu = json_decode ($json, true);
        //dump($menu['json']);
        $clear_menu = $this->clear_menu_from_empty($menu['json']);
        $this->export_as_xml($uploader, $clear_menu);

        return new Response();
    }

    function cleanArray($array){
        $max = 0;
        foreach ($array as $outside) {
            end($outside);
            if (key($outside) > $max) {
                $max = key($outside);
            }
        }
        $new_array = [];
        foreach ($array as $outside) {
            for ($i = 0; $i <= $max; $i++) {
                if (!isset($outside[$i])) {
                    $outside[$i] = null;
                }
            }
            ksort($outside);
            array_push($new_array, $outside);
        }
//        dump($new_array);
//        exit;
        return $new_array;
    }

    private function invert_array_of_array(array $array_of_array)
    {
        //dump($array_of_array);
        $out = [];
        foreach ($array_of_array as $key => $subarr){
            foreach ($subarr as $subkey => $subvalue){
                $out[$subkey][$key] = $subvalue;
            }
        }
        //dump($out);
        return $this->cleanArray($out);
    }

    /**
     * @param Request $request
     * @param $id
     * @Route("/history/delete/{id}", methods={"DELETE"})
     * @return Response
     */
    public function delete(Request $request, $id) {
        $food = $this->getDoctrine()->getRepository(History::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($food);
        $entityManager->flush();
        $this->addFlash('warning', 'Záznam byl smazán z historie.');
        $response = new Response();
        return $response;
    }
}