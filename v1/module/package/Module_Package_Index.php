<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/04/18
 * Time: 3:15 PM
 */
require_once "model/Package_Model_Index.php";
class Module_Package_Index extends Icargo
{
    private $modelObj = NULL;
    public
    function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = new Package_Model_Index();
    }

    public
    function savePackage($param)
    {
        try {
            $this->modelObj->startTransaction();
            if ($param->displayOrder > 0) {
                $allPackages = $this->modelObj->getAllPackagesByCreatedUserId($param->createdBy);
                foreach ($allPackages as $item) {
                    if ($item["display_order"] >= $param->displayOrder) {
                        //update display order [increment by one]
                        $status = $this->modelObj->updatePackage(array("display_order" => $item["display_order"] + 1), "id=" . $item["id"]);
                    }
                }
            }

            $displayOrder = (isset($param->displayOrder)) ? $param->displayOrder : 0 ;

            $package_id = $this->modelObj->savePackage(array(
                "type" => $param->newType,
                "contents" => $param->content,
                "description" => $param->description,
                "commodity_code" => $param->commodityCode,
                "weight" => $param->weight,

                "length" => $param->length,
                "width" => $param->width,
                "height" => $param->height,
                "display_order" => $displayOrder,
                "company_id" => $param->company_id,
                "created_by" => $param->createdBy
            ));

            $allowedUsers= array();
            $allowedUsers[$param->createdBy] = $param->createdBy;

            if ($param->allowOther and $package_id) {
                $items = $this->modelObj->getAllCustomerAndUserByCompanyId($param->company_id);

                foreach($items as $item){
                    $allowedUsers[$item["user_id"]] = $item["user_id"];
                }
            }

            foreach ($allowedUsers as $item) {
                $status = $this->modelObj->saveAllowedUserPackage(array(
                    "user_id" => $item,
                    "package_id" => $package_id
                ));
            }

            $this->modelObj->commitTransaction();

            return array("status"=>"success", "message"=>"Package saved successfully","package_lists"=>$this->_getPackages($param->createdBy));
        }catch(Exception $e){
            print_r($e);die;
            $this->modelObj->rollBackTransaction();
            return array("status"=>"error", "message"=>"Package not saved. Record rollback.");
        }
    }

    private

    function _getPackages($user_id){
        return $this->modelObj->getParcelPackageByUserId($user_id);
    }

    public
    function getPackages($param){
        return $this->_getPackages($param->user_id);
    }

}