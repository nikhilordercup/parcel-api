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
    private $_apiKey = 'sR0Q8I2yZuHrHpZa0T7i0A';

    /**
     * ConfigurationManager constructor.
     */
    public function __construct() {
        $this->_db = new DbHandler();
        \EasyPost\EasyPost::setApiKey($this->_apiKey);
    }

    public function createAddress($info) {
        try {
            EasyPost\Address::create($info);
        } catch (Exception $ex) {
            
        }
    }

    public function createShipment($info) {
        try {
            \EasyPost\Shipment::create($info);
        } catch (Exception $ex) {
            
        }
    }

    public function getPriceList() {
        try {
            $shipment = \EasyPost\Shipment::create($info);
            $shipment->get_rates();
        } catch (Exception $ex) {
            
        }
    }

    public function resultToArray($result) {
        return $result->__toArray();
    }

    public function getShipmentRates($toAddress = array(), $fromAddress = array(), $parcelInfo = array(), $accounts = []) {
        
        try {
            if (count($parcelInfo) > 1) {
                $shipment = \EasyPost\Order::create(array(
                            'to_address' => $toAddress,
                            'from_address' => $fromAddress,
                            'parcel' => $parcelInfo[0],
                            "carrier_accounts" => $accounts
                ));
            } else {
                $shipment = \EasyPost\Shipment::create(array(
                            'to_address' => $toAddress,
                            'from_address' => $fromAddress,
                            'parcel' => $parcelInfo[0],
                            "carrier_accounts" => $accounts
                ));
            }
            //print_r($shipment->get_rates()->rates);exit;
            return $shipment->get_rates()->rates;
        } catch (\EasyPost\Error $ex) {
            print_r($ex->prettyPrint());
            exit;
        }
    }

    public function trimArray(&$array) {
        foreach ($array as $k => $a) {
            if (trim($a) == "")
                unset($array[$k]);
        }
        return $this;
    }

}
