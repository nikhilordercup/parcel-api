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

            $package_id = $this->modelObj->savePackage(array(
                "type" => $param->newType,
                "contents" => $param->content,
                "description" => $param->description,
                "commodity_code" => $param->commodityCode,
                "weight" => $param->weight,

                "length" => $param->length,
                "width" => $param->width,
                "height" => $param->height,
                "display_order" => $param->displayOrder,
                "company_id" => $param->company_id,
                "created_by" => $param->createdBy
            ));

            if ($param->allowOther and $package_id) {
                $allowedUsers = $this->modelObj->getAllCustomerAndUserByCompanyId($param->company_id);
                foreach ($allowedUsers as $item) {
                    $this->modelObj->saveAllowedUserPackage(array(
                        "user_id" => $item["user_id"],
                        "package_id" => $package_id
                    ));
                }
            }
            $this->modelObj->commitTransaction();
            return array("status"=>"success", "message"=>"Package saved successfully");
        }catch(Exception $e){
            $this->modelObj->rollBackTransaction();
            return array("status"=>"error", "message"=>"Package not saved. Record rollback.");
        }
    }
}