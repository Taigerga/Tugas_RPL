<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Ambil id_koki
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

// Jika klik tombol selesai
if (isset($_GET['selesai'])) {
    $id_produksi = $_GET['selesai'];
    
    $sql = "UPDATE produksi 
            SET status = 'selesai', waktu_selesai = NOW()
            WHERE id_produksi = ? AND id_koki = ? AND status = 'dimasak'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_produksi, $id_koki);
    
    if ($stmt->execute()) {
        header("Location: masak.php?success=Pesanan telah diselesaikan");
    } else {
        header("Location: masak.php?error=Gagal menyelesaikan pesanan");
    }
    exit();
}

// Ambil semua produksi yang sedang dimasak
$sql = "SELECT id_produksi, id_pesanan, waktu_mulai
        FROM produksi
        WHERE id_koki = ? AND status = 'dimasak'
        ORDER BY id_pesanan, id_produksi";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_koki);
$stmt->execute();
$result = $stmt->get_result();

// Buat map menu dari pesanan
$menu_map = [];
$q = $conn->query("SELECT ps.id_pesanan, m.nama_menu, ps.id_menu, ps.jumlah, ps.totalharga, pl.nama AS nama_pelanggan
                   FROM pesanan ps
                   JOIN menu m ON ps.id_menu = m.id_menu
                   JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
                   ORDER BY ps.id_pesanan, ps.id_menu");

while ($row = $q->fetch_assoc()) {
    $menu_map[$row['id_pesanan']][] = $row;
}

// Siapkan urutan untuk tiap pesanan
$produksi_index = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masak - Pak Resto Unikom</title>
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
            background: #198754;
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
        .cooking-icon {
            color: #fd7e14;
            font-size: 1.2rem;
            margin-right: 5px;
        }
        .check-icon {
            color: #198754;
            font-size: 1.2rem;
            margin-right: 5px;
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
                    <a class="nav-link" href="lihat_pesanan.php">Lihat Pesanan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">Masak</a>
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
    <h2><i class="bi bi-egg-fried cooking-icon"></i> Daftar Pesanan yang Sedang Dimasak</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    
    <div class="alert-box">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle fs-4 text-primary me-3"></i>
            <div>
                <h5>Petunjuk Kerja Koki</h5>
                <p class="mb-0">Klik tombol "Selesai" untuk menandai pesanan yang sudah selesai dimasak. Pastikan kualitas masakan sesuai standar sebelum menandai selesai.</p>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
            <tr>
                <th>ID Produksi</th>
                <th>ID Pesanan</th>
                <th>Menu</th>
                <th>Waktu Mulai</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php
            while ($row = $result->fetch_assoc()):
                $id_produksi = $row['id_produksi'];
                $id_pesanan = $row['id_pesanan'];

                if (!isset($produksi_index[$id_pesanan])) {
                    $produksi_index[$id_pesanan] = 0;
                }

                $menu_info = $menu_map[$id_pesanan][$produksi_index[$id_pesanan]] ?? null;

                if ($menu_info):
                    ?>
                    <tr>
                        <td><?php echo $id_produksi; ?></td>
                        <td><?php echo $id_pesanan; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($menu_info['nama_menu']); ?></strong>
                            <div class="text-muted small">Pelanggan: <?php echo htmlspecialchars($menu_info['nama_pelanggan']); ?></div>
                        </td>
                        <td><?php echo $row['waktu_mulai']; ?></td>
                        <td>
                            <button type="button" 
                                    class="btn btn-success btn-sm btn-action"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmationModal"
                                    data-id-produksi="<?php echo $id_produksi; ?>"
                                    data-id-pesanan="<?php echo $id_pesanan; ?>"
                                    data-menu="<?php echo htmlspecialchars($menu_info['nama_menu']); ?>">
                                <i class="bi bi-check-circle"></i> Selesai
                            </button>
                        </td>
                    </tr>
                <?php
                endif;

                $produksi_index[$id_pesanan]++;
            endwhile;
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
                    <i class="bi bi-check-circle"></i> Konfirmasi Penyelesaian Masakan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-question-circle text-warning fs-1"></i>
                    <h4 class="mt-2">Apakah menu sudah selesai?</h4>
                </div>
                
                <div class="mb-3">
                    <strong>ID Pesanan:</strong> 
                    <span id="confirm-pesanan"></span>
                </div>
                
                <div class="mb-3">
                    <strong>Menu:</strong> 
                    <span id="confirm-menu"></span>
                </div>
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Pastikan menu sudah matang sempurna dan sesuai standar kualitas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
                <a href="#" id="confirm-link" class="btn btn-success btn-confirm">
                    <i class="bi bi-check-circle"></i> Ya, Selesai
                </a>
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
            const idPesanan = button.getAttribute('data-id-pesanan');
            const menu = button.getAttribute('data-menu');
            
            // Update the modal's content
            document.getElementById('confirm-pesanan').textContent = '#' + idPesanan;
            document.getElementById('confirm-menu').textContent = menu;
            
            // Update the confirm link
            document.getElementById('confirm-link').href = 'masak.php?selesai=' + idProduksi;
        });
        
        // Hapus parameter success/error dari URL setelah ditampilkan
        if (window.location.search.includes('success=') || window.location.search.includes('error=')) {
            setTimeout(() => {
                const url = new URL(window.location);
                url.search = '';
                window.history.replaceState({}, document.title, url);
            }, 3000);
        }
    });
</script>
</body>
</html>