<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uang_diterima = isset($_GET['uang']) ? (int)$_GET['uang'] : 0;

$q_pesanan = $conn->query("
    SELECT p.*, pm.metode_pembayaran 
    FROM pesanan p 
    LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan 
    WHERE p.id_pesanan = $id_pesanan
");
$pesanan = $q_pesanan->fetch_assoc();
if (!$pesanan) { die("Pesanan tidak ditemukan."); }

$q_detail = $conn->query("
    SELECT d.jumlah, d.subtotal, i.nama_isian, i.harga 
    FROM detail_pesanan d 
    JOIN isian i ON d.id_isian = i.id_isian 
    WHERE d.id_pesanan = $id_pesanan
");
$items = [];
while($d = $q_detail->fetch_assoc()) { $items[] = $d; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Pesanan #<?= $pesanan['nomor_antrean'] ?? $pesanan['id_pesanan'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inria Serif', serif; }
        .struk-garis { border-bottom: 2px dashed #EAD9CF; margin: 16px 0; }
        .struk-lengkung { position: relative; }
        .struk-lengkung::before, .struk-lengkung::after {
            content: ''; position: absolute; top: 0; width: 24px; height: 24px;
            background-color: #F5F0E8; border-radius: 50%;
        }
        .struk-lengkung::before { left: -12px; }
        .struk-lengkung::after { right: -12px; }

        /* CSS KHUSUS PRINT (UKURAN KERTAS STRUK) */
        @media print {
            @page { margin: 0; size: 80mm auto; } /* Ukuran kertas struk 80mm */
            body { margin: 0; padding: 0; background: white; }
            body * { visibility: hidden; }
            #printSection, #printSection * { visibility: visible; }
            #printSection { 
                position: absolute; left: 0; top: 0; 
                width: 100%; max-width: 80mm; 
                box-shadow: none !important; border: none !important;
            }
            .no-print { display: none !important; }
            .struk-lengkung::before, .struk-lengkung::after { display: none; }
            .struk-garis { border-bottom: 2px dashed #000; }
        }
    </style>
</head>
<body class="bg-[#F5F0E8] min-h-screen flex items-center justify-center p-4">

    <!-- AREA STRUK (Hanya ini yang di-print) -->
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden my-auto" id="printSection">
            <div class="p-6 text-center struk-lengkung">
                
                <div class="w-16 h-16 bg-[#FDF6F0] border border-[#EAD9CF] rounded-full flex items-center justify-center mx-auto mb-3 text-[#D94A3D]">
                    <svg width="32" height="32" viewBox="0 0 100 100" fill="none">
                        <path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path d="M50 20 C50 15 48 12 50 8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <path d="M38 22 C38 17 36 14 38 10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <path d="M62 22 C62 17 60 14 62 10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <ellipse cx="50" cy="45" rx="35" ry="5" stroke="currentColor" stroke-width="4" fill="none"/>
                    </svg>
                </div>
                <h3 class="font-bold text-2xl tracking-widest text-[#1A1A1A] mb-1" style="font-family: monospace;">SEBLAK 3 SAUDARA</h3>
                <p class="text-xs text-gray-500 mb-2">Jl. Raya Seblak No. 1, Kota Anda</p>

                <div class="struk-garis"></div>

                <div class="text-xs text-left space-y-1 mb-2 font-mono text-gray-700">
                    <div class="flex justify-between"><span>No. Nota</span><span class="font-bold">#<?= str_pad($pesanan['nomor_antrean'] ?? $pesanan['id_pesanan'], 4, '0', STR_PAD_LEFT) ?></span></div>
                    <div class="flex justify-between"><span>Tanggal</span><span class="font-bold"><?= date('d/m/y H:i', strtotime($pesanan['tanggal_pesanan'])) ?></span></div>
                    <div class="flex justify-between"><span>Kasir</span><span class="font-bold"><?= htmlspecialchars($_SESSION['nama_pengguna']) ?></span></div>
                    <div class="flex justify-between"><span>Pelanggan</span><span class="font-bold"><?= htmlspecialchars($pesanan['nama_pembeli']) ?></span></div>
                    <div class="flex justify-between"><span>Tipe</span><span class="font-bold uppercase"><?= $pesanan['jenis_penyajian'] ?></span></div>
                </div>

                <div class="struk-garis"></div>

                <div class="text-left mb-2">
                    <h4 class="font-bold text-sm mb-1 text-[#1A1A1A]">Paket Seblak (Lvl <?= $pesanan['level_pedas'] ?> - <?= $pesanan['jenis_kuah'] ?>)</h4>
                    <div class="text-xs font-mono text-gray-700 space-y-1">
                        <?php foreach($items as $item): ?>
                        <div class="flex justify-between">
                            <span><?= htmlspecialchars($item['nama_isian']) ?></span>
                            <span>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between text-gray-400 mb-1">
                            <span><?= $item['jumlah'] ?> x <?= number_format($item['harga'], 0, ',', '.') ?></span>
                            <span></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="struk-garis"></div>

                <div class="bg-[#FDF6F0] rounded-xl p-4 text-left font-mono mb-4">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-sm text-[#1A1A1A]">TOTAL</span>
                        <span class="font-bold text-lg text-[#1A1A1A]">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                        <span>Metode</span><span class="uppercase"><?= $pesanan['metode_pembayaran'] ?? 'Tunai' ?></span>
                    </div>
                    <?php if (($pesanan['metode_pembayaran'] ?? 'Tunai') === 'Tunai'): ?>
                    <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                        <span>Tunai</span><span>Rp <?= number_format($uang_diterima, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-bold text-[#4A7C59] mt-2 pt-2 border-t border-[#EAD9CF]">
                        <span>Kembali</span><span>- Rp <?= number_format($uang_diterima - $pesanan['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="struk-garis"></div>

                <div class="text-left text-xs mb-6">
                    <p class="font-bold text-gray-700 mb-1">Catatan:</p>
                    <p class="text-gray-500 italic"><?= htmlspecialchars($pesanan['catatan'] ?? '-') ?></p>
                </div>

                <!-- Footer tanpa tulisan SELESAI -->
                <div class="text-center mt-4">
                    <p class="font-bold text-sm text-[#1A1A1A]">Terima kasih atas kunjungan Anda!</p>
                    <p class="text-xs text-gray-500 mb-2">Dukung UMKM Lokal</p>
                    <p class="text-[10px] text-gray-400 font-mono"><?= date('d-m-Y H:i:s') ?></p>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="mt-4 flex gap-2 no-print">
            <a href="dashboard.php" class="flex-1 bg-gray-200 text-gray-700 text-center font-bold py-3 rounded-xl hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="flex-1 bg-[#D94A3D] text-white font-bold py-3 rounded-xl hover:bg-[#B8382D] transition shadow-md">
                <i class="fa-solid fa-print mr-1"></i> Cetak
            </button>
        </div>
    </div>

</body>
</html>