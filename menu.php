<?php
include 'config/db.php';

// Pagination setup
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total number of menu items
$total_items = $conn->query("SELECT COUNT(*) FROM menu")->fetch_row()[0];
$total_pages = ceil($total_items / $items_per_page);

// Get menu items for current page (sorted A-Z)
$sql = "SELECT * FROM menu ORDER BY nama_menu ASC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);
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

    <div class="text-center container mt-4">
        <h2 class="text-center mb-4 section-title">Menu Kami</h2>
        
        <div class="row">
            <?php
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

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
        <div class="grid text-center">
            <a href="index.php" class="btn btn-outline-secondary">Kembali ke Menu</a>
        </div>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 Pak Resto Unikom. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>