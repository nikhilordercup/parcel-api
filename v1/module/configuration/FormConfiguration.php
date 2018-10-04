<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 02/02/2018
 * Time: 01:05 PM
 */

class FormConfiguration
{
    private $_db;

    /**
     * FormConfiguration constructor.
     */
    public function __construct()
    {
        $this->_db=new DbHandler();
    }

    public function addFormConfiguration($companyId,$configData,$extraData){
        $sql="INSERT INTO ".DB_PREFIX."system_configuration (configuration_type,company_id,config_data)".
            " VALUES ('APP_FORM',$companyId,'$configData')";
        return $this->_db->executeQuery($sql);
    }

    public function updateFormConfiguration($companyId, $formConfig){
        $formData = json_encode($formConfig);

        $exist=$this->listFormConfiguration($companyId);
        if(is_null($exist)){
            return $this->addFormConfiguration($companyId,$formData);
        }
        $sql="UPDATE ".DB_PREFIX."system_configuration SET config_data='$formData' WHERE company_id=$companyId AND configuration_type='APP_FORM'";
        return $this->_db->updateData($sql);

    }

    public function deleteFormConfiguration($typeId,$companyId){
        $sql="DELETE ".DB_PREFIX."system_configuration WHERE company_id=$companyId AND configuration_type_id=$typeId";
        return $this->_db->delete($sql);
    }

    public function listFormConfiguration($companyId){
        $sql="SELECT config_data FROM ".DB_PREFIX."system_configuration WHERE  company_id=$companyId AND configuration_type='APP_FORM'";
        return $this->_db->getOneRecord($sql);
    }
}
