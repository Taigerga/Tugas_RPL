<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Filter tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$sql = "SELECT m.nama_menu, SUM(ps.jumlah) as total_terjual, SUM(m.harga * ps.jumlah) as total_pendapatan
        FROM pembayaran pb
        JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
        JOIN menu m ON ps.id_menu = m.id_menu
        WHERE pb.status = 'dibayar' 
        AND DATE(pb.waktu_pembayaran) BETWEEN ? AND ?
        GROUP BY m.id_menu
        ORDER BY total_terjual DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Total keseluruhan
$sql_total = "SELECT SUM(m.harga * ps.jumlah) as total_pendapatan
              FROM pembayaran pb
              JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
              JOIN menu m ON ps.id_menu = m.id_menu
              WHERE pb.status = 'dibayar'
              AND DATE(pb.waktu_pembayaran) BETWEEN ? AND ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("ss", $start_date, $end_date);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total = $result_total->fetch_assoc()['total_pendapatan'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Pak Resto Unikom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .summary-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .chart-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .top-menu-badge {
            background-color: #ffc107;
            color: #333;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .total-highlight {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
        .print-btn {
            position: relative;
            overflow: hidden;
        }
        .print-btn::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            opacity: 0;
            transition: all 0.3s;
        }
        .print-btn:hover::after {
            opacity: 1;
        }
        .date-range {
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container">
            <a class="navbar-brand" href="#">Pak Resto Unikom</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/dashboard_pemilik.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_menu.php">Kelola Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lihat_keluhan.php">Keluhan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Laporan</a>
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
        <h2 class="mb-4">Laporan Penjualan</h2>
        
        <div class="alert-box">
            <div class="d-flex align-items-center">
                <i class="bi bi-graph-up fs-4 text-primary me-3"></i>
                <div>
                    <h5>Analisis Penjualan Restoran</h5>
                    <p class="mb-0">Pantau performa penjualan menu restoran Anda berdasarkan periode waktu tertentu.</p>
                </div>
            </div>
        </div>
        
        <form method="GET" class="row g-3 mb-4 no-print">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-success print-btn" data-bs-toggle="modal" data-bs-target="#printModal">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </button>
            </div>
        </form>
        
        <div class="summary-card p-4 mb-4">
            <div class="row">
                <div class="col-md-8">
                    <h5>Total Pendapatan Periode <span class="date-range"><?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></span></h5>
                    <div class="total-highlight">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    <p class="text-muted mt-2">Laporan ini menunjukkan ringkasan penjualan semua menu dalam periode yang dipilih.</p>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-end">
                    <i class="bi bi-currency-exchange display-4 text-primary"></i>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="bi bi-bar-chart"></i> Grafik Penjualan Menu</h5>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="bi bi-pie-chart"></i> Persentase Pendapatan</h5>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-list"></i> Detail Penjualan Menu
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Menu</th>
                                <th>Terjual</th>
                                <th>Pendapatan</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $result->data_seek(0); // Reset result pointer
                            while ($row = $result->fetch_assoc()): 
                                $percentage = $total > 0 ? ($row['total_pendapatan'] / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($row['nama_menu']); ?>
                                        <?php if($result->num_rows > 3 && $row['total_terjual'] == $result->fetch_row()[1]): ?>
                                            <span class="top-menu-badge ms-2"><i class="bi bi-star"></i> Terlaris</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['total_terjual']; ?></td>
                                    <td>Rp <?php echo number_format($row['total_pendapatan'], 0, ',', '.'); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%" 
                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cetak Laporan -->
    <div class="modal fade no-print" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content confirmation-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="printModalLabel">
                        <i class="bi bi-printer"></i> Konfirmasi Cetak Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-printer text-primary fs-1"></i>
                        <h4 class="mt-2">Cetak Laporan Penjualan</h4>
                    </div>
                    
                    <div class="mb-3">
                        <p>Anda akan mencetak laporan penjualan dengan periode:</p>
                        <div class="alert alert-info">
                            <i class="bi bi-calendar"></i> 
                            <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Total Pendapatan:</strong> 
                        <div class="total-highlight">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> Pastikan printer sudah siap dan terhubung sebelum mencetak.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary btn-confirm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const menuNames = [];
        const totalSold = [];
        const totalRevenue = [];
        
        <?php 
        $result->data_seek(0); 
        while ($row = $result->fetch_assoc()): 
        ?>
            menuNames.push('<?php echo htmlspecialchars($row['nama_menu']); ?>');
            totalSold.push(<?php echo $row['total_terjual']; ?>);
            totalRevenue.push(<?php echo $row['total_pendapatan']; ?>);
        <?php endwhile; ?>
        
        // Sales Chart (Bar Chart)
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: menuNames,
                datasets: [{
                    label: 'Jumlah Terjual',
                    data: totalSold,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Penjualan per Menu'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Terjual'
                        }
                    }
                }
            }
        });
        
        // Revenue Chart (Pie Chart)
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'pie',
            data: {
                labels: menuNames,
                datasets: [{
                    label: 'Pendapatan',
                    data: totalRevenue,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Pendapatan per Menu'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: Rp${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Print styles
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.innerHTML = `
                @media print {
                    body {
                        background-color: white !important;
                        padding: 20px;
                    }
                    .no-print {
                        display: none !important;
                    }
                    .container {
                        max-width: 100% !important;
                        padding: 0 !important;
                    }
                    .summary-card, .chart-container, .card {
                        box-shadow: none !important;
                        border: 1px solid #ddd !important;
                    }
                    .chart-container {
                        page-break-inside: avoid;
                    }
                    h2 {
                        font-size: 1.5rem !important;
                    }
                    .date-range {
                        font-size: 1rem !important;
                    }
                    .total-highlight {
                        font-size: 1.2rem !important;
                    }
                    table {
                        font-size: 0.9rem !important;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>