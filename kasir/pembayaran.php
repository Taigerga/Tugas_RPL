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

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pembayaran = $_POST['id_pembayaran'];
    $metode = $_POST['metode'];
    
    // Hitung total dari pesanan
    $sql_total = "SELECT SUM(m.harga * ps.jumlah) as total
                  FROM pembayaran pb
                  JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
                  JOIN menu m ON ps.id_menu = m.id_menu
                  WHERE pb.id_pembayaran = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $id_pembayaran);
    $stmt_total->execute();
    $total = $stmt_total->get_result()->fetch_assoc()['total'];
    
    // Update pembayaran
    $sql = "UPDATE pembayaran 
            SET status = 'dibayar',
                metode_pembayaran = ?,
                total = ?,
                waktu_pembayaran = NOW(),
                id_kasir = ?
            WHERE id_pembayaran = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdii", $metode, $total, $id_kasir, $id_pembayaran);
    
    if ($stmt->execute()) {
        // Update status meja jika pembayaran berhasil
        $sql_meja = "UPDATE meja m
                    JOIN pelanggan pl ON m.no_meja = pl.no_meja
                    JOIN pesanan ps ON pl.id_pelanggan = ps.id_pelanggan
                    JOIN pembayaran pb ON ps.id_pesanan = pb.id_pesanan
                    SET m.status = 'kosong'
                    WHERE pb.id_pembayaran = ?";
        $stmt_meja = $conn->prepare($sql_meja);
        $stmt_meja->bind_param("i", $id_pembayaran);
        $stmt_meja->execute();
        
        // Redirect ke halaman struk
        header("Location: struk.php?id_pembayaran=" . $id_pembayaran);
    } else {
        header("Location: pembayaran.php?error=Gagal memproses pembayaran");
    }
    exit();
}

// Ambil data pembayaran yang belum dibayar
$sql = "SELECT pb.*, pl.nama as nama_pelanggan, m.nama_menu, ps.jumlah, m.harga
        FROM pembayaran pb
        JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
        JOIN menu m ON ps.id_menu = m.id_menu
        JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
        WHERE pb.status = 'belum dibayar'
        ORDER BY pb.id_pembayaran ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Pak Resto Unikom</title>
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
                        <a class="nav-link active" href="#">Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Laporan</a>
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
        <h2>Proses Pembayaran</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Menu</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pembayaran_data = [];

                    while ($row = $result->fetch_assoc()) {
                        $id = $row['id_pembayaran'];
                        $subtotal = $row['harga'] * $row['jumlah'];

                        if (!isset($pembayaran_data[$id])) {
                            $pembayaran_data[$id] = [
                                'pelanggan' => $row['nama_pelanggan'],
                                'items' => [],
                                'total' => 0,
                                'id_pembayaran' => $id
                            ];
                        }

                        $pembayaran_data[$id]['items'][] = [
                            'nama_menu' => $row['nama_menu'],
                            'jumlah' => $row['jumlah'],
                            'harga' => $row['harga'],
                            'subtotal' => $subtotal
                        ];
                        $pembayaran_data[$id]['total'] += $subtotal;
                    }

                    foreach ($pembayaran_data as $id => $data):
                    ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo htmlspecialchars($data['pelanggan']); ?></td>
                            <td colspan="4">
                                <ul class="mb-1">
                                    <?php foreach ($data['items'] as $item): ?>
                                        <li>
                                            <?php echo htmlspecialchars($item['nama_menu']); ?> 
                                            (<?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?>) 
                                            = <strong>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <strong>Total: Rp <?php echo number_format($data['total'], 0, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bayarModal<?php echo $id; ?>">
                                    Proses
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="bayarModal<?php echo $id; ?>" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Proses Pembayaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pembayaran" value="<?php echo $id; ?>">
                                                    <p>Pelanggan: <?php echo htmlspecialchars($data['pelanggan']); ?></p>
                                                    <p>Total: <strong>Rp <?php echo number_format($data['total'], 0, ',', '.'); ?></strong></p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Metode Pembayaran</label>
                                                        <select class="form-select" name="metode" required>
                                                            <option value="Tunai">Tunai</option>
                                                            <option value="Debit">Kartu Debit</option>
                                                            <option value="Kredit">Kartu Kredit</option>
                                                            <option value="QRIS">QRIS</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>