<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require 'config/database.php';
session_start();

function balas($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['id_penjual'])) {
    balas(['status' => 'error', 'message' => 'Belum login']);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    balas(['status' => 'error', 'message' => 'Data tidak valid']);
}

$wajib = ['nama_pembeli', 'level_pedas', 'jenis_kuah', 'jenis_penyajian', 'total', 'items'];
foreach ($wajib as $key) {
    if (!isset($data[$key])) {
        balas(['status' => 'error', 'message' => "Field $key tidak ada"]);
    }
}

if (empty($data['items'])) {
    balas(['status' => 'error', 'message' => 'Keranjang kosong']);
}

$id_penjual      = (int)$_SESSION['id_penjual'];
$nama_pembeli    = $conn->real_escape_string($data['nama_pembeli']);
$level_pedas     = (int)$data['level_pedas'];
$jenis_kuah      = $conn->real_escape_string($data['jenis_kuah']);
$jenis_penyajian = $conn->real_escape_string($data['jenis_penyajian']);
$total           = (float)$data['total'];
$metode          = $conn->real_escape_string($data['metode_pembayaran'] ?? 'Tunai');
$uang_diterima   = (float)($data['uang_diterima'] ?? 0);
$kembalian       = $uang_diterima - $total;
if ($kembalian < 0) $kembalian = 0;

$status_pesanan = 'Antrian';
$status_bayar   = ($uang_diterima >= $total) ? 'Lunas' : 'Belum Lunas';

$conn->begin_transaction();

try {
    // ========================================================
    // LOGIKA NOMOR ANTREAN RESET PER HARI (BARU)
    // ========================================================
    $q_hari_ini = $conn->query("SELECT COUNT(*) AS total FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE()");
    $jml_hari_ini = $q_hari_ini->fetch_assoc()['total'];
    $nomor_antrean = $jml_hari_ini + 1;

    // Query INSERT ditambahin kolom `nomor_antrean`
    $sql1 = "INSERT INTO pesanan 
             (id_penjual, nomor_antrean, nama_pembeli, level_pedas, jenis_kuah, jenis_penyajian, total_harga, status_pesanan, tanggal_pesanan) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql1);
    if (!$stmt) throw new Exception("Prepare pesanan: " . $conn->error);

    // Binding parameter disesuaikan (ada tambahan 'i' untuk nomor_antrean)
    $stmt->bind_param("iisissds", 
        $id_penjual, 
        $nomor_antrean,
        $nama_pembeli, 
        $level_pedas, 
        $jenis_kuah, 
        $jenis_penyajian, 
        $total, 
        $status_pesanan
    );
    if (!$stmt->execute()) throw new Exception("Execute pesanan: " . $stmt->error);
    $id_pesanan = $conn->insert_id;
    $stmt->close();
    // ========================================================

    $sql2 = "INSERT INTO detail_pesanan 
             (id_pesanan, id_isian, jumlah, harga_satuan, subtotal) 
             VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = $conn->prepare($sql2);
    if (!$stmt_detail) throw new Exception("Prepare detail: " . $conn->error);

    $sql3 = "UPDATE isian SET stok = stok - ? WHERE id_isian = ?";
    $stmt_stok = $conn->prepare($sql3);
    if (!$stmt_stok) throw new Exception("Prepare stok: " . $conn->error);

    foreach ($data['items'] as $item) {
        $id_isian = (int)$item['id'];
        $jumlah   = (int)$item['qty'];
        $harga    = (float)$item['price'];
        $subtotal = $harga * $jumlah;

        $stmt_detail->bind_param("iiidd", $id_pesanan, $id_isian, $jumlah, $harga, $subtotal);
        if (!$stmt_detail->execute()) throw new Exception("Execute detail: " . $stmt_detail->error);

        $stmt_stok->bind_param("ii", $jumlah, $id_isian);
        if (!$stmt_stok->execute()) throw new Exception("Execute stok: " . $stmt_stok->error);
    }
    $stmt_detail->close();
    $stmt_stok->close();

    $sql4 = "INSERT INTO pembayaran 
             (id_pesanan, metode_pembayaran, uang_diterima, kembalian, status_pembayaran, tanggal_pembayaran) 
             VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_bayar = $conn->prepare($sql4);
    if (!$stmt_bayar) throw new Exception("Prepare bayar: " . $conn->error);

    $tipe_bayar = "isdd" . "s";
    $stmt_bayar->bind_param($tipe_bayar, 
        $id_pesanan, 
        $metode, 
        $uang_diterima, 
        $kembalian, 
        $status_bayar
    );
    if (!$stmt_bayar->execute()) throw new Exception("Execute bayar: " . $stmt_bayar->error);
    $stmt_bayar->close();

    $conn->commit();

    balas([
        'status'     => 'success',
        'id_pesanan' => $id_pesanan,
        'nomor'      => str_pad($nomor_antrean, 2, '0', STR_PAD_LEFT)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    balas(['status' => 'error', 'message' => $e->getMessage()]);
}
?>