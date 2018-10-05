<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 3:49 PM
 */

class Ws_Credential_Info
{
    public function saveCredentialInfo($param)
    {
        $this->modelObj  = Shipment_Model::getInstanse();
        return $this->modelObj->saveUserCredentialInfo(array("device_token_id"=>$param["device_token_id"]), $param["user_code"]);
    }
}