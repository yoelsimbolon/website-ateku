<?php
session_start();
require 'config.php'; // Koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama) || empty($alamat) || !isset($_FILES['bukti'])) {
        $_SESSION['error'] = "Semua field harus diisi dan bukti pembayaran harus diupload.";
        header("Location: checkout.html");
        exit;
    }

    // Validasi file upload bukti pembayaran
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file = $_FILES['bukti'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Upload bukti pembayaran gagal.";
        header("Location: checkout.html");
        exit;
    }
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Format file harus JPG atau PNG.";
        header("Location: checkout.html");
        exit;
    }

    // Simpan file bukti ke folder uploads/
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid() . '-' . basename($file['name']);
    $target_file = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        $_SESSION['error'] = "Gagal menyimpan file bukti pembayaran.";
        header("Location: checkout.html");
        exit;
    }

    // Ambil data keranjang dari session atau request (disini contoh dari request POST JSON)
    // Jika data keranjang dari frontend masih di localStorage, frontend harus mengirimkan ke backend
    // Di sini asumsikan data keranjang dikirim melalui POST dengan field 'keranjang' berupa JSON string
    if (!isset($_POST['keranjang'])) {
        $_SESSION['error'] = "Data keranjang tidak ditemukan.";
        header("Location: checkout.html");
        exit;
    }

    $keranjang = json_decode($_POST['keranjang'], true);
    if (!$keranjang || count($keranjang) == 0) {
        $_SESSION['error'] = "Keranjang kosong.";
        header("Location: checkout.html");
        exit;
    }

    // Koneksi database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // Simpan data pesanan ke tabel orders
        $stmt = $conn->prepare("INSERT INTO orders (nama, alamat, bukti_pembayaran, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("sss", $nama, $alamat, $filename);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Simpan detail pesanan ke tabel order_items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_id, nama_menu, harga, jumlah) VALUES (?, ?, ?, ?, ?)");
        foreach ($keranjang as $item) {
            $stmt->bind_param("issii", $order_id, $item['id'], $item['nama'], $item['harga'], $item['jumlah']);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();

        // Bersihkan keranjang (frontend harus bersihkan localStorage setelah sukses checkout)
        $_SESSION['success'] = "Pesanan berhasil dikirim. Terima kasih!";
        header("Location: index.html");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: checkout.html");
        exit;
    }
} else {
    header("Location: checkout.html");
    exit;
}
?>
