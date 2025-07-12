<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelService {

    public function buildExcelData($data, $headers, $styles = []) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $col = 1;
        foreach ($headers as $field => $title) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue("{$colLetter}1", $title);
            $col++;
        }

        $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '8ddcea'],
            ],
        ]);

        $row = 2;
        foreach ($data as $record) {
            $col = 1;
            foreach ($headers as $field => $_) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $cellCoordinate = "{$colLetter}{$row}";
                
                $cellValue = $record[$field] ?? '';
                $sheet->setCellValue($cellCoordinate, $cellValue);

                if (!empty($styles) && !empty($cellValue)) {
                    $this->applyCustomStyles($sheet, $cellCoordinate, $field, $styles, $cellValue);
                }

                $col++;
            }
            $row++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        foreach (range('A', $lastColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
    
        return $response;
    }

    protected function applyCustomStyles($sheet, $cellCoordinate, $field, $styles, $value) {
        if (isset($styles['columns'][$field])) {
            $sheet->getStyle($cellCoordinate)->applyFromArray($styles['columns'][$field]);
        }
        
        if (isset($styles['types'])) {
            if (is_numeric($value)) {
                if (isset($styles['types']['number'])) {
                    $this->applyNumberFormat($sheet, $cellCoordinate, $styles['types']['number']);
                }
            } elseif ($this->isDateTime($value)) {
                if (isset($styles['types']['date'])) {
                    $this->applyDateFormat($sheet, $cellCoordinate, $styles['types']['date']);
                }
            }
        }
        
        if (isset($styles['conditional'])) {
            $this->applyConditionalStyles($sheet, $cellCoordinate, $value, $styles['conditional']);
        }
    }
    
    protected function applyNumberFormat($sheet, $cellCoordinate, $numberStyle) {
        if (isset($numberStyle['format'])) {
            $sheet->getStyle($cellCoordinate)
                  ->getNumberFormat()
                  ->setFormatCode($numberStyle['format']);
        }
        
        if (isset($numberStyle['style'])) {
            $sheet->getStyle($cellCoordinate)->applyFromArray($numberStyle['style']);
        }
    }
    
    protected function applyDateFormat($sheet, $cellCoordinate, $dateStyle) {
        if (isset($dateStyle['format'])) {
            $sheet->getStyle($cellCoordinate)
                  ->getNumberFormat()
                  ->setFormatCode($dateStyle['format']);
        }
        
        if (isset($dateStyle['style'])) {
            $sheet->getStyle($cellCoordinate)->applyFromArray($dateStyle['style']);
        }
    }
    
    protected function applyConditionalStyles($sheet, $cellCoordinate, $value, $conditionalStyles) {
        foreach ($conditionalStyles as $condition => $style) {
            if ($this->evaluateCondition($value, $condition)) {
                $sheet->getStyle($cellCoordinate)->applyFromArray($style);
                break;
            }
        }
    }
    
    protected function evaluateCondition($value, $condition) {
        switch ($condition) {
            case 'negative':
                return is_numeric($value) && $value < 0;
            case 'positive':
                return is_numeric($value) && $value > 0;
            case 'zero':
                return is_numeric($value) && $value == 0;
            case 'empty':
                return empty($value);
            case 'not_empty':
                return !empty($value);
        }
    
        if (str_starts_with($condition, 'equals:')) {
            $expected = substr($condition, 7);
            return (string)$value === $expected;
        }
    
        if (str_starts_with($condition, 'in:')) {
            $list = explode(',', substr($condition, 3));
            return in_array((string)$value, $list);
        }
    
        return false;
    }
    
    protected function isDateTime($value) {
        return $value instanceof \DateTime || (is_string($value) && strtotime($value) !== false);
    }

    public function setDownloadHeaders($excelRes, $fileName = 'Archivo') {
        $fileName = $fileName . '-' . date('Y-m-d') . '.xlsx';

        if ($excelRes->headers->has('Access-Control-Allow-Origin')) {
            $excelRes->headers->remove('Access-Control-Allow-Origin');
        }
    
        //$excelRes->headers->set('Access-Control-Allow-Origin', '*');

        $excelRes->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $excelRes->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $excelRes->headers->set('Cache-Control', 'max-age=0');

        return $excelRes;
    }
}