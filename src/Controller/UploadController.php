<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Service\FileUploader;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use http\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController {


    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * FoodsController constructor.
     * @param TemplateRepository $templateRepository
     */
    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    function does_url_exists($url) {
        return @fopen($url, 'r') ? true : false;
    }

    /**
     * @Route("/doUpload", name="upload")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function index(Request $request, string $uploadDir, FileUploader $uploader, LoggerInterface $logger) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $clean_username = $this->getUser()->getCleanUsername();
        $token = $request->get("token");

        if (!$this->isCsrfTokenValid('upload', $token)){
            $logger->info("CSRF failure");

            $this->addFlash('error', 'Nepovolená operace.');
        }

        $file = $request->files->get('myfile');

        if (empty($file)){
            $this->addFlash('error', 'Nebyl zadán soubor.');
        }

        $file_fullname = $file->getClientOriginalName();
        $url = $uploadDir . "/" . $clean_username . "/" . $file_fullname;

        $filename = substr($file_fullname, 0, strrpos($file_fullname, '.'));
        $extension = substr($file_fullname, strrpos($file_fullname, '.'));

        $AWSpath = "https://menickajednodusecz.s3.amazonaws.com/words/";

        $AWSurl = $AWSpath . $clean_username . "/" . $file_fullname;
        $i = 0;
        $user = $this->getUser();

        while ($this->does_url_exists($AWSurl)) {
            $i++;
            $filename_dubler = $filename . "-" . $i . $extension;
            $AWSurl = $AWSpath . $clean_username . "/" . $filename_dubler;
        }

        if ($i) {
            $filename .= "-" . $i . $extension;
        } else {
            $filename .= $extension;
        }


        $uploader->upload($uploadDir. "/" . $clean_username, $file, $filename);
        $path = './words/' . $clean_username . "/" . $filename;
        try {
            $uploader->aws_upload($path);
        } catch (Exception $e) {

        }

        $templatename = $request->request->get('template_name');
        $template = new Template();

        $template->setPath(basename($AWSurl));

        $i = 0;
        $templatename_dubler = $templatename;
        while ($this->getDoctrine()->getRepository(Template::class)->findBy(['user' => $user, 'name' => $templatename_dubler])) {
            $i++;
            $templatename_dubler = $templatename . "-" . $i;
        }
        if ($i) {
            $templatename .= "-". $i;
        }
        $template->setName($templatename);
        $template->setUser($this->getUser());

        $this->templateRepository->save($template);

        $this->addFlash('success', 'Šablona byla nahrána.');
        return $this->redirect($this->generateUrl('/settings'));
    }
}
