<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi untuk mendapatkan email pemilik dari database
function getOwnerEmail($conn) {
    $sql = "SELECT gmail FROM pemilik LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['gmail'];
    }
    return null;
}

// Fungsi untuk mengirim email (5 parameter)
function sendComplaintSolutionEmail($conn, $recipientEmail, $recipientName, $complaint, $solution) {
    $mail = new PHPMailer(true);
    
    try {
        // Ambil email pemilik dari database
        $ownerEmail = getOwnerEmail($conn);
        if (!$ownerEmail) {
            throw new Exception("Email pemilik tidak ditemukan di database");
        }
        
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $ownerEmail;
        $mail->Password   = 'qtrn sind lmdc tcbp'; // Ganti dengan password/App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Pengirim dan penerima
        $mail->setFrom($ownerEmail, 'Pak Resto Unikom');
        $mail->addAddress($recipientEmail, $recipientName);
        
        // Konten email
        $mail->isHTML(true);
        $mail->Subject = 'Solusi untuk Keluhan Anda di Pak Resto Unikom';
        $mail->Body    = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                    .content { padding: 20px 0; }
                    .complaint { background-color: #fff8e1; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
                    .solution { background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 15px 0; }
                    .footer { margin-top: 20px; padding-top: 10px; text-align: center; font-size: 0.9em; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Pak Resto Unikom</h2>
                    </div>
                    <div class='content'>
                        <h3>Halo $recipientName,</h3>
                        <p>Terima kasih telah memberikan masukan kepada kami. Berikut adalah tanggapan untuk keluhan Anda:</p>
                        
                        <div class='complaint'>
                            <h4>Keluhan Anda:</h4>
                            <p>$complaint</p>
                        </div>
                        
                        <div class='solution'>
                            <h4>Solusi Kami:</h4>
                            <p>$solution</p>
                        </div>
                        
                        <p>Kami sangat menghargai masukan Anda dan berkomitmen untuk meningkatkan pelayanan kami.</p>
                    </div>
                    <div class='footer'>
                        <p>Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi kami.</p>
                        <p>&copy; ".date('Y')." Pak Resto Unikom. Semua hak dilindungi.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: ".$mail->ErrorInfo);
        return false;
    }
}

// Proses form solusi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_solution'])) {
    $id_keluhan = $_POST['id_keluhan'];
    $solusi = $_POST['solusi'];
    
    // Ambil data keluhan
    $sql = "SELECT k.*, p.nama as nama_pelanggan, p.gmail as email_pelanggan 
            FROM keluhan k
            JOIN pesanan ps ON k.id_pesanan = ps.id_pesanan
            JOIN pelanggan p ON ps.id_pelanggan = p.id_pelanggan
            WHERE k.id_keluhan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_keluhan);
    $stmt->execute();
    $keluhan = $stmt->get_result()->fetch_assoc();
    
    if ($keluhan) {
        // Update solusi di database
        $update_sql = "UPDATE keluhan SET solusi = ?, status = 'selesai' WHERE id_keluhan = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $solusi, $id_keluhan);
        $update_stmt->execute();
        
        // Kirim email jika email valid
        $email_sent = false;
        if (filter_var($keluhan['email_pelanggan'], FILTER_VALIDATE_EMAIL)) {
            $email_sent = sendComplaintSolutionEmail(
            $conn, // Tambahkan parameter koneksi
            $keluhan['email_pelanggan'],
            $keluhan['nama_pelanggan'],
            $keluhan['isi_keluhan'],
            $solusi
            );
        }
        
        $_SESSION['feedback'] = [
            'status' => 'success',
            'message' => 'Solusi berhasil disimpan' . 
                        ($email_sent ? ' dan email telah dikirim' : 
                         (filter_var($keluhan['email_pelanggan'], FILTER_VALIDATE_EMAIL) ? 
                         ' tetapi email gagal dikirim' : ' (email tidak dikirim - alamat tidak valid)'))
        ];
    } else {
        $_SESSION['feedback'] = [
            'status' => 'error',
            'message' => 'Keluhan tidak ditemukan'
        ];
    }
    
    header("Location: lihat_keluhan.php");
    exit();
}

