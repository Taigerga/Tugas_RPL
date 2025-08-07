<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Dapatkan data pelayan
$id_akun = $_SESSION['user_id'];
$stmt_pelayan = $conn->prepare("SELECT id_pelayan FROM pelayan WHERE id_akun = ?");
$stmt_pelayan->bind_param("i", $id_akun);
$stmt_pelayan->execute();
$result_pelayan = $stmt_pelayan->get_result();
$data_pelayan = $result_pelayan->fetch_assoc();
$id_pelayan = $data_pelayan ? $data_pelayan['id_pelayan'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $gmail = $_POST['gmail'];
    $no_meja = $_POST['no_meja'];
    $menus = $_POST['menu'];

    try {
        $conn->begin_transaction();

        // 1. Insert pelanggan
        $sql_pelanggan = "INSERT INTO pelanggan (nama, gmail, no_meja) VALUES (?, ?, ?)";
        $stmt_pelanggan = $conn->prepare($sql_pelanggan);
        $stmt_pelanggan->bind_param("ssi", $nama_pelanggan, $gmail, $no_meja);
        $stmt_pelanggan->execute();
        $id_pelanggan = $conn->insert_id;

        // 2. Update status meja
        $sql_meja = "UPDATE meja SET status = 'terisi' WHERE no_meja = ?";
        $stmt_meja = $conn->prepare($sql_meja);
        $stmt_meja->bind_param("i", $no_meja);
        $stmt_meja->execute();

        // 3. Buat ID pesanan manual (shared untuk semua menu)
        $id_pesanan = time(); // atau uniqid() jika ingin string

        $total_transaksi = 0;

        foreach ($menus as $menu) {
            $id_menu = $menu['id_menu'];
            $jumlah = $menu['jumlah'];

            // Validasi stok dan ambil harga
            $sql_menu = "SELECT harga, stok FROM menu WHERE id_menu = ? FOR UPDATE";
            $stmt_menu = $conn->prepare($sql_menu);
            $stmt_menu->bind_param("i", $id_menu);
            $stmt_menu->execute();
            $result_menu = $stmt_menu->get_result();
            $data_menu = $result_menu->fetch_assoc();

            if (!$data_menu) {
                throw new Exception("Menu ID $id_menu tidak ditemukan");
            }

            if ($data_menu['stok'] < $jumlah) {
                throw new Exception("Stok tidak mencukupi untuk ID Menu $id_menu");
            }

            $harga = $data_menu['harga'];
            $subtotal = $harga * $jumlah;
            $total_transaksi += $subtotal;

            // 4. Insert ke pesanan (dengan id_pesanan sama)
            $sql_pesanan = "INSERT INTO pesanan (id_pesanan, id_pelanggan, id_menu, no_meja, jumlah, totalharga, id_pelayan) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_pesanan = $conn->prepare($sql_pesanan);
            if (!$stmt_pesanan) {
                throw new Exception("Prepare pesanan gagal: " . $conn->error);
            }
            if (!$stmt_pesanan->bind_param("iiiiidi", $id_pesanan, $id_pelanggan, $id_menu, $no_meja, $jumlah, $subtotal, $id_pelayan)) {
                throw new Exception("Bind param pesanan gagal: " . $stmt_pesanan->error);
            }
            if (!$stmt_pesanan->execute()) {
                throw new Exception("Execute pesanan gagal: " . $stmt_pesanan->error);
            }
            $stmt_pesanan->bind_param("iiiiidi", $id_pesanan, $id_pelanggan, $id_menu, $no_meja, $jumlah, $subtotal, $id_pelayan);
            $stmt_pesanan->execute();

            // 5. Kurangi stok
            $sql_update_stok = "UPDATE menu SET stok = stok - ? WHERE id_menu = ?";
            $stmt_update = $conn->prepare($sql_update_stok);
            $stmt_update->bind_param("ii", $jumlah, $id_menu);
            $stmt_update->execute();

            // 6. Insert ke produksi
            $sql_produksi = "INSERT INTO produksi (id_pesanan, waktu_mulai, status) VALUES (?, NOW(), 'pending')";
            $stmt_produksi = $conn->prepare($sql_produksi);
            $stmt_produksi->bind_param("i", $id_pesanan);
            $stmt_produksi->execute();
        }

        // 7. Insert ke pembayaran (1x untuk seluruh pesanan)
        $sql_pembayaran = "INSERT INTO pembayaran (id_pesanan, total, status) 
                          VALUES (?, ?, 'belum dibayar')";
        $stmt_pembayaran = $conn->prepare($sql_pembayaran);
        $stmt_pembayaran->bind_param("id", $id_pesanan, $total_transaksi);
        $stmt_pembayaran->execute();

        $conn->commit();
        $_SESSION['success'] = "Pesanan berhasil ditambahkan";
        header("Location: input_pesanan.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: input_pesanan.php");
        exit();
    }
}


