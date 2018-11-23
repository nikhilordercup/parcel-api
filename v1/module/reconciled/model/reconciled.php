<?php

class Reconciled_Model extends Icargo{ 

    public

    static $modelObj = NULL;

    public

    static $_dbObj = NULL;

    public

    function __construct()
    {
        if(self::$_dbObj==NULL)
        {
            self::$_dbObj = new DbHandler();
        }
        $this->_db = self::$_dbObj;
    }

    public

    static function _getInstance()
    {
        if(self::$modelObj==NULL)
        {
            self::$modelObj = new Addressbook_Model();
        }
        return self::$modelObj;
    }
}
?>
