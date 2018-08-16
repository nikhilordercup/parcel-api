<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EasypostController
 *
 * @author admin-pc
 */
class EasypostController {
    private $_db;
    private $_apiKey='Kw4yCLTwNKqMKvUo8iiISjQIMc80cAQA';

    /**
     * ConfigurationManager constructor.
     */
    public function __construct()
    {
        $this->_db=new DbHandler();
        \EasyPost\EasyPost::setApiKey(gzinflate(base64_decode($this->_apiKey)));        
    }
    public function createAddress($info){
        try{
            EasyPost\Address::create($info);
        } catch (Exception $ex) {

        }
    }
    public function createShipment($info){
        try{
            \EasyPost\Shipment::create($info);
        } catch (Exception $ex) {

        }
    }
    public function getPriceList(){
        try{
            $shipment=\EasyPost\Shipment::create($info);
            $shipment->get_rates();
        } catch (Exception $ex) {

        }
    }
    public function resultToArray($result){
        return $result->__toArray();
    }
}
