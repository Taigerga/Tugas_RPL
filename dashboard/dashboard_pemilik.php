<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Pak Resto Unikom</title>
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
                        <a class="nav-link" href="../pemilik/kelola_menu.php">Kelola Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pemilik/lihat_keluhan.php">Keluhan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pemilik/laporan_penjualan.php">Laporan</a>
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
        <h2>Dashboard Pemilik</h2>
        
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Menu</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM menu";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo '<p class="card-text display-4">' . $row['total'] . '</p>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Keluhan Baru</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM keluhan WHERE status = 'baru'";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo '<p class="card-text display-4">' . $row['total'] . '</p>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pendapatan Hari Ini</h5>
                        <?php
                        $today = date('Y-m-d');
                        $sql = "SELECT SUM(total) as total FROM pembayaran WHERE DATE(waktu_pembayaran) = ? AND status = 'dibayar'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $today);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        echo '<p class="card-text display-4">Rp ' . number_format($row['total'] ?? 0, 0, ',', '.') . '</p>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>