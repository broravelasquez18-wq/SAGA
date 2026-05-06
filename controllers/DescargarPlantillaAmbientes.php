<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

require_once "../config/conexion.php";
$con = conexion();

$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ambientes');

// HEADERS (solo piso, nombre, descripcion)
$headers = ['piso', 'nombre', 'descripcion'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilos del header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3EB489']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Ajustar anchos de columna
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(45);

// EJEMPLOS DE DATOS
$ejemplos = [
    ['Piso 1', 'Aula 101', 'Salón equipado con proyector'],
    ['Piso 1', 'Aula 102', 'Salón estándar'],
    ['Piso 1', 'Aula 103', ''],
    ['Piso 2', 'Laboratorio de Química', 'Lab con 12 mesas de trabajo'],
    ['Piso 2', 'Laboratorio de Física', 'Equipado con instrumentos de medición'],
    ['Piso 2', 'Sala de Cómputo', '40 computadores disponibles'],
    ['Edificio A', 'Auditorio Principal', 'Auditorio con aire acondicionado'],
    ['Edificio B', 'Taller de Mecánica', 'Área de práctica mecánica'],
];

$sheet->fromArray($ejemplos, NULL, 'A2');

// Estilo de ejemplos
$dataStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]]
];
$sheet->getStyle('A2:C9')->applyFromArray($dataStyle);

// HOJA DE INSTRUCCIONES
$instrucciones = $spreadsheet->createSheet();
$instrucciones->setTitle('Instrucciones');

$instrucciones->setCellValue('A1', '📋 INSTRUCCIONES PARA CARGA MASIVA DE AMBIENTES');
$instrucciones->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('3EB489');
$instrucciones->mergeCells('A1:C1');

$instrucciones->setCellValue('A3', 'FORMATO DE COLUMNAS:');
$instrucciones->getStyle('A3')->getFont()->setBold(true)->setSize(12);

$info = [
    ['piso', 'Nombre del piso (ej: Piso 1, Edificio A)', 'Obligatorio'],
    ['nombre', 'Nombre del ambiente (ej: Aula 101)', 'Obligatorio'],
    ['descripcion', 'Descripción adicional del ambiente', 'Opcional'],
];

$instrucciones->fromArray(['Columna', 'Descripción', 'Requerido'], NULL, 'A4');
$instrucciones->getStyle('A4:C4')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
]);

$instrucciones->fromArray($info, NULL, 'A5');

$instrucciones->getColumnDimension('A')->setWidth(18);
$instrucciones->getColumnDimension('B')->setWidth(50);
$instrucciones->getColumnDimension('C')->setWidth(15);

$instrucciones->setCellValue('A9', 'NOTAS IMPORTANTES:');
$instrucciones->getStyle('A9')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('F59E0B');
$instrucciones->setCellValue('A10', '⚠️ El piso debe existir previamente en la sede');
$instrucciones->setCellValue('A11', '⚠️ No puede haber dos ambientes con el mismo nombre en el mismo piso');
$instrucciones->setCellValue('A12', '✅ Puedes crear cientos de ambientes en un solo archivo');

$instrucciones->setCellValue('A14', 'EJEMPLO RÁPIDO: Crear 10 aulas en Piso 1');
$instrucciones->getStyle('A14')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('3EB489');
$instrucciones->setCellValue('A15', 'Piso 1 | Aula 101 | Salón con proyector');
$instrucciones->setCellValue('A16', 'Piso 1 | Aula 102 | Salón estándar');
$instrucciones->setCellValue('A17', 'Piso 1 | Aula 103 | ');
$instrucciones->setCellValue('A18', '... (repite hasta Aula 110)');

// Volver a la hoja principal
$spreadsheet->setActiveSheetIndex(0);

// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Carga_Masiva_Ambientes.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>