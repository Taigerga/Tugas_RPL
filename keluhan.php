<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pesanan = $_POST['id_pesanan'];
    $isi_keluhan = $_POST['isi_keluhan'];
    
    // Insert complaint into database
    $sql = "INSERT INTO keluhan (id_pesanan, isi_keluhan, tanggal_keluhan, status) 
            VALUES (?, ?, NOW(), 'baru')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_pesanan, $isi_keluhan);
    
    if ($stmt->execute()) {
        $success = "Keluhan Anda telah berhasil dikirim. Terima kasih atas masukan Anda!";
    } else {
        $error = "Gagal mengirim keluhan. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pak Resto Unikom - Keluhan Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-utensils me-2"></i>Pak Resto Unikom
            </a>
            <div class="ms-auto d-flex">
                
                <a href="menu.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-book-open me-2"></i>Menu
                </a>

                <a href="keluhan.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-exclamation-circle me-2"></i>Keluhan
                </a>

                <a href="login.php" class="btn btn-light">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Staff
                </a>

            </div>
        </div>
    </nav>  

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Form Keluhan Pelanggan</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <a href="index.php" class="btn btn-primary">Kembali ke Menu</a>
                        <?php else: ?>
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="id_pesanan" class="form-label">ID Pesanan</label>
                                    <input type="number" class="form-control" id="id_pesanan" name="id_pesanan" required>
                                    <small class="text-muted">Masukkan ID pesanan yang Anda dapatkan saat memesan</small>
                                </div>
                                <div class="mb-3">
                                    <label for="isi_keluhan" class="form-label">Keluhan Anda</label>
                                    <textarea class="form-control" id="isi_keluhan" name="isi_keluhan" rows="5" required></textarea>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Keluhan
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">Kembali ke Menu</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 Pak Resto Unikom. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>