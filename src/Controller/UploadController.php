<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Service\FileUploader;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\S3\Transfer;
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

    /**
     * @Route("/aws", name="aws")
     */
    public function try() {
        $path = "./words/template.docx";
        $this->aws_upload($path);
    }

    public function aws_upload($path){
        try {
            $s3Client = new S3Client([
                'region' => 'eu-frankfurt',
                'version' => 'latest',
                'credentials' => CredentialProvider::env()
            ]);
            $result = $s3Client->putObject([
                'Bucket'     => 'menickajednoduse',
                'Key'        => $path,
                'SourceFile' => $path,
            ]);
        } catch (AwsException $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * @Route("/doUpload", name="upload")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function index(Request $request, string $uploadDir, FileUploader $uploader, LoggerInterface $logger) {
        $token = $request->get("token");

        if (!$this->isCsrfTokenValid('upload', $token))
        {
            $logger->info("CSRF failure");

            $this->addFlash('error', 'Nepovolená operace.');
//            return new Response("Operation not allowed",  Response::HTTP_BAD_REQUEST,
//                ['content-type' => 'text/plain']);
        }

        $file = $request->files->get('myfile');

        if (empty($file))
        {
            $this->addFlash('error', 'Nebyl zadán soubor.');
//            return new Response("No file specified",
//                Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }


        $file_fullname = $file->getClientOriginalName();
        $url = $uploadDir . "/" . $file_fullname;

        $filename = substr($file_fullname, 0, strrpos($file_fullname, '.'));
        $extension = substr($file_fullname, strrpos($file_fullname, '.'));
        $i = 0;
        while (file_exists($url)) {
            $i++;
            $filename_dubler = $filename . "-" . $i . $extension;
            $url = $uploadDir . "/" . $filename_dubler;
        }

        if ($i) {
            $filename .= "-" . $i . $extension;
        } else {
            $filename .= $extension;
        }

        $uploader->upload($uploadDir, $file, $filename);

//        $s3 = new Aws\S3\S3Client([
//            'version'  => '2006-03-01',
//            'region'   => 'us-east-1',
//        ]);
//        $bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
        //$upload = $s3->upload($bucket, $_FILES['userfile']['name'], fopen($_FILES['userfile']['tmp_name'], 'rb'), 'public-read');

        $templatename = $request->request->get('template_name');
        $template = new Template();

        $template->setPath(basename($url));

        $i = 0;
        $templatename_dubler = $templatename;
        while ($this->getDoctrine()->getRepository(Template::class)->findBy(array('name' => $templatename_dubler))) {
            $i++;
            $templatename_dubler = $templatename . "-" . $i;
        }
        if ($i) {
            $templatename .= "-". $i;
        }
        $template->setName($templatename);

        $this->templateRepository->save($template);

        $this->addFlash('success', 'Šablona byla nahrána.');
//        return new Response("File uploaded",  Response::HTTP_OK,
//            ['content-type' => 'text/plain']);
        return $this->redirect($this->generateUrl('/settings'));
    }
}
