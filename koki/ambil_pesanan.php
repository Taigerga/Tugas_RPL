<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'koki') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_produksi = $_POST['id_produksi'];
    $id_koki = $_POST['id_koki'];
    
    // Update status dari 'pending' ke 'dimasak' dan isi id_koki
    $sql = "UPDATE produksi 
            SET status = 'dimasak', 
                id_koki = ?,
                waktu_mulai = NOW() 
            WHERE id_produksi = ? 
            AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_koki, $id_produksi);
    
    if ($stmt->execute()) {
        header("Location: lihat_pesanan.php?success=Pesanan berhasil diambil dan status diubah ke dimasak");
    } else {
        header("Location: lihat_pesanan.php?error=Gagal mengambil pesanan");
    }
    exit();
}

header("Location: lihat_pesanan.php");
exit();
?>