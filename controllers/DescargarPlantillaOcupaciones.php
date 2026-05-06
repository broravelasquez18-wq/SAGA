<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ocupaciones');

// HEADERS
$headers = ['piso', 'ambiente', 'cedula_instructor', 'fecha', 'jornada', 'hora_inicio', 'hora_fin', 'observaciones'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilos del header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Ajustar anchos de columna
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(22);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(14);
$sheet->getColumnDimension('H')->setWidth(40);

// EJEMPLOS DE DATOS
$ejemplos = [
    ['Piso 1', 'Aula 101', '12345678', '2026-05-05', 'mañana',        '',      '',      'Formación técnica'],
    ['Piso 1', 'Aula 102', '87654321', '2026-05-05', 'tarde',         '',      '',      ''],
    ['Piso 2', 'Lab Química',  '11223344', '2026-05-06', 'noche',     '',      '',      'Práctica de laboratorio'],
    ['Piso 2', 'Sala Cómputo', '55667788', '2026-05-07', 'personalizado', '08:00', '11:00', 'Clase especial'],
    ['Edificio A', 'Auditorio', '99001122', '2026-05-08', 'mañana',   '',      '',      ''],
];

$sheet->fromArray($ejemplos, NULL, 'A2');

// Estilo de ejemplos
$dataStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]]
];
$sheet->getStyle('A2:H6')->applyFromArray($dataStyle);

// HOJA DE INSTRUCCIONES
$instrucciones = $spreadsheet->createSheet();
$instrucciones->setTitle('Instrucciones');

$instrucciones->setCellValue('A1', 'INSTRUCCIONES PARA CARGA MASIVA DE OCUPACIONES');
$instrucciones->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('2563EB');
$instrucciones->mergeCells('A1:D1');

$instrucciones->setCellValue('A3', 'FORMATO DE COLUMNAS:');
$instrucciones->getStyle('A3')->getFont()->setBold(true)->setSize(12);

$info = [
    ['piso',              'Nombre exacto del piso (ej: Piso 1, Edificio A)',                 'Obligatorio'],
    ['ambiente',          'Nombre exacto del ambiente (ej: Aula 101)',                        'Obligatorio'],
    ['cedula_instructor', 'Cédula del instructor (solo números)',                             'Obligatorio'],
    ['fecha',             'Fecha en formato YYYY-MM-DD (ej: 2026-05-15)',                     'Obligatorio'],
    ['jornada',           'mañana / tarde / noche / personalizado',                           'Obligatorio'],
    ['hora_inicio',       'Hora inicio HH:MM (ej: 08:00) — solo si jornada=personalizado',   'Condicional'],
    ['hora_fin',          'Hora fin HH:MM (ej: 11:00) — solo si jornada=personalizado',      'Condicional'],
    ['observaciones',     'Notas adicionales sobre la ocupación',                             'Opcional'],
];

$instrucciones->fromArray(['Columna', 'Descripcion', 'Requerido'], NULL, 'A4');
$instrucciones->getStyle('A4:C4')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
]);

$instrucciones->fromArray($info, NULL, 'A5');

$instrucciones->getColumnDimension('A')->setWidth(20);
$instrucciones->getColumnDimension('B')->setWidth(60);
$instrucciones->getColumnDimension('C')->setWidth(15);

$instrucciones->setCellValue('A14', 'NOTAS IMPORTANTES:');
$instrucciones->getStyle('A14')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('F59E0B');
$instrucciones->setCellValue('A15', '⚠️ El piso y el ambiente deben existir previamente en la sede');
$instrucciones->setCellValue('A16', '⚠️ El instructor debe estar registrado con esa cedula en el sistema');
$instrucciones->setCellValue('A17', '⚠️ No se permiten ocupaciones en fechas pasadas');
$instrucciones->setCellValue('A18', '⚠️ Si hay conflicto de horario en el ambiente o instructor, ese registro se omite');
$instrucciones->setCellValue('A19', '⚠️ Jornadas predefinidas: mañana=06:00-12:00 / tarde=12:00-18:00 / noche=18:00-22:00');
$instrucciones->setCellValue('A20', '✅ Maximo 200 ocupaciones por carga');

// Volver a la hoja principal
$spreadsheet->setActiveSheetIndex(0);

// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Carga_Masiva_Ocupaciones.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
