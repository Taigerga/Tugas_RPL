<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Tambah Menu
if (isset($_POST['tambah'])) {
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    
    $sql = "INSERT INTO menu (nama_menu, harga, stok) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $nama_menu, $harga, $stok);
    $stmt->execute();
    header("Location: kelola_menu.php?success=Menu berhasil ditambahkan");
    exit();
}

// Hapus Menu
if (isset($_GET['hapus'])) {
    $id_menu = $_GET['hapus'];
    $sql = "DELETE FROM menu WHERE id_menu = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    header("Location: kelola_menu.php?success=Menu berhasil dihapus");
    exit();
}

// Update Menu
if (isset($_POST['update'])) {
    $id_menu = $_POST['id_menu'];
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    
    $sql = "UPDATE menu SET nama_menu = ?, harga = ?, stok = ? WHERE id_menu = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdii", $nama_menu, $harga, $stok, $id_menu);
    $stmt->execute();
    header("Location: kelola_menu.php?success=Menu berhasil diupdate");
    exit();
}

$sql = "SELECT * FROM menu";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .confirmation-modal {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .modal-header {
            background: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .btn-confirm {
            background: linear-gradient(to right, #198754, #0d6efd);
            border: none;
            transition: all 0.3s;
        }
        .btn-confirm:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }
        .btn-cancel {
            background: linear-gradient(to right, #dc3545, #fd7e14);
            border: none;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .alert-box {
            background-color: #e7f1ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .menu-card {
            transition: all 0.3s;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .menu-header {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            padding: 15px;
        }
        .menu-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
        .menu-stock {
            font-size: 0.9rem;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .menu-stock.in-stock {
            background-color: #d4edda;
            color: #155724;
        }
        .menu-stock.out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        .action-buttons .btn {
            margin-right: 5px;
            transition: all 0.3s;
        }
        .action-buttons .btn:hover {
            transform: scale(1.1);
        }
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Pak Resto Unikom</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/dashboard_pemilik.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Kelola Menu</a>
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
        <h2 class="mb-4">Kelola Menu</h2>
        
        <div class="alert-box">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle fs-4 text-primary me-3"></i>
                <div>
                    <h5>Manajemen Menu Restoran</h5>
                    <p class="mb-0">Kelola daftar menu, harga, dan stok untuk restoran Anda. Tambah, edit, atau hapus menu sesuai kebutuhan.</p>
                </div>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()):
                    $stock_class = $row['stok'] > 0 ? 'in-stock' : 'out-of-stock';
                    $stock_text = $row['stok'] > 0 ? 'Stok: ' . $row['stok'] : 'Stok Habis';
            ?>
            <div class="col">
                <div class="card menu-card h-100">
                    <div class="menu-header">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['nama_menu']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="menu-price">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                            <span class="menu-stock <?php echo $stock_class; ?>">
                                <?php echo $stock_text; ?>
                            </span>
                        </div>
                        <p class="card-text text-muted">ID Menu: <?php echo $row['id_menu']; ?></p>
                    </div>
                    <div class="card-footer bg-white action-buttons">
                        <button class="btn btn-sm btn-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editMenuModal<?php echo $row['id_menu']; ?>">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteMenuModal<?php echo $row['id_menu']; ?>">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modal Edit -->
            <div class="modal fade" id="editMenuModal<?php echo $row['id_menu']; ?>" tabindex="-1" aria-labelledby="editMenuModalLabel<?php echo $row['id_menu']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="editMenuModalLabel<?php echo $row['id_menu']; ?>">
                                <i class="bi bi-pencil"></i> Edit Menu
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_menu" value="<?php echo $row['id_menu']; ?>">
                                <div class="mb-3">
                                    <label for="nama_menu" class="form-label">Nama Menu</label>
                                    <input type="text" class="form-control" id="nama_menu" name="nama_menu" value="<?php echo htmlspecialchars($row['nama_menu']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga</label>
                                    <input type="number" class="form-control" id="harga" name="harga" value="<?php echo $row['harga']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stok" name="stok" value="<?php echo $row['stok']; ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Modal Hapus -->
            <div class="modal fade" id="deleteMenuModal<?php echo $row['id_menu']; ?>" tabindex="-1" aria-labelledby="deleteMenuModalLabel<?php echo $row['id_menu']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content confirmation-modal">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteMenuModalLabel<?php echo $row['id_menu']; ?>">
                                <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus Menu
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="bi bi-question-circle text-danger fs-1"></i>
                                <h4 class="mt-2">Apakah Anda yakin?</h4>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Menu yang akan dihapus:</strong> 
                                <div class="mt-2 p-3 bg-light rounded">
                                    <?php echo htmlspecialchars($row['nama_menu']); ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Menu yang dihapus tidak dapat dikembalikan. 
                                Pastikan menu ini tidak sedang digunakan dalam pesanan aktif.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Batal
                            </button>
                            <a href="kelola_menu.php?hapus=<?php echo $row['id_menu']; ?>" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Ya, Hapus Menu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
            } else {
                echo '<div class="col-12 text-center py-5">';
                echo '  <i class="bi bi-menu-up display-1 text-muted"></i>';
                echo '  <h3 class="mt-3">Belum Ada Menu</h3>';
                echo '  <p class="text-muted">Tambahkan menu baru untuk memulai</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Floating Button for Add Menu -->
    <a href="#" class="btn btn-primary floating-btn" data-bs-toggle="modal" data-bs-target="#tambahMenuModal">
        <i class="bi bi-plus-lg"></i>
    </a>

    <!-- Modal Tambah Menu -->
    <div class="modal fade" id="tambahMenuModal" tabindex="-1" aria-labelledby="tambahMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahMenuModalLabel">
                        <i class="bi bi-plus-lg"></i> Tambah Menu Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_menu" class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" id="nama_menu" name="nama_menu" required placeholder="Masukkan nama menu">
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga" name="harga" required placeholder="Masukkan harga">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="stok" class="form-label">Stok Awal</label>
                            <input type="number" class="form-control" id="stok" name="stok" required value="10" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Tambah Berhasil (akan muncul setelah submit) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Sukses</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle-fill text-success display-1 mb-3"></i>
                    <h4>Menu Berhasil Ditambahkan!</h4>
                    <p>Menu baru telah ditambahkan ke dalam sistem.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close success alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
        
        // Show success modal if there's a success message
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET['success'])): ?>
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                
                // Remove success parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url);
            <?php endif; ?>
        });
    </script>
</body>
</html>