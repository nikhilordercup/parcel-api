<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 11:14 AM
 */

namespace v1\module\BucketManager;


use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class BucketManager
{
    /**
     * @var S3Client $_client
     */
    protected $_client;
    protected $_bucketName='icargo-public';
    /**
     * BucketManager constructor.
     */
    public function __construct()
    {
        $this->_client = new S3Client([
            'region' => 'eu-west-1',
            'version' => 'latest',
            'credentials' => [
                'key' => 'old@@AKIAJO35W__author__34IHPA2JURQ@@old',
                'secret' => '__ZWO+E+Enc9K6ZR3DLJa__gdp__BLKEhJNoeqEbvkQK1lOHD__'
            ],
            'http' => ['verify' => false]
        ]);
    }
    public function uploadFile($filePath){
        try {
            $result = $this->_client->putObject([
                'Bucket' => $this->_bucketName,
                'Key' => ENV . '/' . $filePath,
                'SourceFile' => $filePath,
                'ACL' => 'public-read'
            ]);
        }catch (S3Exception $e){
            echo $e->getMessage() . PHP_EOL;
        }
    }
    public function getFile($fileName){
        try {
            $result = $this->_client->getObject([
                'Bucket' => $this->_bucketName,
                'Key' => $fileName
            ]);
            header("Content-Type: {$result['ContentType']}");
            echo $result['Body'];
        }catch (S3Exception $e){
            echo $e->getMessage() . PHP_EOL;
        }
    }
}