// Ambil data menu dan meja
$sql_menu = "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu";
$menus = $conn->query($sql_menu)->fetch_all(MYSQLI_ASSOC);

$sql_meja = "SELECT * FROM meja WHERE status = 'kosong' ORDER BY no_meja";
$mejas = $conn->query($sql_meja)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .menu-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .total-container {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .confirmation-modal {
            background: linear-gradient(135deg, #f5f7fa, #e4edf9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .modal-header {
            background: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .summary-item {
            padding: 10px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        .summary-item:last-child {
            border-bottom: none;
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
        .highlight-box {
            background-color: #e7f1ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="../dashboard/dashboard_pelayan.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Input Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_meja.php">Kelola Meja</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../pelayan/status_produksi.php">Status Produksi</a>
                    </li>                    
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="../logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Input Pesanan Baru</h2>
        
        <div class="highlight-box">
            <h5><i class="bi bi-info-circle"></i> Petunjuk Pengisian</h5>
            <p class="mb-0">Isi data pelanggan dengan lengkap dan tambahkan menu pesanan. Pastikan semua data sudah sesuai sebelum menyimpan.</p>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form id="form-pesanan" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            Data Pelanggan
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nama_pelanggan" class="form-label">Nama Pelanggan</label>
                                <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gmail" class="form-label">Email Pelanggan</label>
                                <input type="email" class="form-control" id="gmail" name="gmail" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="no_meja" class="form-label">Nomor Meja</label>
                                <select class="form-select" id="no_meja" name="no_meja" required>
                                    <option value="">Pilih Meja</option>
                                    <?php foreach ($mejas as $meja): ?>
                                        <option value="<?= $meja['no_meja'] ?>">
                                            Meja <?= $meja['no_meja'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span>Pesanan Menu</span>
                            <button type="button" id="tambah-menu" class="btn btn-sm btn-light">
                                <i class="bi bi-plus"></i> Tambah Menu
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="daftar-menu">
                                <!-- Item menu akan ditambahkan di sini oleh JavaScript -->
                            </div>
                            
                            <div class="total-container">
                                <h5>Total Pesanan</h5>
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">Rp 0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>PPN (10%):</span>
                                    <span id="ppn">Rp 0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span id="total">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-3">
                        <button type="button" id="btn-submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Simpan Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Konfirmasi Pesanan -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content confirmation-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel"><i class="bi bi-question-circle"></i> Konfirmasi Pesanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="mb-4 text-center">Apakah pesanan sudah sesuai?</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Detail Pelanggan</h5>
                            <div class="summary-item">
                                <strong>Nama:</strong> <span id="confirm-nama"></span>
                            </div>
                            <div class="summary-item">
                                <strong>Email:</strong> <span id="confirm-email"></span>
                            </div>
                            <div class="summary-item">
                                <strong>Meja:</strong> <span id="confirm-meja"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Ringkasan Pesanan</h5>
                            <div id="confirm-pesanan">
                                <!-- Daftar menu akan diisi oleh JavaScript -->
                            </div>
                            <div class="summary-item mt-3">
                                <strong>Total Pesanan:</strong> <span id="confirm-total"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-4">
                        <i class="bi bi-exclamation-triangle"></i> Pastikan semua data sudah benar. Pesanan tidak dapat diubah setelah disimpan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">Kembali Periksa</button>
                    <button type="button" id="confirm-submit" class="btn btn-primary btn-confirm">Ya, Simpan Pesanan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template untuk item menu (hidden) -->
    <template id="template-menu">
        <div class="menu-item" data-id="">
            <div class="row g-2">
                <div class="col-md-6">
                    <select class="form-select menu-select" name="menu[][id_menu]" required>
                        <option value="">Pilih Menu</option>
                        <?php foreach ($menus as $menu): ?>
                            <option value="<?= $menu['id_menu'] ?>" 
                                    data-harga="<?= $menu['harga'] ?>"
                                    data-stok="<?= $menu['stok'] ?>"
                                    data-nama="<?= htmlspecialchars($menu['nama_menu']) ?>">
                                <?= htmlspecialchars($menu['nama_menu']) ?> - 
                                Rp <?= number_format($menu['harga'], 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control jumlah" name="menu[][jumlah]" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100 hapus-menu">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex justify-content-between">
                <small class="text-muted stok-info">Stok tersedia: -</small>
                <small class="text-muted subtotal">Subtotal: Rp 0</small>
            </div>
        </div>
    </template>

    <!-- Template untuk ringkasan menu di modal -->
    <template id="template-confirm-menu">
        <div class="summary-item">
            <span class="confirm-menu-nama"></span> 
            <span class="confirm-menu-jumlah"></span> 
            <span class="confirm-menu-subtotal"></span>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const daftarMenu = document.getElementById('daftar-menu');
            const template = document.getElementById('template-menu');
            const form = document.getElementById('form-pesanan');
            const btnSubmit = document.getElementById('btn-submit');
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const confirmSubmit = document.getElementById('confirm-submit');
            
            // Tambah menu pertama saat halaman dimuat
            tambahMenuItem();
            
            // Event untuk tombol tambah menu
            document.getElementById('tambah-menu').addEventListener('click', tambahMenuItem);
            
            // Event untuk hapus menu
            daftarMenu.addEventListener('click', function(e) {
                if (e.target.classList.contains('hapus-menu')) {
                    e.target.closest('.menu-item').remove();
                    hitungTotal();
                    
                    // Jika tidak ada menu, tambahkan satu
                    if (daftarMenu.children.length === 0) {
                        tambahMenuItem();
                    }
                }
            });
            
            // Event untuk perubahan menu dan jumlah
            daftarMenu.addEventListener('change', function(e) {
                if (e.target.classList.contains('menu-select') || e.target.classList.contains('jumlah')) {
                    const menuItem = e.target.closest('.menu-item');
                    updateMenuItem(menuItem);
                    hitungTotal();
                }
            });
            
            // Event untuk tombol simpan pesanan
            btnSubmit.addEventListener('click', function() {
                if (validateForm()) {
                    // Isi data konfirmasi
                    document.getElementById('confirm-nama').textContent = document.getElementById('nama_pelanggan').value;
                    document.getElementById('confirm-email').textContent = document.getElementById('gmail').value;
                    document.getElementById('confirm-meja').textContent = "Meja " + document.getElementById('no_meja').value;
                    document.getElementById('confirm-total').textContent = document.getElementById('total').textContent;
                    
                    // Isi daftar menu di konfirmasi
                    const confirmPesanan = document.getElementById('confirm-pesanan');
                    confirmPesanan.innerHTML = '';
                    
                    const menuItems = daftarMenu.querySelectorAll('.menu-item');
                    const templateConfirm = document.getElementById('template-confirm-menu').content;
                    
                    menuItems.forEach(item => {
                        const select = item.querySelector('.menu-select');
                        const jumlahInput = item.querySelector('.jumlah');
                        
                        if (select.value) {
                            const menuNama = select.options[select.selectedIndex].dataset.nama;
                            const jumlah = jumlahInput.value;
                            const harga = parseFloat(select.options[select.selectedIndex].dataset.harga);
                            const subtotal = harga * jumlah;
                            
                            const clone = templateConfirm.cloneNode(true);
                            clone.querySelector('.confirm-menu-nama').textContent = menuNama;
                            clone.querySelector('.confirm-menu-jumlah').textContent = ` x ${jumlah}`;
                            clone.querySelector('.confirm-menu-subtotal').textContent = ` = Rp ${subtotal.toLocaleString('id-ID')}`;
                            
                            confirmPesanan.appendChild(clone);
                        }
                    });
                    
                    // Tampilkan modal konfirmasi
                    confirmationModal.show();
                }
            });
            
            // Event untuk tombol konfirmasi di modal
            confirmSubmit.addEventListener('click', function() {
                form.submit();
            });
            
            function tambahMenuItem() {
                const index = daftarMenu.children.length;
                const clone = template.content.cloneNode(true);
                const menuItem = clone.querySelector('.menu-item');
                menuItem.dataset.id = Date.now();

                // GANTI name-nya dengan index unik
                const select = clone.querySelector('.menu-select');
                const jumlahInput = clone.querySelector('.jumlah');

                select.setAttribute('name', `menu[${index}][id_menu]`);
                jumlahInput.setAttribute('name', `menu[${index}][jumlah]`);

                daftarMenu.appendChild(clone);

                // Inisialisasi info stok dan subtotal
                select.dispatchEvent(new Event('change'));
            }
            
            function updateMenuItem(menuItem) {
                const select = menuItem.querySelector('.menu-select');
                const jumlahInput = menuItem.querySelector('.jumlah');
                const stokInfo = menuItem.querySelector('.stok-info');
                const subtotalEl = menuItem.querySelector('.subtotal');
                
                if (select.value) {
                    const harga = parseFloat(select.options[select.selectedIndex].dataset.harga);
                    const stok = parseInt(select.options[select.selectedIndex].dataset.stok);
                    const jumlah = parseInt(jumlahInput.value) || 0;
                    
                    // Update info stok
                    stokInfo.textContent = `Stok tersedia: ${stok}`;
                    
                    // Validasi jumlah tidak melebihi stok
                    if (jumlah > stok) {
                        jumlahInput.value = stok;
                        jumlahInput.dispatchEvent(new Event('change'));
                        return;
                    }
                    
                    // Hitung subtotal
                    const subtotal = harga * jumlah;
                    subtotalEl.textContent = `Subtotal: Rp ${subtotal.toLocaleString('id-ID')}`;
                } else {
                    stokInfo.textContent = 'Stok tersedia: -';
                    subtotalEl.textContent = 'Subtotal: Rp 0';
                }
            }
            
            function hitungTotal() {
                const menuItems = daftarMenu.querySelectorAll('.menu-item');
                let subtotal = 0;
                
                menuItems.forEach(item => {
                    const select = item.querySelector('.menu-select');
                    const jumlahInput = item.querySelector('.jumlah');
                    
                    if (select.value && jumlahInput.value) {
                        const harga = parseFloat(select.options[select.selectedIndex].dataset.harga);
                        const jumlah = parseInt(jumlahInput.value);
                        subtotal += harga * jumlah;
                    }
                });
                
                const ppn = subtotal * 0.1; // PPN 10%
                const total = subtotal + ppn;
                
                document.getElementById('subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
                document.getElementById('ppn').textContent = `Rp ${ppn.toLocaleString('id-ID')}`;
                document.getElementById('total').textContent = `Rp ${total.toLocaleString('id-ID')}`;
            }
            
            function validateForm() {
                const menuItems = daftarMenu.querySelectorAll('.menu-item');
                let isValid = true;
                
                // Validasi minimal satu menu dipilih
                if (menuItems.length === 0) {
                    alert('Harap tambahkan minimal satu menu');
                    return false;
                }
                
                // Validasi setiap menu
                menuItems.forEach(item => {
                    const select = item.querySelector('.menu-select');
                    const jumlahInput = item.querySelector('.jumlah');
                    
                    if (!select.value) {
                        alert('Harap pilih menu untuk semua item');
                        isValid = false;
                        return;
                    }
                    
                    if (!jumlahInput.value || parseInt(jumlahInput.value) < 1) {
                        alert('Jumlah harus minimal 1 untuk semua menu');
                        isValid = false;
                        return;
                    }
                    
                    const stok = parseInt(select.options[select.selectedIndex].dataset.stok);
                    const jumlah = parseInt(jumlahInput.value);
                    
                    if (jumlah > stok) {
                        const menuName = select.options[select.selectedIndex].text.split(' - ')[0];
                        alert(`Stok tidak mencukupi untuk ${menuName}! Stok tersedia: ${stok}`);
                        isValid = false;
                        return;
                    }
                });
                
                // Validasi data pelanggan
                const nama = document.getElementById('nama_pelanggan').value;
                const email = document.getElementById('gmail').value;
                const meja = document.getElementById('no_meja').value;
                
                if (!nama || !email || !meja) {
                    alert('Harap lengkapi data pelanggan');
                    return false;
                }
                
                return isValid;
            }
        });
    </script>
</body>
</html>