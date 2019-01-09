<?php

//require_once('../vendor/setasign/fpdf/fpdf.php');
//require_once('../vendor/setasign/fpdi/fpdi.php');

use setasign\Fpdi\Fpdi;


class ConcatPdf extends Fpdi
{
    public $files = array();

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function concat()
    {
        foreach($this->files AS $file) {
            $pageCount = $this->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pageId = $this->ImportPage($pageNo);
                $s = $this->getTemplatesize($pageId);
                $this->AddPage($s['orientation'], $s);
                $this->useImportedPage($pageId);
            }
        }
    }
}

?>