<?php

class Pod_Signatre
{

    public

    function saveImage($file_name, $encoded_string){
        $libObj = new Library();
        $path = $libObj->base_path()."/pod/signature";

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $data = $encoded_string;
        $type = array("png");

        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        if(file_put_contents("$path/$file_name.{$type}", $data)){

            return $libObj->get_api_url()."pod/signature/$file_name.{$type}";
        }
        return false;
    }
}