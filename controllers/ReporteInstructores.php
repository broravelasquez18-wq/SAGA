<?php
session_start();
require_once "../config/conexion.php";
require_once '../vendor/autoload.php';

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$con = conexion();

// ⭐ OBTENER SEDE_ID
$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;
if($sede_id <= 0) {
    header("Location: ../views/admin/index_sedes.php");
    exit();
}

// Obtener nombre de la sede
$sede_query = mysqli_query($con, "SELECT nombre FROM sedes WHERE id = $sede_id");
$sede_nombre = mysqli_fetch_assoc($sede_query)['nombre'];

$formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf']) ? $_GET['formato'] : 'excel';

// ⭐ QUERY FILTRADA POR SEDE
$query = "SELECT * FROM usuarios 
          WHERE rol = 'instructor' 
          AND (sede_id = $sede_id OR (tipo_contrato = 'contratista' AND sede_id IS NULL))
          ORDER BY estado DESC, nombre ASC";
$result = mysqli_query($con, $query);

// ⭐ ESTADÍSTICAS FILTRADAS
$total = mysqli_num_rows($result);
$activos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND estado='activo' AND (sede_id = $sede_id OR (tipo_contrato = 'contratista' AND sede_id IS NULL))"))['total'];
$inactivos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND estado='inactivo' AND (sede_id = $sede_id OR (tipo_contrato = 'contratista' AND sede_id IS NULL))"))['total'];
$fecha_limite = date('Y-m-d', strtotime('+30 days'));
$por_vencer = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND tipo_contrato='contratista' AND estado='activo' AND fecha_fin_contrato <= '$fecha_limite' AND fecha_fin_contrato >= CURDATE() AND (sede_id = $sede_id OR sede_id IS NULL)"))['total'];

if($formato == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Instructores');
    
    $sheet->mergeCells('A1:I1');
    $sheet->setCellValue('A1', 'REPORTE DE INSTRUCTORES - ' . strtoupper($sede_nombre));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A52');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    $sheet->mergeCells('A2:I2');
    $sheet->setCellValue('A2', 'Sistema SAGA - ' . date('d/m/Y H:i'));
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 4;
    $sheet->mergeCells("A$row:I$row");
    $sheet->setCellValue("A$row", 'ESTADÍSTICAS');
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3EB489');
    $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue("A$row", 'Total:'); $sheet->setCellValue("B$row", $total);
    $sheet->setCellValue("D$row", 'Activos:'); $sheet->setCellValue("E$row", $activos);
    $sheet->setCellValue("G$row", 'Inactivos:'); $sheet->setCellValue("H$row", $inactivos);
    $sheet->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
    $sheet->getStyle("A$row:I$row")->getFont()->setBold(true);
    
    $row = 7;
    $headers = ['ID', 'Nombre', 'Cédula', 'Nivel Estudio', 'Estado', 'Contrato', 'Inicio', 'Fin', 'Días'];
    $col = 'A';
    foreach($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle("A$row:I$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A52');
    $sheet->getStyle("A$row:I$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $row++;
    mysqli_data_seek($result, 0);
    while($data = mysqli_fetch_assoc($result)) {
        $nombre = $data['nombre'] . ' ' . $data['apellido'];
        $dias = '-';
        $color = $data['estado'] == 'activo' ? 'D1FAE5' : 'FEE2E2';
        
        if($data['tipo_contrato'] == 'contratista' && $data['fecha_fin_contrato']) {
            $fecha_fin = new DateTime($data['fecha_fin_contrato']);
            $hoy = new DateTime();
            if($fecha_fin >= $hoy) {
                $dif = $hoy->diff($fecha_fin);
                $dias = $dif->days;
                if($dias <= 30) $color = 'FED7AA';
            } else {
                $dias = 'Vencido';
                $color = 'FECACA';
            }
        }
        
        $sheet->setCellValue("A$row", $data['id']);
        $sheet->setCellValue("B$row", $nombre);
        $sheet->setCellValue("C$row", $data['cedula']);
        $sheet->setCellValue("D$row", ucfirst($data['nivel_estudio'] ?? '-'));
        $sheet->setCellValue("E$row", strtoupper($data['estado']));
        $sheet->setCellValue("F$row", ucfirst($data['tipo_contrato'] ?? '-'));
        $sheet->setCellValue("G$row", $data['fecha_inicio_contrato'] ? date('d/m/Y', strtotime($data['fecha_inicio_contrato'])) : '-');
        $sheet->setCellValue("H$row", $data['fecha_fin_contrato'] ? date('d/m/Y', strtotime($data['fecha_fin_contrato'])) : '-');
        $sheet->setCellValue("I$row", $dias);
        
        $sheet->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        $sheet->getStyle("A$row:I$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }
    
    foreach(range('A','I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Instructores_' . $sede_nombre . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} else {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SAGA');
    $pdf->SetTitle('Reporte Instructores - ' . $sede_nombre);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 12, 'REPORTE DE INSTRUCTORES', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, strtoupper($sede_nombre), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Sistema SAGA - ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFillColor(62, 180, 137);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'ESTADÍSTICAS', 0, 1, 'L', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 6, 'Total: ' . $total, 0, 0);
    $pdf->Cell(60, 6, 'Activos: ' . $activos, 0, 0);
    $pdf->Cell(60, 6, 'Inactivos: ' . $inactivos, 0, 1);
    $pdf->Ln(5);
    
    $pdf->SetFillColor(30, 58, 82);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    
    $pdf->Cell(10, 8, 'ID', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Cédula', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Nivel', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Contrato', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Inicio', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Fin', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Días', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 7);
    
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        $nombre = $row['nombre'] . ' ' . $row['apellido'];
        $dias = '-';
        
        $pdf->SetFillColor($row['estado'] == 'activo' ? 209 : 254, $row['estado'] == 'activo' ? 250 : 226, $row['estado'] == 'activo' ? 229 : 226);
        
        if($row['tipo_contrato'] == 'contratista' && $row['fecha_fin_contrato']) {
            $fecha_fin = new DateTime($row['fecha_fin_contrato']);
            $hoy = new DateTime();
            if($fecha_fin >= $hoy) {
                $dias = $hoy->diff($fecha_fin)->days;
                if($dias <= 30) $pdf->SetFillColor(254, 215, 170);
            } else {
                $dias = 'Vencido';
            }
        }
        
        $pdf->Cell(10, 6, $row['id'], 1, 0, 'C', true);
        $pdf->Cell(50, 6, substr($nombre, 0, 30), 1, 0, 'L', true);
        $pdf->Cell(25, 6, $row['cedula'], 1, 0, 'C', true);
        $pdf->Cell(30, 6, ucfirst($row['nivel_estudio'] ?? '-'), 1, 0, 'C', true);
        $pdf->Cell(20, 6, strtoupper($row['estado']), 1, 0, 'C', true);
        $pdf->Cell(25, 6, ucfirst($row['tipo_contrato'] ?? '-'), 1, 0, 'C', true);
        $pdf->Cell(25, 6, ($row['fecha_inicio_contrato'] ? date('d/m/Y', strtotime($row['fecha_inicio_contrato'])) : '-'), 1, 0, 'C', true);
        $pdf->Cell(25, 6, ($row['fecha_fin_contrato'] ? date('d/m/Y', strtotime($row['fecha_fin_contrato'])) : '-'), 1, 0, 'C', true);
        $pdf->Cell(20, 6, $dias, 1, 1, 'C', true);
    }
    
    $pdf->Output('Reporte_Instructores_' . $sede_nombre . '_' . date('Y-m-d') . '.pdf', 'D');
}

mysqli_close($con);
?>