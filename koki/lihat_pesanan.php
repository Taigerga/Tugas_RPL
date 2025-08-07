<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Ambil ID koki berdasarkan akun login
$id_akun = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id_koki FROM koki WHERE id_akun = ?");
$stmt->bind_param("i", $id_akun);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!$data) {
    die("Data koki tidak ditemukan untuk akun ini.");
}
$id_koki = $data['id_koki'];

// Ambil hanya kombinasi unik: satu produksi satu menu
$sql = "SELECT pr.id_produksi, pr.id_pesanan, pr.status, pr.waktu_mulai
        FROM produksi pr
        WHERE pr.status = 'pending'
        ORDER BY pr.id_pesanan, pr.id_produksi";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Pesanan - Pak Resto Unikom</title>
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
        .badge-pending {
            background-color: #6c757d;
        }
        .badge-in-progress {
            background-color: #fd7e14;
        }
        .badge-completed {
            background-color: #198754;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }
        .btn-action {
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Pak Resto Unikom</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/dashboard_koki.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Lihat Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="masak.php">Masak</a>
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
        <h2>Daftar Pesanan yang Perlu Dimasak</h2>
        
        <div class="alert-box">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle fs-4 text-primary me-3"></i>
                <div>
                    <h5>Petunjuk Kerja Koki</h5>
                    <p class="mb-0">Klik tombol "Ambil" untuk mengambil pesanan yang akan dimasak. Pastikan Anda hanya mengambil pesanan yang bisa diselesaikan.</p>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Menu</th>
                        <th>Waktu Order</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <?php
                $produksi_per_pesanan = [];

                // Ambil semua menu dari setiap id_pesanan
                $menu_map = [];
                $q = $conn->query("SELECT ps.id_pesanan, m.nama_menu, ps.id_menu, ps.jumlah, ps.totalharga, pl.nama as nama_pelanggan
                                FROM pesanan ps
                                JOIN menu m ON ps.id_menu = m.id_menu
                                JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
                                ORDER BY ps.id_pesanan, ps.id_menu");

                while ($row = $q->fetch_assoc()) {
                    $menu_map[$row['id_pesanan']][] = $row;
                }
                ?>
                <tbody>
                    <?php
                        while ($row = $result->fetch_assoc()) {
                            $id_pesanan = $row['id_pesanan'];
                            $id_produksi = $row['id_produksi'];

                            // Ambil urutan produksi ke berapa untuk pesanan ini
                            if (!isset($produksi_per_pesanan[$id_pesanan])) {
                                $produksi_per_pesanan[$id_pesanan] = 0;
                            }

                            $index = $produksi_per_pesanan[$id_pesanan];

                            // Ambil data menu ke-N sesuai urutan produksi
                            $menu_info = $menu_map[$id_pesanan][$index] ?? null;

                            if ($menu_info) {
                                ?>
                                <tr>
                                    <td><?php echo $id_pesanan; ?></td>
                                    <td><?php echo htmlspecialchars($menu_info['nama_pelanggan']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($menu_info['nama_menu']); ?></strong>
                                        <div class="text-muted small">Jumlah: <?php echo $menu_info['jumlah']; ?></div>
                                    </td>
                                    <td><?php echo $row['waktu_mulai']; ?></td>
                                    <td>
                                        <span class="badge badge-pending"><?php echo ucfirst($row['status']); ?></span>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm btn-action" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmationModal"
                                                data-id-produksi="<?php echo $id_produksi; ?>"
                                                data-id-koki="<?php echo $id_koki; ?>"
                                                data-menu="<?php echo htmlspecialchars($menu_info['nama_menu']); ?>"
                                                data-pelanggan="<?php echo htmlspecialchars($menu_info['nama_pelanggan']); ?>">
                                            <i class="bi bi-check-circle"></i> Ambil
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }

                            // Naikkan index produksi untuk pesanan ini
                            $produksi_per_pesanan[$id_pesanan]++;
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content confirmation-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="bi bi-question-circle"></i> Konfirmasi Pengambilan Pesanan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
                        <h4 class="mt-2">Apakah Anda yakin?</h4>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Menu:</strong> 
                        <span id="confirm-menu"></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Untuk Pelanggan:</strong> 
                        <span id="confirm-pelanggan"></span>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Anda akan bertanggung jawab untuk menyiapkan menu ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <form id="confirm-form" method="POST" action="ambil_pesanan.php">
                        <input type="hidden" name="id_produksi" id="id_produksi">
                        <input type="hidden" name="id_koki" id="id_koki">
                        <button type="submit" class="btn btn-primary btn-confirm">
                            <i class="bi bi-check-circle"></i> Ya, Ambil Pesanan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmationModal = document.getElementById('confirmationModal');
            
            confirmationModal.addEventListener('show.bs.modal', function (event) {
                // Button that triggered the modal
                const button = event.relatedTarget;
                
                // Extract info from data-bs-* attributes
                const idProduksi = button.getAttribute('data-id-produksi');
                const idKoki = button.getAttribute('data-id-koki');
                const menu = button.getAttribute('data-menu');
                const pelanggan = button.getAttribute('data-pelanggan');
                
                // Update the modal's content
                document.getElementById('confirm-menu').textContent = menu;
                document.getElementById('confirm-pelanggan').textContent = pelanggan;
                document.getElementById('id_produksi').value = idProduksi;
                document.getElementById('id_koki').value = idKoki;
            });
        });
    </script>
</body>
</html>