<?php

require_once './Database.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MigrationFileManager
 *
 * @author perce_qzotijf
 */
class MigrationFileManager {

    public function getFileList() {
        $files = scandir('./sql/');
        foreach ($files as $k => $file) {
            if (!$this->endsWith($file, '.sql'))
                unset($files[$k]);
        }
        return $files;
    }

    private function endsWith($haystack, $needle) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public function readConfig() {
        return json_decode(file_get_contents('./config.json'));
    }

    public function getProcessedFiles() {
        $db = Database::get();
        return $db->select(' * FROM icargo_migration');
    }

    public function createMigrationTable() {
        $sql = 'CREATE TABLE IF NOT EXISTS `icargo_migration`'
                . ' ( `id` INT NOT NULL AUTO_INCREMENT , `file_name` VARCHAR(250) NOT NULL ,'
                . ' `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , '
                . 'PRIMARY KEY (`id`)) ENGINE = MyISAM;';
        $db = Database::get();
        $db->raw($sql);
    }

    public function checkMigrationStatus($fileName, $history) {
        $found = array(
            'file_name' => $fileName,
            'applied_at' => 'N/A',
            'status' => FALSE
        );
        foreach ($history as $h) {
            if ($fileName == $h->file_name) {
                $found['applied_at'] = $h->applied_at;
                $found['status'] = TRUE;
                break;
            }
        }
        return $found;
    }

    public function applyMigration($fileName) {
        $sql = file_get_contents('./sql/' . $fileName);
        $db = Database::get();
        try {
            $db->raw($sql);
            $this->saveMigrationStatus($fileName);
            exit(json_encode(array('success' => TRUE, 'message' => 'Migration applied successfully.')));
        } catch (Exception $ex) {
            exit(json_encode(array('success' => FALSE, 'message' => $ex->getMessage())));
        }
    }

    public function saveMigrationStatus($fileName) {
        $sql = "INSERT INTO icargo_migration (file_name)values('$fileName')";
        Database::get()->raw($sql);
    }

}
