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
$sheet->setTitle('Celadores');

// HEADERS
$headers = ['cedula', 'nombre', 'apellido', 'email', 'contraseña', 'tipo_contrato', 'fecha_inicio_contrato', 'fecha_fin_contrato'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilos del header
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a52']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(16);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(22);
$sheet->getColumnDimension('H')->setWidth(22);

// EJEMPLOS DE DATOS
$ejemplos = [
    ['12345678', 'Luis',    'Ramírez', 'lramirez@sena.edu.co', 'clave123', 'planta',      '',           ''],
    ['87654321', 'Ana',     'Castro',  'acastro@sena.edu.co',  'clave456', 'contratista', '2026-01-01', '2026-12-31'],
    ['11223344', 'Pedro',   'Suárez',  'psuarez@sena.edu.co',  'clave789', 'planta',      '',           ''],
    ['55667788', 'Sandra',  'Niño',    'snino@sena.edu.co',    'clave321', 'contratista', '2026-03-01', '2026-11-30'],
    ['99001122', 'Héctor',  'Vargas',  'hvargas@sena.edu.co',  'clave654', 'planta',      '',           ''],
];

$sheet->fromArray($ejemplos, NULL, 'A2');

$dataStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
];
$sheet->getStyle('A2:H6')->applyFromArray($dataStyle);

// HOJA DE INSTRUCCIONES
$instrucciones = $spreadsheet->createSheet();
$instrucciones->setTitle('Instrucciones');

$instrucciones->setCellValue('A1', 'INSTRUCCIONES PARA CARGA MASIVA DE CELADORES');
$instrucciones->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1e3a52');
$instrucciones->mergeCells('A1:D1');

$instrucciones->setCellValue('A3', 'FORMATO DE COLUMNAS:');
$instrucciones->getStyle('A3')->getFont()->setBold(true)->setSize(12);

$info = [
    ['cedula',                'Número de cédula del celador (único en el sistema)',              'Obligatorio'],
    ['nombre',                'Nombres del celador',                                             'Obligatorio'],
    ['apellido',              'Apellidos del celador',                                           'Obligatorio'],
    ['email',                 'Correo electrónico válido (único en el sistema)',                 'Obligatorio'],
    ['contraseña',            'Contraseña de acceso al sistema',                                'Obligatorio'],
    ['tipo_contrato',         'planta / contratista',                                           'Obligatorio'],
    ['fecha_inicio_contrato', 'Fecha inicio YYYY-MM-DD — solo si tipo_contrato=contratista',    'Condicional'],
    ['fecha_fin_contrato',    'Fecha fin YYYY-MM-DD — solo si tipo_contrato=contratista',       'Condicional'],
];

$instrucciones->fromArray(['Columna', 'Descripción', 'Requerido'], NULL, 'A4');
$instrucciones->getStyle('A4:C4')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
]);
$instrucciones->fromArray($info, NULL, 'A5');

$instrucciones->getColumnDimension('A')->setWidth(24);
$instrucciones->getColumnDimension('B')->setWidth(60);
$instrucciones->getColumnDimension('C')->setWidth(15);

$instrucciones->setCellValue('A14', 'NOTAS IMPORTANTES:');
$instrucciones->getStyle('A14')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('F59E0B');
$instrucciones->setCellValue('A15', '⚠️ La cédula y el email deben ser únicos en todo el sistema');
$instrucciones->setCellValue('A16', '⚠️ Los celadores quedan asignados a la sede desde la que se carga el archivo');
$instrucciones->setCellValue('A17', '⚠️ Los contratistas requieren fechas de inicio y fin de contrato válidas');
$instrucciones->setCellValue('A18', '⚠️ La fecha fin debe ser posterior a la fecha inicio');
$instrucciones->setCellValue('A19', '✅ Máximo 200 celadores por carga');

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Carga_Masiva_Celadores.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
