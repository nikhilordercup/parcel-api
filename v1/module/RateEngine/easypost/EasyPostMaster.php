<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 22-11-2018
 * Time: 05:20 PM
 */
namespace v1\module\RateEngine\easypost;

use EasyPost\EasyPost;

abstract class EasyPostMaster extends EasyPost
{
    public function __construct($authData)
    {
        self::setApiKey($authData->apiKey);
    }
    public function convertAddress($address){
        $easyPostAddress=$address;
        /**
         * Perform modification to address here if required
         */
        return $easyPostAddress;
    }
    public function convertPackage($package){
        unset($package['packaging_type'],$package['dimension_unit'],$package['weight_unit']);
        return $package;
    }
    public function packagesToOrder($packages){
        $order=[];
        foreach ($packages as $i=>$package){
            $order[$i]['parcel']=$this->convertPackage($package);
        }
        return $order;
    }
    public function carrierAccounts(){

    }
    public function processRates(){

    }
    public function buildRequest(){

    }
}