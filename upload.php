<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];


    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);


    $values = [];
    foreach ($data as $row) {
        if (!empty($row['A'])) {
            $values[] = $row['A'];
        }
    }


    $json_data = json_encode(['pagetitles' => $values]);


    $ch = curl_init('https://fld.ru/api/checkPagetitles');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

    $response = curl_exec($ch);
    curl_close($ch);


    $response_data = json_decode($response, true);
    $data = $response_data['data'];


    $newSpreadsheet = new Spreadsheet();
    $newSheet = $newSpreadsheet->getActiveSheet();


    $row = 1;
    foreach ($data as $key => $value) {
        $newSheet->setCellValue('A' . $row, $key);
        $newSheet->setCellValue('B' . $row, $value ? 'true' : 'false');
        $row++;
    }


    $newFileName = 'response_' . time() . '.xlsx';
    $writer = new Xlsx($newSpreadsheet);
    $writer->save($newFileName);


    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($newFileName) . '"');
    header('Content-Length: ' . filesize($newFileName));
    readfile($newFileName);

    
    unlink($newFileName);
    exit;
} else {
    echo "No file uploaded or invalid request.";
}
