<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Penjualan_Seblak_3_Saudara_$tgl_mulai-$tgl_selesai.xls");

$query = "SELECT p.*, pm.metode_pembayaran, pm.status_pembayaran 
          FROM pesanan p 
          LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan 
          WHERE DATE(p.tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai' 
          ORDER BY p.tanggal_pesanan ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
</head>
<body>
    <h3>Laporan Penjualan Seblak 3 Saudara</h3>
    <p>Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai)) ?></p>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nomor Antrean</th>
                <th>Nama Pembeli</th>
                <th>Level/Kuah/Penyajian</th>
                <th>Total (Rp)</th>
                <th>Metode</th>
                <th>Status Bayar</th>
                <th>Status Pesanan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])) ?></td>
                <td><?= $row['nomor_antrean'] ?? $row['id_pesanan'] ?></td>
                <td><?= htmlspecialchars($row['nama_pembeli']) ?></td>
                <td>Lvl <?= $row['level_pedas'] ?> / <?= $row['jenis_kuah'] ?> / <?= $row['jenis_penyajian'] ?></td>
                <td><?= $row['total_harga'] ?></td>
                <td><?= $row['metode_pembayaran'] ?? 'Tunai' ?></td>
                <td><?= $row['status_pembayaran'] ?? 'Lunas' ?></td>
                <td><?= $row['status_pesanan'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>