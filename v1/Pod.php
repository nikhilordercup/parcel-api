<?php
require_once "./Resize.php";
class Pod
{
    public static $_libObj = null;

    public $path = null;

    private $_ext = array("png","jpeg","jpg");

    public

    function __construct(){
        if(self::$_libObj===null)
            self::$_libObj = new Library();

        $this->libObj = self::$_libObj;
    }

    private

    function _getPath($folder){
      $path = $this->libObj->base_path()."/pod/$folder";

      if (!file_exists($path)) {
          mkdir($path, 0777, true);
      }
      return $path;
    }

    private

    function _saveImage($file_name, $encoded_string){
        $data = $encoded_string;
        $type = $this->_ext;

        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $this->type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($this->type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                throw new \Exception('invalid image type');
            }
            $this->dataString = str_replace(' ', '+', $data);
            $data = base64_decode($this->dataString);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            } else {
              $destinatib_img = $this->_checkFileExist("$this->path/$file_name.{$this->type}");

              $fp = fopen("$destinatib_img",'wb+');
              $imgStatus = fwrite($fp, $data);
              fclose($fp);

              if($imgStatus)
                  return $this->_getFileName($destinatib_img).".{$this->type}";

              return false;
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }
    }

    private

    function _getFileExtension($file){
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    private

    function _getFileName($file){
        return pathinfo($file, PATHINFO_FILENAME);;
    }

    private

    function _checkFileExist($destinatib_img, $counter=0){
        if(file_exists($destinatib_img)){
            $counter++;
            $newFileName = preg_replace('/[\[{\(].*[\]}\)]/U' , "", $this->_getFileName($destinatib_img));
            $newFileName = "$this->path/$newFileName($counter).{$this->type}";
            return $this->_checkFileExist($newFileName, $counter);
        }
        return $destinatib_img;
    }

    public

    function savePodSignature($file_name, $encoded_string){
        $folder = "signature";
        $this->path = $this->_getPath($folder);
        $file = $this->_saveImage($file_name, $encoded_string);
        $file = $this->libObj->get_api_url()."pod/$folder/$file";
        return $file;
    }

    public

    function savePodPicture($file_name, $encoded_string){
        $folder = "picture";
        $this->path = $this->_getPath($folder);
        $file = $this->_saveImage($file_name, $encoded_string);

        if($file){
            $file = $this->libObj->get_api_url()."pod/$folder/$file";

            return $file;
        }
        return $filePath;
    }
}
