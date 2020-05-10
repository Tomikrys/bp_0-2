<?php
namespace App\Service;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Psr\Log\LoggerInterface;

class FileUploader {
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function upload($uploadDir, $file, $filename)
    {
        try {
            $file->move($uploadDir, $filename);
        } catch (FileException $e){
            $this->logger->error('failed to upload file: ' . $e->getMessage());
            throw new FileException('Failed to upload file');
        }
    }

    public function aws_upload($path){
        putenv("AWS_ACCESS_KEY_ID=AKIAVEKPVHFC4QT6CW4Q");
        putenv("AWS_SECRET_ACCESS_KEY=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");
        try {
            $s3Client = new S3Client([
                'region' => 'us-east-1',
                'version' => 'latest',
                'credentials' => CredentialProvider::env()
            ]);
            $result = $s3Client->putObject([
                'Bucket'     => $_ENV["S3_BUCKET"],
                'Key'        => $path,
                'SourceFile' => $path,
            ]);
        } catch (AwsException $e) {
            echo $e->getMessage() . "\n";
        }
    }
}