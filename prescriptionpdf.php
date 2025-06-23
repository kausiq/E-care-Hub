<?php
require 'connection.php';
require 'vendor/autoload.php'; // Require Composer's autoloader for DomPDF

// Removed duplicate 'use Dompdf\Options;' declaration
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$prescriptionId = $_GET['id'] ?? null;

if (!$prescriptionId) {
    header('Location: dashboard.php');
    exit();
}

// Get prescription details
$stmt = $pdo->prepare("SELECT p.*, 
                      u.name as doctor_name, d.specialty as doctor_specialty,
                      pat.name as patient_name
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.id
                      JOIN doctors d ON p.doctor_id = d.user_id
                      JOIN users pat ON p.patient_id = pat.id
                      WHERE p.id = ?");
$stmt->execute([$prescriptionId]);
$prescription = $stmt->fetch();

if (!$prescription) {
    header('Location: dashboard.php');
    exit();
}

// Check if current user is either the doctor who created it or the patient it's for
if ($prescription['doctor_id'] != $userId && $prescription['patient_id'] != $userId) {
    header('Location: dashboard.php');
    exit();
}

// Get medicines
$stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
$stmt->execute([$prescriptionId]);
$medicines = $stmt->fetchAll();

use Dompdf\Dompdf;
use Dompdf\Options;

// Create PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prescription #' . $prescriptionId . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #4361ee;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4361ee;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 50px;
            border-top: 2px solid #4361ee;
            padding-top: 20px;
        }
        .signature {
            margin-top: 50px;
            width: 250px;
            border-top: 1px solid #333;
            text-align: center;
            padding-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .section-title {
            color: #4361ee;
            margin: 20px 0 10px 0;
        }
        .clinic-info {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="60%">
                    <h1>E Care Hub</h1>
                    <p>123 Medical Street</p>
                    <p>Healthcare City, HC 12345</p>
                    <p>Phone: (123) 456-7890</p>
                </td>
                <td width="40%" style="text-align: right;">
                    <h2>PRESCRIPTION</h2>
                    <p><strong>Date:</strong> ' . date('M j, Y', strtotime($prescription['created_at'])) . '</p>
                    <p><strong>Prescription ID:</strong> ' . $prescriptionId . '</p>
                    ' . ($prescription['appointment_id'] ? '<p><strong>Appointment ID:</strong> ' . $prescription['appointment_id'] . '</p>' : '') . '
                </td>
            </tr>
        </table>
    </div>
    
    <table width="100%">
        <tr>
            <td width="50%">
                <h3 class="section-title">Patient Information</h3>
                <p><strong>Name:</strong> ' . htmlspecialchars($prescription['patient_name']) . '</p>
                <p><strong>Prescribed On:</strong> ' . date('M j, Y', strtotime($prescription['created_at'])) . '</p>
            </td>
            <td width="50%">
                <h3 class="section-title">Doctor Information</h3>
                <p><strong>Name:</strong> Dr. ' . htmlspecialchars($prescription['doctor_name']) . '</p>
                <p><strong>Specialty:</strong> ' . htmlspecialchars($prescription['doctor_specialty']) . '</p>
            </td>
        </tr>
    </table>
    
    <h3 class="section-title">Medicines Prescribed</h3>';
    
if (empty($medicines)) {
    $html .= '<p>No medicines prescribed</p>';
} else {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($medicines as $medicine) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($medicine['medicine_name']) . '</td>
                <td>' . htmlspecialchars($medicine['dosage']) . '</td>
                <td>' . htmlspecialchars($medicine['frequency']) . '</td>
                <td>' . htmlspecialchars($medicine['duration']) . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
}

if (!empty($prescription['notes'])) {
    $html .= '
    <h3 class="section-title">Clinical Notes</h3>
    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 20px;">
        ' . nl2br(htmlspecialchars($prescription['notes'])) . '
    </div>';
}

if (!empty($prescription['advice'])) {
    $html .= '
    <h3 class="section-title">Medical Advice</h3>
    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 20px;">
        ' . nl2br(htmlspecialchars($prescription['advice'])) . '
    </div>';
}

$html .= '
    <div class="footer">
        <table width="100%">
            <tr>
                <td width="60%">
                    <h3 class="section-title">Pharmacy Instructions</h3>
                    <p>Please dispense as written. No substitutions allowed.</p>
                </td>
                <td width="40%" style="text-align: right;">
                    <div class="signature">
                        <p>Dr. ' . htmlspecialchars($prescription['doctor_name']) . '</p>
                        <p>License: MH123456</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF
$dompdf->stream('prescription_' . $prescriptionId . '.pdf', ['Attachment' => true]);