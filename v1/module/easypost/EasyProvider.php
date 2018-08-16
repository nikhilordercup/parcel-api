<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EasyProvider
 *
 * @author admin-pc
 */
class EasyProvider {

    private $_db = null;

    function __construct() {
        $this->_db = new DbHandler;
    }

    public function getProviderForCarrier($carrier) {
      $providers=$this->_db->getAllRecords("SELECT * FROM ".DB_PREFIX."provider_services");
      
    }

    public function fetchCarrierList() {
        $carrier=$this->_db->getAllRecords("SELECT * FROM ".DB_PREFIX."provider_carriers");
    }

    public function fetchCarrierAccount($carrier) {
        $carrierAccount=$this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."provider_carriers");
    }

    public function fetchCarrierProviders() {
        $providerSql="SELECT C.*,PS.* FROM  "
                . DB_PREFIX."carrier_service AS CS LEFT JOIN ".DB_PREFIX."carriers AS C ON C.id = CS.carrier_id "
                . "LEFT JOIN ".DB_PREFIX."provider_services AS PS ON CS.provider_id = PS.id";
        $carrierProvider=$this->_db->getAllRecords("SELECT * FROM ".DB_PREFIX."provider_services");
    }
    
    public function fetchCarrierProvider($carrierid) {
        $providerSql="SELECT C.*,PS.* FROM  "
                . DB_PREFIX."carrier_service AS CS LEFT JOIN ".DB_PREFIX."carriers AS C ON C.id = CS.carrier_id "
                . "LEFT JOIN ".DB_PREFIX."provider_services AS PS ON CS.provider_id = PS.id "
                . "WHERE C.id=$carrierid AND CS.carrier_id=$carrierid";
        $carrierProvider=$this->_db->getAllRecords("SELECT * FROM ".DB_PREFIX."provider_services");
    }

}
