<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // Password plain text
    
    $sql = "SELECT * FROM akun WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Bandingkan password plain text (tanpa hashing)
        if ($password == $user['password']) {
            $_SESSION['user_id'] = $user['id_akun'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            switch ($user['role']) {
                case 'pemilik':
                    header("Location: ../dashboard/dashboard_pemilik.php");
                    break;
                case 'pelayan':
                    header("Location: ../dashboard/dashboard_pelayan.php");
                    break;
                case 'koki':
                    header("Location: ../dashboard/dashboard_koki.php");
                    break;
                case 'kasir':
                    header("Location: ../dashboard/dashboard_kasir.php");
                    break;
                default:
                    header("Location: ../login.php?error=Role tidak valid");
            }
            exit();
        } else {
            header("Location: ../login.php?error=Password salah");
            exit();
        }
    } else {
        header("Location: ../login.php?error=Username tidak ditemukan");
        exit();
    }
}
?>