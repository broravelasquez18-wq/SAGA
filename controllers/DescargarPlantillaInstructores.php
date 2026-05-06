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
$sheet->setTitle('Instructores');

// HEADERS
$headers = ['cedula', 'nombre', 'apellido', 'email', 'contraseña', 'nivel_estudio', 'tipo_contrato', 'fecha_inicio_contrato', 'fecha_fin_contrato'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilos del header
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a52']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(16);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(22);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(22);
$sheet->getColumnDimension('I')->setWidth(22);

// EJEMPLOS DE DATOS
$ejemplos = [
    ['12345678', 'Carlos',  'Gómez',   'cgomez@sena.edu.co',   'clave123', 'profesional',    'planta',      '',           ''],
    ['87654321', 'María',   'Torres',  'mtorres@sena.edu.co',  'clave456', 'maestria',        'contratista', '2026-01-01', '2026-12-31'],
    ['11223344', 'Juan',    'Pérez',   'jperez@sena.edu.co',   'clave789', 'tecnologo',       'planta',      '',           ''],
    ['55667788', 'Claudia', 'Ríos',    'crios@sena.edu.co',    'clave321', 'especializacion', 'contratista', '2026-03-01', '2026-11-30'],
    ['99001122', 'Andrés',  'Morales', 'amorales@sena.edu.co', 'clave654', 'doctorado',       'planta',      '',           ''],
];

$sheet->fromArray($ejemplos, NULL, 'A2');

$dataStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
];
$sheet->getStyle('A2:I6')->applyFromArray($dataStyle);

// HOJA DE INSTRUCCIONES
$instrucciones = $spreadsheet->createSheet();
$instrucciones->setTitle('Instrucciones');

$instrucciones->setCellValue('A1', 'INSTRUCCIONES PARA CARGA MASIVA DE INSTRUCTORES');
$instrucciones->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1e3a52');
$instrucciones->mergeCells('A1:D1');

$instrucciones->setCellValue('A3', 'FORMATO DE COLUMNAS:');
$instrucciones->getStyle('A3')->getFont()->setBold(true)->setSize(12);

$info = [
    ['cedula',                 'Número de cédula del instructor (único en el sistema)',                      'Obligatorio'],
    ['nombre',                 'Nombres del instructor',                                                     'Obligatorio'],
    ['apellido',               'Apellidos del instructor',                                                   'Obligatorio'],
    ['email',                  'Correo electrónico válido (único en el sistema)',                            'Obligatorio'],
    ['contraseña',             'Contraseña de acceso al sistema',                                           'Obligatorio'],
    ['nivel_estudio',          'tecnico / tecnologo / profesional / especializacion / maestria / doctorado', 'Obligatorio'],
    ['tipo_contrato',          'planta / contratista',                                                      'Obligatorio'],
    ['fecha_inicio_contrato',  'Fecha inicio YYYY-MM-DD — solo si tipo_contrato=contratista',               'Condicional'],
    ['fecha_fin_contrato',     'Fecha fin YYYY-MM-DD — solo si tipo_contrato=contratista',                  'Condicional'],
];

$instrucciones->fromArray(['Columna', 'Descripción', 'Requerido'], NULL, 'A4');
$instrucciones->getStyle('A4:C4')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
]);
$instrucciones->fromArray($info, NULL, 'A5');

$instrucciones->getColumnDimension('A')->setWidth(24);
$instrucciones->getColumnDimension('B')->setWidth(65);
$instrucciones->getColumnDimension('C')->setWidth(15);

$instrucciones->setCellValue('A15', 'NOTAS IMPORTANTES:');
$instrucciones->getStyle('A15')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('F59E0B');
$instrucciones->setCellValue('A16', '⚠️ La cédula y el email deben ser únicos en todo el sistema');
$instrucciones->setCellValue('A17', '⚠️ Los instructores de tipo "planta" quedan asignados a la sede desde la que se carga el archivo');
$instrucciones->setCellValue('A18', '⚠️ Los instructores "contratista" pueden acceder a todas las sedes y requieren fechas de contrato');
$instrucciones->setCellValue('A19', '⚠️ El nivel_estudio debe ser exactamente uno de los valores permitidos (sin tildes en "tecnico", "tecnologo")');
$instrucciones->setCellValue('A20', '✅ Máximo 200 instructores por carga');

// Volver a la hoja principal
$spreadsheet->setActiveSheetIndex(0);

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Carga_Masiva_Instructores.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
