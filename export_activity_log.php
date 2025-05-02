<?php
require_once 'db.php';
require('fpdf.php');

session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'official') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo
        $this->Image('img/datodalogo.jpg',10,6,30);
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Title
        $this->Cell(0,10,'Activity Log Report',0,1,'C');
        $this->Ln(5);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

$conn = $GLOBALS['conn'];

$sql = "SELECT username, action, created_at FROM activity_logs ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $line = "[" . $row['created_at'] . "] " . $row['username'] . ": " . $row['action'];
        $pdf->MultiCell(0,10,$line);
    }
} else {
    $pdf->Cell(0,10,'No activity logs available.');
}

ob_clean();
$pdf->Output('D', 'activity_log_report.pdf');
exit();
?>
