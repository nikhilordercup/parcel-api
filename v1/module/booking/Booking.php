<?php
class Booking extends Icargo
{
    public function __construct($data){return true;
        $this->_parentObj = parent::__construct(array("email" => $data["email"], "access_token" => $data["access_token"]));
    }

}
?>