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

    private function _createForm($type, $config, $formHiddenData){
        

        $html = array();

        array_push($html, '<form name="formData" ng-submit="form_submit()" novalidate form-manager>');
        array_push($html, '<ion-list>');

        //consignee html
        if(isset($config->consignee)){
            if($config->consignee->show_name=="Yes"){
                if($config->consignee->name_required=="Yes"){
                    array_push($html, '<ion-item class="nameSection" data-contact-name-required="required">');
                }else{
                    array_push($html, '<ion-item class="nameSection">');
                }
                array_push($html, '<strong data-ng-hide="elm_contact_name">Contact: replace_contact_name <a><i class="ion-edit" data-ng-click="elm_contact_name=true"></i></a></strong>');
                array_push($html, '<div class="nameField" data-ng-show="elm_contact_name">');

                array_push($html, '<span class="item-note elm-close"><i class="ion-close-round" data-ng-click="elm_contact_name=false"></i></span>');

                if($config->consignee->name_required=="Yes"){
                    array_push($html, '<input ng-keypress="save_contact_name(e,formData.contact_name)" value="replace_contact_name" id="contact_name" ng-model="formData.contact_name" name="formData.contact_name" type="text" placeholder="Change Your Name" ng-required="true"/>');
                }else{
                    array_push($html, '<input ng-keypress="save_contact_name(e,formData.contact_name)" value="replace_contact_name" id="contact_name" ng-model="formData.contact_name" name="formData.contact_name" type="text" placeholder="Change Your Name"/>');
                }

                array_push($html, '</div>');
                array_push($html, '</ion-item>');
            }
        }

        //parcel dimension html
        if(isset($config->parcel)){
            if($config->parcel->show_dimension=="Yes"){
                array_push($html, '<ion-item class="colo-1">');
                    if($config->parcel->scan_required=="Yes"){
                        array_push($html, '<div id="parcel-container" class="tabularlists" data-parcel-scan-required="required">');
                        array_push($html, '<ion-list id="parcel-sub-container"></ion-list>');
                        array_push($html, '</div>');
                    }elseif($config->parcel->scan_required=="No"){
                        array_push($html, '<div id="parcel-container" class="tabularlists"');
                        array_push($html, '<ion-list id="parcel-sub-container"></ion-list>');
                        array_push($html, '</div>');
                    }
                array_push($html, '</ion-item>');
                if($config->parcel->scan_button=="Yes"){
                    array_push($html, '<div id="parcel_scan_button"></div>');
                }
            }
        }

        //signature html
        if(isset($config->signature)){
            if($config->signature->show_signature=="Yes"){
                array_push($html, '<ion-item>');
                if($config->signature->signature_required=="Yes"){
                    array_push($html, '<div class="signatureField" data-signature-required="required" ng-if="signatureCaptured==false">');
                }else{
                    array_push($html, '<div class="signatureField" ng-if="signatureCaptured==false">');
                }
                array_push($html, '<a href="#/app/signature">Signature</a>');
                array_push($html, '</div>');
                array_push($html, '</ion-item>');

                //retake signature html
                array_push($html, '<ion-item id="signature-with-retake" class="commentSection" ng-if="signatureCaptured==true">');
                array_push($html, '<div class="commentField thumbnail"><img src="{{customer_signature}}"></div>');
                array_push($html, '<div class="scanField"><a href="#/app/signature">Retake</a></div>');
                array_push($html, '</ion-item>');
            }
        }

        //comment without scan html
        if(isset($config->comment_without_scan)){
            if($config->comment_without_scan->show_comment_area=="Yes"){
                array_push($html, '<ion-item class="commentSection">');
                array_push($html, '<div class="commentField Displayfull">');

                if($config->comment_without_scan->comment_required=="Yes"){
                    array_push($html, '<textarea placeholder="Comment" ng-model="formData.customer_comment" name="formData.customer_comment" ng-required="true"></textarea>');
                }else{
                    array_push($html, '<textarea placeholder="Comment" ng-model="formData.customer_comment" name="formData.customer_comment"></textarea>');
                }
                array_push($html, '</div>');
                array_push($html, '</ion-item>');
            }
        }

        //comment with scan html
        if(isset($config->comment_with_scan)){
            if($config->comment_with_scan->show_comment_area=="Yes"){
                array_push($html, '<ion-item class="commentSection">');
                array_push($html, '<div class="commentField">');

                if($config->comment_with_scan->comment_required=="Yes"){
                    array_push($html, '<textarea placeholder="Comment" ng-model="formData.customer_comment" name="formData.customer_comment" ng-required="true"></textarea>');
                }else{
                    array_push($html, '<textarea placeholder="Comment" ng-model="formData.customer_comment" name="formData.customer_comment"></textarea>');
                }

                /*if(isset($config->comment_with_scan->comment_scan_required)){
                    if($config->comment_with_scan->comment_scan_required=="Yes"){
                        array_push($html, '<div class="scanField scan-card-field" data-card-scan-required="required"><a ng-click="failed_attempt_carded_scan_info();">CARD SCAN</a></div>');
                    }else{
                        array_push($html, '<div class="scanField scan-card-field"><a ng-click="failed_attempt_carded_scan_info();">CARD SCAN</a></div>');
                    }
                }*/

                array_push($html, '</div>');
                array_push($html, '<div class="scanField"><a ng-click="failed_attempt_carded_scan_info();">CARD SCAN</a></div>');

                array_push($html, '</ion-item>');
            }
        }

        //hidden value
        array_push($html, '<input id="service" value="'.$formHiddenData->service.'" type="hidden"/>');
        array_push($html, '<input id="service-code" value="'.$formHiddenData->code.'" type="hidden"/>');
        array_push($html, '<input id="service-name" value="'.$formHiddenData->name.'" type="hidden"/>');

        //submit button html
        array_push($html, '<ion-item class="submitBtn">');
        array_push($html, '<input ng-model="save" type="submit" value="Save"/>');
        array_push($html, '</ion-item>');

        array_push($html, '</ion-list>');
        array_push($html, '</form>');
        return $html;
    }
    public function addFormConfiguration($companyId,$configData,$extraData){
        $sql="INSERT INTO ".DB_PREFIX."system_configuration (configuration_type,company_id,config_data,extra_data)".
            " VALUES ('APP_FORM',$companyId,'$configData','$extraData')";
        return $this->_db->executeQuery($sql);
    }
    /*
    full of bugs. commented by nishant.
    public function updateFormConfiguration($companyId,$configData,$extraData){
        $exist=$this->listFormConfiguration($companyId);
        if(is_null($exist)){
            return $this->addFormConfiguration($companyId,$configData,$extraData);
        }
        $configData=addslashes($configData);
       // $extraData=addslashes($extraData);
        $sql="UPDATE ".DB_PREFIX."system_configuration SET config_data='$configData',extra_data='$extraData' WHERE ".
            " company_id=$companyId AND configuration_type='APP_FORM'";
        return $this->_db->updateData($sql);
    }*/

    public function updateFormConfiguration($companyId, $extraData, $formConfig){
        $formData = array();
        foreach($extraData as $type => $config){
            $formHiddenData = $formConfig->$type;
            $formHtml = $this->_createForm($type, $config, $formHiddenData);
            $formData[$type] = addslashes(implode("",$formHtml));
        }

        $formData = json_encode($formData);
        $extraData = json_encode($extraData);

        $exist=$this->listFormConfiguration($companyId);
        if(is_null($exist)){
            return $this->addFormConfiguration($companyId,$formData,$extraData);
        }

      
        $sql="UPDATE ".DB_PREFIX."system_configuration SET config_data='$formData',extra_data='$extraData' WHERE ".
            " company_id=$companyId AND configuration_type='APP_FORM'";
        return $this->_db->updateData($sql);

    }

    public function deleteFormConfiguration($typeId,$companyId){
        $sql="DELETE ".DB_PREFIX."system_configuration WHERE ".
            " company_id=$companyId AND configuration_type_id=$typeId";
        return $this->_db->delete($sql);
    }
    public function listFormConfiguration($companyId){
        $sql="SELECT * FROM ".DB_PREFIX."system_configuration WHERE  company_id=$companyId AND configuration_type='APP_FORM'";
        return $this->_db->getOneRecord($sql);
    }
}