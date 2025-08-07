<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Filter tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query untuk laporan
$sql = "SELECT pb.id_pembayaran, pb.waktu_pembayaran, pb.total, 
               pb.metode_pembayaran, pl.nama as nama_pelanggan,
               m.nama_menu, ps.jumlah
        FROM pembayaran pb
        JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
        JOIN menu m ON ps.id_menu = m.id_menu
        JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
        WHERE pb.status = 'dibayar'
        AND DATE(pb.waktu_pembayaran) BETWEEN ? AND ?
        ORDER BY pb.waktu_pembayaran DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Hitung total pendapatan
$sql_total = "SELECT COALESCE(SUM(total), 0) as total_pendapatan
              FROM pembayaran
              WHERE status = 'dibayar'
              AND DATE(waktu_pembayaran) BETWEEN ? AND ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("ss", $start_date, $end_date);
$stmt_total->execute();
$total = $stmt_total->get_result()->fetch_assoc()['total_pendapatan'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Pak Resto Unikom</title>
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
                        <a class="nav-link" href="../dashboard/dashboard_kasir.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pembayaran.php">Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Laporan</a>
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
        <h2>Laporan Transaksi</h2>
        
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Total Pendapatan: Rp <?php echo number_format($total, 0, ',', '.'); ?></h5>
                <p class="card-text">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Menu</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $laporan = [];

                    while ($row = $result->fetch_assoc()) {
                        $id = $row['id_pembayaran'];

                        if (!isset($laporan[$id])) {
                            $laporan[$id] = [
                                'waktu' => $row['waktu_pembayaran'],
                                'pelanggan' => $row['nama_pelanggan'],
                                'metode' => $row['metode_pembayaran'],
                                'total' => $row['total'],
                                'items' => []
                            ];
                        }

                        $laporan[$id]['items'][] = [
                            'menu' => $row['nama_menu'],
                            'jumlah' => $row['jumlah']
                        ];
                    }

                    foreach ($laporan as $id => $data): ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo date('d M H:i', strtotime($data['waktu'])); ?></td>
                            <td><?php echo htmlspecialchars($data['pelanggan']); ?></td>
                            <td>
                                <ul class="mb-1">
                                    <?php foreach ($data['items'] as $item): ?>
                                        <li><?php echo htmlspecialchars($item['menu']) . " (x" . $item['jumlah'] . ")"; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><?php 
                                $jumlah_total = 0;
                                foreach ($data['items'] as $item) $jumlah_total += $item['jumlah'];
                                echo $jumlah_total;
                            ?></td>
                            <td>Rp <?php echo number_format($data['total'], 0, ',', '.'); ?></td>
                            <td><?php echo $data['metode']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>