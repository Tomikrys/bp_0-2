<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\History;
use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use App\Repository\HistoryRepository;
use DateTime;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;
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
class HistoryController extends AbstractController {

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
        $history = $this->getDoctrine()->getRepository(History::class)->findBy(['user' => $user]);
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
        usort($prepared_history, function($a, $b) {return $a['sortdate']->getTimestamp() - $b['sortdate']->getTimestamp();});
        //dump($prepared_history);
        return $this->render('pages/history/history.html.twig', array('history' => $prepared_history));
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
        return array(
            'days' => $days,
            'meals' => $this->invert_array_of_array($week_meals)
        );
    }

    /**
     * @Route("/history/add", methods={"GET", "POST"})
     * @throws \Exception
     */
    public function add() {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $json = file_get_contents('php://input');
        //dump($json);
        $data = json_decode ($json, true);
        //dump($data['date']);
        //dump($json);
        //dump($data);
        $history = new History();
        $date = new DateTime("@" . $data['date'] / 1000);
        //dump($date);
        $history->setDateFrom($date);
        //dump($history->getDateFrom());
        //dump($history->getDateFromFormatted());
        //dump(json_encode($data['json']));
        //dump($data['json']);
        $history->setJson($data['json']);
        $history->setUser($this->getUser());
        //dump($history);
        $this->historyRepository->save($history);

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
        $out = array();
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