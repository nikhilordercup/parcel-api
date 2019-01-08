<?php
class App_Logger
{
    private static $loggerObj = null;
    private $_logPath = "../../app-logger";

    public static function _getInstance()
    {
        if (self::$loggerObj===null) {
            self::$loggerObj = new App_Logger();
        }
        return self::$loggerObj;
    }

    private function _getUserId($json)
    {
        if (isset($json->user_code)) {
            return $json->user_code;
        } elseif (isset($json->driver_id)) {
            return $json->driver_id;
        } elseif (isset($json->user_id)) {
            return $json->user_id;
        } elseif (isset($json->driverCode)) {
            return $json->driverCode;
        }
        return 0;
    }

    private function _getLogPath()
    {
        $date = date("d-M-Y");
        return "$this->_logPath/$date/$this->driverId";
    }

    private function _getFileName($file, $version=0)
    {
        if (file_exists($file)) {
            $version++;
            $file = "$this->_logPath/log $version.txt";
            return $this->_getFileName($file, $version);
        } else {
            return "$file";
        }
    }

    private function _saveLog($param)
    {
        $filePath = "$this->_logPath/log.txt";
        $filePath = $this->_getFileName($filePath);
        $fp = fopen($filePath, "w") or die('Permission error');
        fwrite($fp, $param);
        fclose($fp);
    }

    public function _logEvent($param)
    {
        $this->driverId = $this->_getUserId($param);
        $this->_logPath = $this->_getLogPath();
        if (!file_exists($this->_logPath)) {
            mkdir($this->_logPath, 0777, true);
        }
        $this->_saveLog(json_encode($param));
    }
}
