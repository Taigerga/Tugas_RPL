<?php
include 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pak Resto Unikom - Menu</title>
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

    <div class="hero-section">
        <div class="container text-center py-5">
            <h1 class="display-4 fw-bold text-white mb-3">Selamat Datang di Pak Resto Unikom </h1>
            <p class="lead text-white">Nikmati hidangan lezat dengan kualitas terbaik</p>
        </div>
    </div>

    <div class="container mt-4 text-center">
        <h2 class="text-center mb-4 section-title">Menu Kami</h2>
        <div class="row">
            <?php
            $sql = "SELECT * FROM menu";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-4 mb-4">';
                    echo '  <div class="card h-100 menu-card">';
                    echo '    <div class="card-img-top" style="background-image: url(\'assets/images/menu-placeholder.jpg\');"></div>';
                    echo '    <div class="card-body">';
                    echo '      <h5 class="card-title">' . htmlspecialchars($row['nama_menu']) . '</h5>';
                    echo '      <p class="card-text text-muted">' . (isset($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : 'Hidangan lezat dari dapur kami') . '</p>';
                    echo '      <div class="d-flex justify-content-between align-items-center">';
                    echo '        <span class="price">Rp ' . number_format($row['harga'], 0, ',', '.') . '</span>';
                    echo '        <span class="badge bg-' . ($row['stok'] > 0 ? 'success' : 'danger') . '">' . ($row['stok'] > 0 ? 'Tersedia' : 'Habis') . '</span>';
                    echo '      </div>';
                    echo '    </div>';
                    echo '  </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="col-12"><p class="text-center">Menu belum tersedia</p></div>';
            }
            ?>
        </div>
    </div>

    <div class="text-center testimonial-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 section-title">Testimonial Pelanggan</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" class="rounded-circle me-3" width="60" alt="Customer">
                            <div>
                                <h5 class="mb-0">Sarah Johnson</h5>
                                <div class="rating text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"Makanan di Pak Resto Unikom  selalu segar dan enak. Pelayanan juga sangat ramah!"</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/men/75.jpg" class="rounded-circle me-3" width="60" alt="Customer">
                            <div>
                                <h5 class="mb-0">Michael Chen</h5>
                                <div class="rating text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"Tempat favorit saya untuk makan siang. Harganya terjangkau dengan kualitas premium."</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" class="rounded-circle me-3" width="60" alt="Customer">
                            <div>
                                <h5 class="mb-0">Lisa Rodriguez</h5>
                                <div class="rating text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"Atmosfernya nyaman dan makanan disajikan dengan indah. Sangat direkomendasikan!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3"><i class="fas fa-utensils me-2"></i>Pak Resto Unikom </h5>
                    <p>Menghadirkan pengalaman kuliner terbaik dengan bahan-bahan pilihan dan resep istimewa.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">Jam Operasional</h5>
                    <ul class="list-unstyled">
                        <li>Senin-Jumat: 10:00 - 22:00</li>
                        <li>Sabtu-Minggu: 09:00 - 23:00</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Hubungi Kami</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> (021) 1234-5678</li>
                        <li><i class="fas fa-envelope me-2"></i> info@cobaresto.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Jl. Resto Enak No. 123, Jakarta</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 Pak Resto Unikom . All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>