// Ambil daftar keluhan
$sql = "SELECT DISTINCT k.*, p.nama as nama_pelanggan 
        FROM keluhan k
        JOIN pesanan ps ON k.id_pesanan = ps.id_pesanan
        JOIN pelanggan p ON ps.id_pelanggan = p.id_pelanggan
        ORDER BY FIELD(k.status, 'baru', 'diproses', 'selesai'), k.tanggal_keluhan DESC";
$result = $conn->query($sql);
$keluhans = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Keluhan - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .badge-new { background-color: #dc3545; }
        .badge-process { background-color: #ffc107; color: #000; }
        .badge-done { background-color: #28a745; }
        .table-responsive { margin-top: 20px; }
        .feedback-alert { position: fixed; top: 20px; right: 20px; z-index: 1000; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Pak Resto Unikom</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/dashboard_pemilik.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_menu.php">Kelola Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Keluhan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan_penjualan.php">Laporan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="../logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['feedback'])): ?>
            <div class="feedback-alert alert alert-<?= $_SESSION['feedback']['status'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= $_SESSION['feedback']['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['feedback']); ?>
        <?php endif; ?>

        <h2 class="mb-4">Daftar Keluhan Pelanggan</h2>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Keluhan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keluhans as $keluhan): ?>
                        <tr>
                            <td><?= $keluhan['id_keluhan'] ?></td>
                            <td><?= htmlspecialchars($keluhan['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($keluhan['isi_keluhan']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($keluhan['tanggal_keluhan'])) ?></td>
                            <td>
                                <span class="badge rounded-pill 
                                    <?= $keluhan['status'] == 'baru' ? 'badge-new' : 
                                       ($keluhan['status'] == 'diproses' ? 'badge-process' : 'badge-done') ?>">
                                    <?= ucfirst($keluhan['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($keluhan['status'] != 'selesai'): ?>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#solusiModal<?= $keluhan['id_keluhan'] ?>">
                                        Beri Solusi
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Selesai</span>
                                    <?php if (!empty($keluhan['solusi'])): ?>
                                        <button class="btn btn-sm btn-info ms-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewSolutionModal<?= $keluhan['id_keluhan'] ?>">
                                            Lihat Solusi
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal untuk memberikan solusi -->
    <?php foreach ($keluhans as $keluhan): ?>
        <?php if ($keluhan['status'] != 'selesai'): ?>
            <div class="modal fade" id="solusiModal<?= $keluhan['id_keluhan'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Beri Solusi</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_keluhan" value="<?= $keluhan['id_keluhan'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Keluhan dari <?= htmlspecialchars($keluhan['nama_pelanggan']) ?></label>
                                    <div class="form-control bg-light"><?= htmlspecialchars($keluhan['isi_keluhan']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="solusi<?= $keluhan['id_keluhan'] ?>" class="form-label">Solusi</label>
                                    <textarea class="form-control" id="solusi<?= $keluhan['id_keluhan'] ?>" 
                                              name="solusi" rows="5" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" name="submit_solution" class="btn btn-primary">Simpan & Kirim Email</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Modal untuk melihat solusi -->
            <div class="modal fade" id="viewSolutionModal<?= $keluhan['id_keluhan'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Solusi untuk Keluhan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Pelanggan</label>
                                <div class="form-control bg-light"><?= htmlspecialchars($keluhan['nama_pelanggan']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Keluhan</label>
                                <div class="form-control bg-light"><?= htmlspecialchars($keluhan['isi_keluhan']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Solusi</label>
                                <div class="form-control bg-light"><?= htmlspecialchars($keluhan['solusi']) ?></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close feedback alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var alert = document.querySelector('.feedback-alert');
            if (alert) {
                setTimeout(function() {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    </script>
</body>
</html>