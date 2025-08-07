<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelayan') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

if (isset($_GET['ubah_status'])) {
    $no_meja = $_GET['no_meja'];
    $status = $_GET['status'];
    
    $sql = "UPDATE meja SET status = ? WHERE no_meja = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $no_meja);
    $stmt->execute();
    header("Location: kelola_meja.php?success=Status meja berhasil diubah");
    exit();
}

$sql = "SELECT * FROM meja ORDER BY no_meja";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Meja - Pak Resto Unikom</title>
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
                        <a class="nav-link active" href="#">Kelola Meja</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../pelayan/status_produksi.php">Status Produksi</a>
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
        <h2>Kelola Status Meja</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Meja <?php echo $row['no_meja']; ?></h5>
                            <p class="card-text">
                                Status: 
                                <span class="badge bg-<?php 
                                    echo $row['status'] == 'kosong' ? 'success' : 
                                         ($row['status'] == 'terisi' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </p>
                            
                            <div class="btn-group" role="group">
                                <a href="kelola_meja.php?ubah_status=1&no_meja=<?php echo $row['no_meja']; ?>&status=kosong" 
                                   class="btn btn-sm btn-<?php echo $row['status'] == 'kosong' ? 'primary' : 'outline-primary'; ?>">Kosong</a>
                                <a href="kelola_meja.php?ubah_status=1&no_meja=<?php echo $row['no_meja']; ?>&status=terisi" 
                                   class="btn btn-sm btn-<?php echo $row['status'] == 'terisi' ? 'primary' : 'outline-primary'; ?>">Terisi</a>
                                <a href="kelola_meja.php?ubah_status=1&no_meja=<?php echo $row['no_meja']; ?>&status=reservasi" 
                                   class="btn btn-sm btn-<?php echo $row['status'] == 'reservasi' ? 'primary' : 'outline-primary'; ?>">Reservasi</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>