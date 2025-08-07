<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'koki') {
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
    <title>Dashboard Koki - Pak Resto Unikom</title>
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
                        <a class="nav-link" href="../koki/lihat_pesanan.php">Lihat Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../koki/masak.php">Masak</a>
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
        <h2>Dashboard Koki</h2>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pesanan Masak</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM produksi WHERE status = 'dimasak'";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo '<p class="card-text display-4">' . $row['total'] . '</p>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pesanan Selesai</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM produksi WHERE status = 'selesai'";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo '<p class="card-text display-4">' . $row['total'] . '</p>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>