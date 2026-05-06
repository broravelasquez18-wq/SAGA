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

$sede_query = mysqli_query($con, "SELECT nombre FROM sedes WHERE id = $sede_id");
$sede_nombre = mysqli_fetch_assoc($sede_query)['nombre'];

$formato = in_array($_GET['formato'] ?? '', ['excel', 'pdf']) ? $_GET['formato'] : 'excel';

// ⭐ QUERY FILTRADA POR SEDE
$query = "SELECT * FROM usuarios WHERE rol = 'celador' AND sede_id = $sede_id ORDER BY estado DESC, nombre ASC";
$result = mysqli_query($con, $query);

// ⭐ ESTADÍSTICAS FILTRADAS
$total = mysqli_num_rows($result);
$activos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND estado='activo' AND sede_id = $sede_id"))['total'];
$inactivos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND estado='inactivo' AND sede_id = $sede_id"))['total'];
$fecha_limite = date('Y-m-d', strtotime('+30 days'));
$por_vencer = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND tipo_contrato='contratista' AND estado='activo' AND fecha_fin_contrato <= '$fecha_limite' AND fecha_fin_contrato >= CURDATE() AND sede_id = $sede_id"))['total'];

if($formato == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Celadores');
    
    $sheet->mergeCells('A1:H1');
    $sheet->setCellValue('A1', 'REPORTE DE CELADORES - ' . strtoupper($sede_nombre));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('8B5CF6');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    $sheet->mergeCells('A2:H2');
    $sheet->setCellValue('A2', 'Sistema SAGA - Generado: ' . date('d/m/Y H:i'));
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 4;
    $sheet->mergeCells("A$row:H$row");
    $sheet->setCellValue("A$row", 'ESTADÍSTICAS GENERALES');
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('8B5CF6');
    $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue("A$row", 'Total Celadores:');
    $sheet->setCellValue("B$row", $total);
    $sheet->setCellValue("D$row", 'Activos:');
    $sheet->setCellValue("E$row", $activos);
    $sheet->setCellValue("G$row", 'Inactivos:');
    $sheet->setCellValue("H$row", $inactivos);
    $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
    $sheet->getStyle("A$row:H$row")->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue("A$row", 'Por Vencer (30 días):');
    $sheet->setCellValue("B$row", $por_vencer);
    $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
    $sheet->getStyle("A$row:H$row")->getFont()->setBold(true);
    
    $row = 8;
    $headers = ['ID', 'Nombre Completo', 'Cédula', 'Estado', 'Tipo Contrato', 'Fecha Inicio', 'Fecha Fin', 'Días Restantes'];
    $col = 'A';
    foreach($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    
    $sheet->getStyle("A$row:H$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('8B5CF6');
    $sheet->getStyle("A$row:H$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A$row:H$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $row++;
    mysqli_data_seek($result, 0);
    while($data = mysqli_fetch_assoc($result)) {
        $nombre_completo = $data['nombre'] . ' ' . $data['apellido'];
        $dias_restantes = '-';
        $color = 'FFFFFF';
        
        if($data['estado'] == 'activo') {
            $color = 'D1FAE5';
        } else {
            $color = 'FEE2E2';
        }
        
        if($data['tipo_contrato'] == 'contratista' && $data['fecha_fin_contrato']) {
            $fecha_fin = new DateTime($data['fecha_fin_contrato']);
            $hoy = new DateTime();
            if($fecha_fin >= $hoy) {
                $diferencia = $hoy->diff($fecha_fin);
                $dias_restantes = $diferencia->days;
                if($dias_restantes <= 30) {
                    $color = 'FED7AA';
                }
            } else {
                $dias_restantes = 'Vencido';
                $color = 'FECACA';
            }
        }
        
        $sheet->setCellValue("A$row", $data['id']);
        $sheet->setCellValue("B$row", $nombre_completo);
        $sheet->setCellValue("C$row", $data['cedula']);
        $sheet->setCellValue("D$row", strtoupper($data['estado']));
        $sheet->setCellValue("E$row", ucfirst($data['tipo_contrato'] ?? '-'));
        $sheet->setCellValue("F$row", $data['fecha_inicio_contrato'] ? date('d/m/Y', strtotime($data['fecha_inicio_contrato'])) : '-');
        $sheet->setCellValue("G$row", $data['fecha_fin_contrato'] ? date('d/m/Y', strtotime($data['fecha_fin_contrato'])) : '-');
        $sheet->setCellValue("H$row", $dias_restantes);
        
        $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        $sheet->getStyle("A$row:H$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row++;
    }
    
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(35);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(18);
    
    $filename = 'Reporte_Celadores_' . $sede_nombre . '_' . date('Y-m-d') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} else if($formato == 'pdf') {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SAGA');
    $pdf->SetTitle('Reporte de Celadores - ' . $sede_nombre);
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 12, 'REPORTE DE CELADORES', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, strtoupper($sede_nombre), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Sistema SAGA - Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFillColor(139, 92, 246);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'ESTADÍSTICAS GENERALES', 0, 1, 'L', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 6, 'Total: ' . $total, 0, 0);
    $pdf->Cell(60, 6, 'Activos: ' . $activos, 0, 0);
    $pdf->Cell(60, 6, 'Inactivos: ' . $inactivos, 0, 0);
    $pdf->Cell(60, 6, 'Por Vencer: ' . $por_vencer, 0, 1);
    $pdf->Ln(5);
    
    $pdf->SetFillColor(30, 58, 82);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    
    $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
    $pdf->Cell(60, 8, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Cédula', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Contrato', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Inicio', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Fin', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Días', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        $nombre_completo = $row['nombre'] . ' ' . $row['apellido'];
        $dias_restantes = '-';
        
        if($row['estado'] == 'activo') {
            $pdf->SetFillColor(209, 250, 229);
        } else {
            $pdf->SetFillColor(254, 226, 226);
        }
        
        if($row['tipo_contrato'] == 'contratista' && $row['fecha_fin_contrato']) {
            $fecha_fin = new DateTime($row['fecha_fin_contrato']);
            $hoy = new DateTime();
            if($fecha_fin >= $hoy) {
                $diferencia = $hoy->diff($fecha_fin);
                $dias_restantes = $diferencia->days;
                if($dias_restantes <= 30) {
                    $pdf->SetFillColor(254, 215, 170);
                }
            } else {
                $dias_restantes = 'Vencido';
            }
        }
        
        $pdf->Cell(15, 6, $row['id'], 1, 0, 'C', true);
        $pdf->Cell(60, 6, substr($nombre_completo, 0, 35), 1, 0, 'L', true);
        $pdf->Cell(30, 6, $row['cedula'], 1, 0, 'C', true);
        $pdf->Cell(25, 6, strtoupper($row['estado']), 1, 0, 'C', true);
        $pdf->Cell(30, 6, ucfirst($row['tipo_contrato'] ?? '-'), 1, 0, 'C', true);
        $pdf->Cell(30, 6, ($row['fecha_inicio_contrato'] ? date('d/m/Y', strtotime($row['fecha_inicio_contrato'])) : '-'), 1, 0, 'C', true);
        $pdf->Cell(30, 6, ($row['fecha_fin_contrato'] ? date('d/m/Y', strtotime($row['fecha_fin_contrato'])) : '-'), 1, 0, 'C', true);
        $pdf->Cell(30, 6, $dias_restantes, 1, 1, 'C', true);
    }
    
    $pdf->Output('Reporte_Celadores_' . $sede_nombre . '_' . date('Y-m-d') . '.pdf', 'D');
}

mysqli_close($con);
?>