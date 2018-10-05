<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 4:56 PM
 */

class Push_Notification_Model_Index
{
    public static $_db = NULL;

    public

    function __construct()
    {
        if (self::$_db == NULL)
        {
            self::$_db = new DbHandler();
        }
        $this->db = self::$_db;
    }

    public

    function getDeviceTokenByUserId($user_id)
    {
        $sql = "SELECT device_token_id FROM " . DB_PREFIX . "users WHERE id IN('$user_id')";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
}