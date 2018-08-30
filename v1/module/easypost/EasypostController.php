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
                $parcel = [];
                foreach ($parcelInfo as $p) {
                        array_push($parcel, ['parcel' => $p]);
                }
                $ca = [];
                foreach ($accounts as $a) {
                    array_push($ca, ['id' => $a]);
                }
                $shipment = \EasyPost\Order::create(array(
                            'to_address' => $toAddress,
                            'from_address' => $fromAddress,
                            'shipments' => $parcel,
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

    public function generateLabel($toAddress, $fromAddress, $parcelInfo, $carrier, $selectedService, $accounts, $data, $insurance = 0) {
        try {
            if (count($parcelInfo) > 1) {
                $parcel = [];
                $toAddress = (array) $toAddress;
                $this->trimArray($toAddress);
                $fromAddress = (array) $fromAddress;
                $this->trimArray($fromAddress);
                foreach ($parcelInfo as $p) {
                    if (isset($data->insurance)&& isset($data->insurance->value)) {
                        array_push($parcel, ['parcel' => (array)$p,
                            'insurance' => [
                                'amount' => $data->insurance->value
                            ]
                        ]);
                    } else {
                    array_push($parcel, ['parcel' => (array) $p]);
                    }
                }
                $ca = [];
                foreach ($accounts as $a) {
                    array_push($ca, ['id' => $a]);
                }
//                print_r(array(
//                            'to_address' => (array) $toAddress,
//                            'from_address' => (array) $fromAddress,
//                            'shipments' => $parcel,
//                            "carrier_accounts" => $ca
//                ));exit;
                $shipment = \EasyPost\Order::create(array(
                            'to_address' => (array) $toAddress,
                            'from_address' => (array) $fromAddress,
                            'shipments' => $parcel,
                            "carrier_accounts" => $ca
                ));
                if ($insurance) {
                    $shipment->insure(array('amount' => $insurance));
                }
                $carrier = "";
                foreach ($shipment->get_rates()->rates as $r) {
                    $r = $r->__toArray();
                    if ($r['service'] == $data->service) {
                        $carrier = $r['carrier'];
                    }
                }
                if ($carrier == "") {
                    throw new Exception("Carrier not found for easypost.");
                }
                $shipment->buy(['carrier' => $carrier, 'service' => $data->service]);
                //print_r($shipment->__toArray());exit;
                return $shipment;
            } else {

                $shipment = \EasyPost\Shipment::create(array(
                            'to_address' => (array) $toAddress,
                            'from_address' => (array) $fromAddress,
                            'parcel' => (array) $parcelInfo[0],
                            "carrier_accounts" => $accounts
                ));
                $shipment->buy($this->getRateId($shipment, $selectedService));
//                if (isset($data->insurance)&& isset($data->insurance->value)) {
//                    $shipment->insure(array('amount' => $data->insurance->value));
//                }
                return $shipment;
            }
        } catch (\EasyPost\Error $ex) {
            print_r($ex->prettyPrint());
            exit;
        }
    }

    public function getRateId($shipment, $serviceName) {
        $rates = $shipment->get_rates()->rates;
        $rate = null;
        foreach ($rates as $r) {
            $rate = $r->__toArray();
            if ($rate['service'] == $serviceName) {
                $rate = $r;
                break;
            }
        }
        return $rate;
    }

}
