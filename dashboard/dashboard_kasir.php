<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Dapatkan id_kasir dari session
$id_akun = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id_kasir FROM kasir WHERE id_akun = ?");
$stmt->bind_param("i", $id_akun);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!$data) {
    die("Data kasir tidak ditemukan untuk akun ini.");
}
$id_kasir = $data['id_kasir'];

// Hitung total pendapatan hari ini
$today = date('Y-m-d');
$sql_pendapatan = "SELECT COALESCE(SUM(pb.total), 0) as total_hari_ini 
                   FROM pembayaran pb
                   WHERE DATE(pb.waktu_pembayaran) = ?
                   AND pb.status = 'dibayar'";
$stmt_pendapatan = $conn->prepare($sql_pendapatan);
$stmt_pendapatan->bind_param("s", $today);
$stmt_pendapatan->execute();
$pendapatan = $stmt_pendapatan->get_result()->fetch_assoc()['total_hari_ini'];

// Hitung transaksi belum dibayar
$sql_belum_bayar = "SELECT COUNT(*) as total_belum_bayar 
                    FROM pembayaran 
                    WHERE status = 'belum dibayar'";
$belum_bayar = $conn->query($sql_belum_bayar)->fetch_assoc()['total_belum_bayar'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Pak Resto Unikom</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kasir/pembayaran.php">Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kasir/laporan.php">Laporan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard Kasir</h2>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pendapatan Hari Ini</h5>
                        <p class="card-text display-4">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></p>
                        <p class="card-text"><small><?php echo date('d M Y'); ?></small></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Belum Dibayar</h5>
                        <p class="card-text display-4"><?php echo $belum_bayar; ?></p>
                        <p class="card-text"><small>Pesanan yang belum dibayar</small></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Pembayaran Terbaru -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5>Transaksi Terakhir</h5>
            </div>
            <div class="card-body">
                <?php
                $sql_transaksi = "SELECT DISTINCT pb.id_pembayaran, pb.waktu_pembayaran, pb.total, 
                                        pb.metode_pembayaran, pl.nama as nama_pelanggan
                                FROM pembayaran pb
                                JOIN (
                                    SELECT DISTINCT id_pesanan, id_pelanggan 
                                    FROM pesanan
                                ) ps ON pb.id_pesanan = ps.id_pesanan
                                JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
                                WHERE pb.status = 'dibayar'
                                ORDER BY pb.waktu_pembayaran DESC
                                LIMIT 5";
                $result_transaksi = $conn->query($sql_transaksi);
                ?>

                <?php if ($result_transaksi->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Metode</th>
                                    <th>Total</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_transaksi->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id_pembayaran']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_pelanggan']); ?></td>
                                        <td><?php echo $row['metode_pembayaran']; ?></td>
                                        <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['waktu_pembayaran'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Belum ada transaksi hari ini</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>