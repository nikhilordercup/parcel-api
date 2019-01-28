<?php

namespace v1\module\PackageTypes\model;
class PackageTypesModel
{

    private static $db = NULL;

    public function __construct()
    {
        if (self::$db == NULL) {
            self::$db = new \DbHandler();
        }
        $this->_db = self::$db;
    }

    public function getAllPackageTypesByUserID($data)
    {   //print_r($data); die;
       // $filter = (($data->type == 'customer') ? "customer_user_id = '$data->user_id'" : "created_by = '$data->created_by'");

        $sqlStmt = "SELECT PT.*,CP.name AS cusomer_name, CH.name as child_name FROM " . DB_PREFIX . "package_type AS PT LEFT JOIN " . DB_PREFIX . "users AS CP
                    ON PT.created_by=CP.id LEFT JOIN " . DB_PREFIX . "users AS CH ON PT.customer_user_id=CH.id WHERE created_by = '$data->created_by'  ORDER BY PT.display_order ASC";
        $result = $this->_db->getAllRecords($sqlStmt);
        if (empty($result)) {
            return array('status' => 'false', 'message' => '!!!OOPS Package not found');
        } else {
            return array('status' => 'success', 'data' => $result);
        }
    }


    public function deletePackageType($package_id)
    {
        $sql = "DELETE FROM " . DB_PREFIX . "package_type WHERE id = '$package_id'";
        $deleteStmt = $this->_db->delete($sql);
        if ($deleteStmt) {
            return array('status' => 'true', 'message' => 'Package delete.');
        } else {
            return array('status' => 'false', 'message' => '!!OOPS Something went wrong');
        }
    }


    public function getPackageTypeByUserID($pkg_id)
    {
        $sqlStmt = "SELECT * FROM " . DB_PREFIX . "package_type WHERE id='$pkg_id'";
        $record = $this->_db->getRowRecord($sqlStmt);
        return $record;
    }

    public function updatePackageType($postData)
    {

        $pkgID = $postData->data->id;
        $type = $postData->data->type;
        $length = $postData->data->length;
        $weight = $postData->data->weight;
        $width = $postData->data->width;
        $height = $postData->data->height;
        $description = $postData->data->description;
        $contents = $postData->data->contents;
        $commodity_code = $postData->data->commodity_code;
        $display_order = $postData->data->display_order;
        $allowed_user = isset($postData->data->allowed_user) ? $postData->data->allowed_user : '0';
        $args = array('type' => $type, 'contents' => $contents, 'description' => $description, 'weight' => $weight,
            'length' => $length, 'width' => $width, 'height' => $height, 'commodity_code' => $commodity_code, 'display_order' => $display_order,
            'allowed_user' => $allowed_user);
        $updateStmt = $this->_db->update("package_type", $args, "id='$pkgID'");
        if ($updateStmt) {
            return array('status' => 'success', 'message' => 'Your package type update successfully.');
        } else {
            return array('status' => 'false', 'message' => '!!OOPS Something went wrong.');
        }
    }

}
