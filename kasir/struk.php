<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Validasi parameter
if (!isset($_GET['id_pembayaran']) || !is_numeric($_GET['id_pembayaran'])) {
    die("Parameter id_pembayaran tidak valid.");
}

$id_pembayaran = $_GET['id_pembayaran'];

// Ambil data pembayaran
$sql = "SELECT pb.*, pl.nama as nama_pelanggan, k.nama as nama_kasir, 
               DATE_FORMAT(pb.waktu_pembayaran, '%d/%m/%Y %H:%i') as waktu_bayar
        FROM pembayaran pb
        JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
        JOIN pelanggan pl ON ps.id_pelanggan = pl.id_pelanggan
        JOIN kasir k ON pb.id_kasir = k.id_kasir
        WHERE pb.id_pembayaran = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $id_pembayaran);
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Data pembayaran tidak ditemukan.");
}

$pembayaran = $result->fetch_assoc();

// Ambil detail pesanan
$sql_detail = "SELECT m.nama_menu, ps.jumlah, m.harga, (m.harga * ps.jumlah) as subtotal
               FROM pesanan ps
               JOIN menu m ON ps.id_menu = m.id_menu
               WHERE ps.id_pesanan = ?";
$stmt_detail = $conn->prepare($sql_detail);
if (!$stmt_detail) {
    die("Error preparing detail statement: " . $conn->error);
}

// Dapatkan id_pesanan dari data pembayaran
$id_pesanan = $pembayaran['id_pesanan'];
$stmt_detail->bind_param("i", $id_pesanan);
if (!$stmt_detail->execute()) {
    die("Error executing detail query: " . $stmt_detail->error);
}

