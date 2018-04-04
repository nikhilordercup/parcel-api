<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 15/01/2018
 * Time: 06:18 PM
 */
class Profile
{
    private $_db;
    /**
     * Profile constructor.
     */
    public function __construct()
    {
        $this->_db=new DbHandler();
    }
    public function save($companyId,$profileType,$profileName,$profileData){
        $sql="INSERT INTO ".DB_PREFIX."csv_import_profiles (company_id,profile_type,profile_name,profile_data)".
            "VALUES($companyId,'$profileType','$profileName','$profileData')";
        $this->_db->executeQuery($sql);
    }
    public function update($profile_id,$companyId,$profileType,$profileName,$profileData){
        $sql="UPDATE ".DB_PREFIX."csv_import_profiles SET profile_name='$profileName',".
            "company_id=$companyId ,profile_type='$profileType',profile_data='$profileData' ".
            " WHERE profile_id=$profile_id";
        $this->_db->executeQuery($sql);
    }
    public function fetchAll($companyId,$profileType){
        $sql="SELECT * FROM ".DB_PREFIX."csv_import_profiles WHERE company_id=$companyId AND profile_type='$profileType'";
        return $this->_db->getAllRecords($sql);
    }
    public function delete($profileId){
        $sql="DELETE FROM ".DB_PREFIX."csv_import_profiles WHERE profile_id=$profileId";
        $this->_db->delete($sql);
    }
}
