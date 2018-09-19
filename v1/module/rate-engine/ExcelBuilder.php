<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as ExcelWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Description of ExcelBuilder
 *
 * @author perce
 */
class ExcelBuilder {

    private $_excelWriter, $_excelSheet;

    //put your code here
    function __construct() {
        $this->_excelSheet = new Spreadsheet();
        $this->_excelSheet->getActiveSheet()->setTitle("Country");
        $this->_excelWriter = new ExcelWriter($this->_excelSheet);
    }

    public function download($filename="Rate-Data") {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($this->_excelSheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function addHeader($header = []) {
        $start = 'A';
        foreach ($header as $i => $h) {
            $this->_excelSheet->getActiveSheet()
                    ->setCellValue($start . '1', $h);
            $this->_excelSheet->getActiveSheet()
                    ->getStyle($start . '1')
                    ->getFont()->setBold(TRUE);
            $start++;
            $this->_excelSheet->getActiveSheet()
                    ->freezePane($start . '2');
        }
        return $this;
    }

    public function addData($data = []) {
        $line = 2;
        foreach ($data as $h) {
            $start = 'A';
            foreach ($h as $i => $t) {
                $this->_excelSheet->getActiveSheet()
                        ->setCellValue($start . $line, $t);
                $start++;
            }
            $line++;
        }
        return $this;
    }

    public function addSheet($sheetName = "") {
        $this->_excelSheet->createSheet()->setTitle($sheetName);
        return $this;
    }

    public function changeSheetByName($name) {
        $this->_excelSheet->setActiveSheetIndexByName($name);
        return $this;
    }

    public function resetActiveSheet() {
        $this->_excelSheet->setActiveSheetIndex(0);
        return $this;
    }

    public function removeSheetByIndex($index) {
        $this->_excelSheet->removeSheetByIndex($index);
        return $this;
    }

    public function addSelectOption($cellColumn, $listElement,$rows) {
        
        for ($i = 2; $i < $rows+101; $i++) {
            $validation = $this->_excelSheet->getActiveSheet()->getCell($cellColumn . $i)
                    ->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the drop-down list.');
            $validation->setFormula1('"'.$listElement.'"');
        }
        return $this;
    }

}