$detail_pesanan = $stmt_detail->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .struk-container, .struk-container * {
                visibility: visible;
            }
            .struk-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
        .struk-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: 'Courier New', monospace;
            background-color: #fff;
        }
        .header-struk {
            text-align: center;
            margin-bottom: 15px;
        }
        .header-struk h3 {
            font-weight: bold;
            margin-bottom: 5px;
            color: #0d6efd;
        }
        .divider {
            border-top: 2px dashed #000;
            margin: 15px 0;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .item-name {
            flex: 2;
        }
        .item-qty, .item-price, .item-subtotal {
            flex: 1;
            text-align: right;
        }
        .total-section {
            margin-top: 15px;
            font-weight: bold;
            text-align: right;
            font-size: 1.1em;
        }
        .footer-struk {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
            color: #6c757d;
        }
        .receipt-icon {
            font-size: 3rem;
            color: #198754;
            margin-bottom: 10px;
        }
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
        .preview-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .preview-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        .preview-total {
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #6c757d;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="container mt-4 no-print">
        <div class="d-flex justify-content-between mb-4">
            <a href="pembayaran.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                <i class="bi bi-printer"></i> Cetak Struk
            </button>
        </div>
        
        <div class="alert alert-info">
            <h4><i class="bi bi-info-circle"></i> Informasi Pembayaran</h4>
            <p>Berikut adalah detail pembayaran untuk transaksi #<?php echo $id_pembayaran; ?>.</p>
            <p>Klik tombol "Cetak Struk" untuk mencetak struk pembayaran.</p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-receipt"></i> Preview Struk
            </div>
            <div class="card-body">
                <div class="preview-box">
                    <div class="preview-header">Pak Resto Unikom</div>
                    <div class="text-center mb-2">Jl. Contoh No. 123, Kota</div>
                    <div class="text-center mb-3">Telp: 0812-3456-7890</div>
                    
                    <div class="divider"></div>
                    
                    <div class="mb-2">
                        <strong>No. Struk:</strong> <?php echo $id_pembayaran; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Tanggal:</strong> <?php echo $pembayaran['waktu_bayar']; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Kasir:</strong> <?php echo htmlspecialchars($pembayaran['nama_kasir']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Pelanggan:</strong> <?php echo htmlspecialchars($pembayaran['nama_pelanggan']); ?>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="preview-header">Detail Pesanan</div>
                    <div class="preview-item" style="font-weight: bold;">
                        <div>Menu</div>
                        <div>Qty</div>
                        <div>Harga</div>
                        <div>Subtotal</div>
                    </div>
                    
                    <?php while ($item = $detail_pesanan->fetch_assoc()): ?>
                        <div class="preview-item">
                            <div><?php echo htmlspecialchars($item['nama_menu']); ?></div>
                            <div><?php echo $item['jumlah']; ?></div>
                            <div><?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                            <div><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="preview-total">
                        Total: Rp <?php echo number_format($pembayaran['total'], 0, ',', '.'); ?>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="text-center mt-3">
                        Terima kasih atas kunjungan Anda
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="struk-container">
        <div class="header-struk">
            <div class="receipt-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <h3>Pak Resto Unikom</h3>
            <p>Jl. Contoh No. 123, Kota</p>
            <p>Telp: 0812-3456-7890</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="info-struk">
            <p><strong>No. Struk:</strong> <?php echo $id_pembayaran; ?></p>
            <p><strong>Tanggal:</strong> <?php echo $pembayaran['waktu_bayar']; ?></p>
            <p><strong>Kasir:</strong> <?php echo htmlspecialchars($pembayaran['nama_kasir']); ?></p>
            <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($pembayaran['nama_pelanggan']); ?></p>
            <p><strong>Metode Bayar:</strong> <?php echo htmlspecialchars($pembayaran['metode_pembayaran']); ?></p>
        </div>
        
        <div class="divider"></div>
        
        <div class="items-struk">
            <div class="item-row" style="font-weight: bold;">
                <div class="item-name">Menu</div>
                <div class="item-qty">Qty</div>
                <div class="item-price">Harga</div>
                <div class="item-subtotal">Subtotal</div>
            </div>
            
            <?php 
            // Reset pointer untuk hasil query detail pesanan
            $detail_pesanan->data_seek(0);
            while ($item = $detail_pesanan->fetch_assoc()): 
            ?>
                <div class="item-row">
                    <div class="item-name"><?php echo htmlspecialchars($item['nama_menu']); ?></div>
                    <div class="item-qty"><?php echo $item['jumlah']; ?></div>
                    <div class="item-price"><?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                    <div class="item-subtotal"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="divider"></div>
        
        <div class="total-section">
            <p>Total: Rp <?php echo number_format($pembayaran['total'], 0, ',', '.'); ?></p>
        </div>
        
        <div class="footer-struk">
            <p>Terima kasih atas kunjungan Anda</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
        </div>
    </div>

    <!-- Modal Konfirmasi Cetak -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content confirmation-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="bi bi-printer"></i> Konfirmasi Cetak Struk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-question-circle text-primary fs-1"></i>
                        <h4 class="mt-2">Apakah Anda yakin ingin mencetak struk?</h4>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Pastikan printer sudah siap dan terhubung dengan komputer.
                    </div>
                    
                    <div class="mt-3">
                        <strong>Detail Pembayaran:</strong>
                        <div class="mt-2">
                            <div><strong>ID Pembayaran:</strong> #<?php echo $id_pembayaran; ?></div>
                            <div><strong>Pelanggan:</strong> <?php echo htmlspecialchars($pembayaran['nama_pelanggan']); ?></div>
                            <div><strong>Total:</strong> Rp <?php echo number_format($pembayaran['total'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary btn-confirm" onclick="printStruk()">
                        <i class="bi bi-printer"></i> Ya, Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printStruk() {
            window.print();
            // Tutup modal setelah mencetak
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            modal.hide();
        }
        
        // Auto print jika diinginkan (opsional)
        window.onload = function() {
            // Uncomment baris berikut jika ingin auto print
            // setTimeout(function() { 
            //     const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            //     modal.show();
            // }, 1000);
        };
    </script>
</body>
</html>