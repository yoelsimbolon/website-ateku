<?php
session_start();
require 'config.php'; // file koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Semua field harus diisi.";
        header("Location: register.html");
        exit;
    }

    // Koneksi database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Cek apakah email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Email sudah terdaftar, silakan login.";
        $stmt->close();
        $conn->close();
        header("Location: register.html");
        exit;
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Simpan user baru
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
        $stmt->close();
        $conn->close();
        header("Location: login.html");
        exit;
    } else {
        $_SESSION['error'] = "Registrasi gagal, coba lagi.";
        $stmt->close();
        $conn->close();
        header("Location: register.html");
        exit;
    }
} else {
    header("Location: register.html");
    exit;
}
?>
