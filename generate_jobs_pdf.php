<?php
require_once 'TCPDF-main/tcpdf.php';

session_start();
$host = 'localhost';
$db = 'taskmate';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

$sql_jobs = "
    SELECT 
        j.job_id,
        j.client_id,
        j.freelancer_id,
        j.job_title,
        j.task_category,
        j.budget,
        j.job_status,
        j.deadline,
        j.date_posted,
        c.name AS client_name,
        f.name AS freelancer_name,
        jp.payment_status,
        SUM(CASE WHEN jp.payment_status = 'Completed' THEN jp.amount ELSE 0 END) AS total_paid
    FROM jobs j
    LEFT JOIN users c ON j.client_id = c.id
    LEFT JOIN users f ON j.freelancer_id = f.id
    LEFT JOIN jobs_payments jp ON j.job_id = jp.job_id
    GROUP BY j.job_id, j.client_id, j.freelancer_id, j.job_title, j.task_category, j.budget, 
             j.job_status, j.deadline, j.date_posted, c.name, f.name, jp.payment_status
    ORDER BY j.date_posted DESC
";
$result_jobs = $conn->query($sql_jobs);

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('TaskMate');
$pdf->SetAuthor('TaskMate Admin');
$pdf->SetTitle('Jobs Report');
$pdf->SetSubject('TaskMate Jobs Overview');

$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 8);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'TaskMate Jobs Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date('d M Y'), 0, 1, 'C');
$pdf->Ln(5);

$html = '<table border="1" cellpadding="4">
    <thead>
        <tr style="background-color:#f8fafc;">
            <th>Job ID</th>
            <th>Job Title</th>
            <th>Client</th>
            <th>Freelancer</th>
            <th>Category</th>
            <th>Budget/Paid</th>
            <th>Status</th>
            <th>Deadline</th>
            <th>Date Posted</th>
        </tr>
    </thead>
    <tbody>';

if ($result_jobs->num_rows > 0) {
    while ($job = $result_jobs->fetch_assoc()) {
        $status_color = '';
        switch (strtolower($job['job_status'])) {
            case 'open': $status_color = '#fef3c7'; break;
            case 'in progress': $status_color = '#dbeafe'; break;
            case 'completed': $status_color = '#dcfce7'; break;
        }

        $html .= '<tr>
            <td>' . $job['job_id'] . '</td>
            <td>' . htmlspecialchars($job['job_title']) . '</td>
            <td>' . htmlspecialchars($job['client_name']) . '</td>
            <td>' . ($job['freelancer_name'] ? htmlspecialchars($job['freelancer_name']) : 'Not Assigned') . '</td>
            <td>' . htmlspecialchars($job['task_category']) . '</td>
            <td>' . number_format($job['budget'], 2) . '<br>Paid: ' . number_format($job['total_paid'] ?? 0, 2) . '</td>
            <td style="background-color:' . $status_color . '">' . htmlspecialchars($job['job_status']) . '</td>
            <td>' . date('d M Y', strtotime($job['deadline'])) . '</td>
            <td>' . date('d M Y', strtotime($job['date_posted'])) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="9" style="text-align:center;">No jobs found.</td></tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('TaskMate_Jobs_Report.pdf', 'D');

$conn->close();
?>