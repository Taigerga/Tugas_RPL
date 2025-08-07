<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelayan') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';
$id_akun = $_SESSION['user_id'];
$stmt_pelayan = $conn->prepare("SELECT id_pelayan FROM pelayan WHERE id_akun = ?");
$stmt_pelayan->bind_param("i", $id_akun);
$stmt_pelayan->execute();
$result_pelayan = $stmt_pelayan->get_result();
$data_pelayan = $result_pelayan->fetch_assoc();

if (!$data_pelayan) {
    die("Akun ini belum terdaftar sebagai pelayan.");
}
$id_pelayan = $data_pelayan['id_pelayan'];
// Ambil data produksi yang terkait dengan pesanan yang dibuat oleh pelayan ini
$sql = "SELECT 
    pr.*, 
    m.nama_menu, 
    pl.nama as nama_pelanggan, 
    ps.jumlah, 
    py.nama as nama_pelayan, 
    k.nama as nama_koki
FROM (
    SELECT 
        id_pesanan, 
        MIN(id_produksi) as first_produksi_id
    FROM produksi
    GROUP BY id_pesanan
) as first_productions
JOIN produksi pr ON pr.id_produksi = first_productions.first_produksi_id
JOIN pesanan ps ON pr.id_pesanan = ps.id_pesanan
JOIN menu m ON ps.id_menu = m.id_menu
JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
JOIN pelayan py ON ps.id_pelayan = py.id_pelayan
LEFT JOIN koki k ON pr.id_koki = k.id_koki
WHERE ps.id_pelayan = ?
ORDER BY pr.status, pr.waktu_mulai DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pelayan);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Produksi - Pak Resto Unikom</title>
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
                        <a class="nav-link" href="../dashboard/dashboard_pelayan.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="input_pesanan.php">Input Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_meja.php">Kelola Meja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Status Produksi</a>
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
        <h2>Status Produksi Pesanan</h2>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Menu</th>
                        <th>Jumlah</th>
                        <th>Pelayan</th>
                        <th>Koki</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_pesanan']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pelanggan']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_menu']); ?></td>
                            <td><?php echo $row['jumlah']; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pelayan']); ?></td>
                            <td><?php echo $row['nama_koki'] ? htmlspecialchars($row['nama_koki']) : '-'; ?></td>
                            <td><?php echo $row['waktu_mulai']; ?></td>
                            <td><?php echo $row['waktu_selesai'] ? $row['waktu_selesai'] : '-'; ?></td>
                            <td>
                                <?php
                                $status = $row['status'];
                                $badgeClass = match($status) {
                                    'pending' => 'secondary',
                                    'dimasak' => 'warning',
                                    'selesai' => 'success',
                                    default => 'dark'
                                };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>