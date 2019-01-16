<?php

namespace v1\module\PackageTypes;

class PackageTypes extends \Icargo
{

    public function __construct($data = array())
    {
        parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
    }

    private function getInstance()
    {
        return new \v1\module\PackageTypes\model\PackageTypesModel();
    }

    public function getAllPackageTypesByUID($postData)
    {
        $record = $this->getInstance()->getAllPackageTypesByUserID($postData);
        return $record;
    }

    public function getPackageTypeByID($postData)
    {
        $userPackageRecord = $this->getInstance()->getPackageTypeByUserID($postData->pkg_id);
        return array('status' => 'success', 'pkgData' => $userPackageRecord);
    }

    public function updateUserPackage($postData)
    {
        $userPkgData = $this->getInstance()->updatePackageType($postData);
        return $userPkgData;
    }

    public function deletePackageByID($pid)
    {
        $deleteStmt = $this->getInstance()->deletePackageType($pid->package_id);
        return $deleteStmt;
    }

}