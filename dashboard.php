<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

$isian = $conn->query("SELECT * FROM isian ORDER BY id_isian ASC");

$total_antrian = $conn->query("SELECT COUNT(*) AS total FROM pesanan WHERE status_pesanan = 'Antrian'")->fetch_assoc()['total'];

// ===== LOGIKA NOMOR ANTREAN RESET PER HARI =====
$nomor_antrean_hari_ini = $conn->query("SELECT COUNT(*) AS jml FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE()")->fetch_assoc()['jml'];
$next_id = $nomor_antrean_hari_ini + 1;

// ===== QUERY PRODUK TERLARIS HARI INI =====
$best_seller = "Belum ada";
$q_best = $conn->query("
    SELECT i.nama_isian, SUM(d.jumlah) as total_terjual 
    FROM detail_pesanan d
    JOIN pesanan p ON d.id_pesanan = p.id_pesanan
    JOIN isian i ON d.id_isian = i.id_isian
    WHERE DATE(p.tanggal_pesanan) = CURDATE()
    GROUP BY d.id_isian
    ORDER BY total_terjual DESC
    LIMIT 1
");
if ($q_best && $q_best->num_rows > 0) {
    $best = $q_best->fetch_assoc();
    $best_seller = htmlspecialchars($best['nama_isian']) . ' (' . $best['total_terjual'] . 'x)';
}

// ===== QUERY ISIAN HAMPIR HABIS (STOK <= 5) =====
$hampir_habis = $conn->query("SELECT nama_isian, stok FROM isian WHERE stok > 0 AND stok <= 5 ORDER BY stok ASC");
$list_hampir_habis = [];
while($h = $hampir_habis->fetch_assoc()) {
    $list_hampir_habis[] = $h['nama_isian'] . ' (' . $h['stok'] . ')';
}

function getGambar($nama) {
    $nama_bersih = strtolower(trim($nama));
    $mapping = [
        'kerupuk bawang'            => 'kerupuk bawang',
        'kerupuk udang'             => 'kerupuk udang',
        'kerupuk mawar putih'       => 'kerupuk mawar putih',
        'kerupuk mawar warna-warni' => 'kerupuk mawar warna warni',
        'makroni'                   => 'makroni',
        'makroni spiral'            => 'makaroni spiral',
        'kerupuk rantai'            => 'kerupuk rantai',
        'cikuwa'                    => 'cikuwa',
        'fishball'                  => 'fish ball',
        'salmonball'                => 'salmon ball',
        'odeng'                     => 'odeng',
        'crabb stick'               => 'crab stick',
        'otak-otak'                 => 'otak otak',
        'scallob'                   => 'scallop',
        'fish roll'                 => 'fish roll',
        'twister'                   => 'twister',
        'tempura'                   => 'tempura',
        'telur'                     => 'telur',
        'cumi'                      => 'cumi',
        'tofu'                      => 'tofu',
        'bihun'                     => 'mie bihun',
        'bakso'                     => 'bakso',
        'ceker'                     => 'ceker',
        'sosis kecil'               => 'sosis kecil',
        'sosis merah'               => 'sosis merah',
        'sosis besar'               => 'sosis besar',
        'cuanki lidah'              => 'cuanki lidah',
        'pilus cikur'               => 'pilus cikur',
        'siomay kering'             => 'siomay kering',
        'jamur enoki'               => 'jamur enoki',
        'pokcoy'                    => 'pokcoy',
        'dumpling ayam'             => 'dumpling ayam',
        'dumpling keju'             => 'dumpling keju',
        'mie kuning'                => 'mie kuning',
        'sawi putih'                => 'sawi putih',
        'sawi hijau'                => 'sawi hijau'
    ];
    $file_name = $mapping[$nama_bersih] ?? $nama_bersih;
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG'];
    foreach ($extensions as $ext) {
        $path = 'assets/' . $file_name . '.' . $ext;
        if (file_exists($path)) return $path;
    }
    return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?q=80&w=400&auto=format&fit=crop';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kasir - Seblak 3 Saudara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inria Serif', serif; }
        .sheet-mobile { transition: transform .35s cubic-bezier(.22,.9,.35,1), opacity .25s ease, visibility .25s; transform: translateY(100%); opacity: 0; visibility: hidden; pointer-events: none; }
        .sheet-mobile.open { transform: translateY(0); opacity: 1; visibility: visible; pointer-events: auto; }
        .safe-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
        #btnKeranjangMobile { z-index: 45; }
        #overlaySheetMobile { z-index: 50; }
        #sheetMobile { z-index: 55; }
        .cart-scroll::-webkit-scrollbar { width: 4px; }
        .cart-scroll::-webkit-scrollbar-thumb { background: #e5d5c8; border-radius: 10px; }
        .cart-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body class="bg-[#F5F0E8] min-h-screen">

<!-- HEADER MOBILE -->
<header class="md:hidden sticky top-0 z-40 bg-[#7B1E1E] text-white px-4 py-3 flex items-center justify-between shadow-md">
    <button type="button" onclick="bukaSidebarMobile()" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="text-center">
        <p class="text-xs opacity-80 leading-none">Kasir</p>
        <p class="font-bold text-sm leading-tight">Seblak 3 Saudara</p>
    </div>
    <div class="w-10 h-10"></div>
</header>

<div id="overlaySidebarMobile" onclick="tutupSidebarMobile()" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden"></div>

<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside id="sidebarAsli" class="w-56 bg-[#7B1E1E] text-white flex flex-col p-4 fixed h-full z-50 -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col items-center mb-8">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none">
                <path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z" stroke="white" stroke-width="3" fill="none"/>
                <path d="M50 20 C50 15 48 12 50 8" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <path d="M38 22 C38 17 36 14 38 10" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <path d="M62 22 C62 17 60 14 62 10" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <ellipse cx="50" cy="45" rx="35" ry="5" stroke="white" stroke-width="3" fill="none"/>
            </svg>
            <h2 class="text-lg font-bold mt-2">Seblak</h2>
            <p class="text-sm">3 Saudara</p>
        </div>
        <nav class="space-y-2 text-sm flex-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] font-bold"><i class="fa-solid fa-cash-register w-4"></i> Kasir</a>
            <a href="antrian.php" class="flex items-center justify-between px-3 py-2 rounded hover:bg-[#5E1616]">
                <span class="flex items-center gap-3"><i class="fa-solid fa-list w-4"></i> Antrean</span>
                <?php if ($total_antrian > 0): ?><span class="bg-[#C94F4F] text-white text-xs px-2 rounded-full"><?= $total_antrian ?></span><?php endif; ?>
            </a>
            <a href="laporan.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-chart-line w-4"></i> Catatan Penjualan</a>
            <a href="kelola_isian.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-bowl-food w-4"></i> Kelola Isian</a>
        </nav>
        <div class="mt-4 pt-4 border-t border-white/20">
            <div class="flex items-center gap-3 px-3 py-2 mb-2">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center"><i class="fa-solid fa-user text-sm"></i></div>
                <div class="text-sm">
                    <p class="font-bold leading-tight"><?= htmlspecialchars($_SESSION['nama_pengguna']) ?></p>
                    <p class="text-[10px] text-white/70 leading-tight">Admin</p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] text-sm font-semibold hover:bg-[#4A1111] transition"><i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Logout</a>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <div class="flex-1 ml-0 md:ml-56 pb-24 md:pb-0">
        <!-- HERO HEADER -->
        <div class="px-4 md:px-6 pt-4 md:pt-6">
            <div class="bg-[#FDF6F0] rounded-xl p-4 md:p-5 flex flex-col-reverse md:flex-row items-start md:items-center justify-between border border-[#EAD9CF] relative overflow-hidden gap-4 md:gap-0">
                <div class="z-10 w-full md:w-auto">
                    <span class="text-[10px] md:text-xs font-bold text-[#7B1E1E] tracking-wide uppercase">Kasir</span>
                    <h1 class="text-xl md:text-2xl font-bold text-[#1A1A1A] mt-1 mb-2 leading-tight">Kelola Pesanan dengan Mudah</h1>
                    <p class="text-xs md:text-sm text-[#4A4A4A] mb-3 leading-relaxed">Pilih isian dari stok yang tersedia, total akan dihitung otomatis.</p>
                    <div class="flex flex-wrap gap-2 text-[10px] md:text-xs">
                        <span class="bg-[#7B1E1E]/10 text-[#7B1E1E] px-2.5 py-1 rounded-full font-semibold whitespace-nowrap"><i class="fa-solid fa-crown text-[#E89B3C]"></i> Best Seller: <?= $best_seller ?></span>
                        <?php if (!empty($list_hampir_habis)): ?>
                            <span class="bg-[#C94F4F]/10 text-[#C94F4F] px-2.5 py-1 rounded-full font-semibold whitespace-nowrap"><i class="fa-solid fa-triangle-exclamation"></i> Hampir habis: <?= implode(', ', array_slice($list_hampir_habis, 0, 2)) ?><?= count($list_hampir_habis) > 2 ? '...' : '' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <img src="assets/gambar seblak full toping.jpg" alt="Seblak" class="w-full h-40 md:w-40 md:h-28 object-cover rounded-lg shadow-md shrink-0">
            </div>
        </div>

        <!-- KONTEN: MENU + KERANJANG -->
        <div class="flex gap-6 p-4 md:p-6">
            <!-- KIRI: DAFTAR ISIAN -->
            <div class="flex-1">
                <h2 class="font-bold text-lg mb-4 text-[#1A1A1A]">Isi / Isian</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                    <?php while ($row = $isian->fetch_assoc()):
                        $stok_habis = $row['stok'] <= 0 || $row['status_isian'] == 'Habis';
                        $stok_hampir = $row['stok'] > 0 && $row['stok'] <= 5;
                        $gambar = getGambar($row['nama_isian']);
                    ?>
                    <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-[#EAD9CF] <?= $stok_habis ? 'opacity-70' : '' ?> flex flex-col">
                        <div class="relative aspect-square bg-gray-50 overflow-hidden">
                            <img src="<?= $gambar ?>" alt="<?= htmlspecialchars($row['nama_isian']) ?>" class="w-full h-full object-cover">
                            <?php if ($stok_habis): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="bg-[#C94F4F] text-white text-xs font-bold px-3 py-1 rounded-full">Stok habis</span></div>
                            <?php elseif ($stok_hampir): ?>
                                <div class="absolute top-2 right-2"><span class="bg-[#E89B3C] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Hampir habis</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-sm text-[#1A1A1A] leading-tight mb-1"><?= htmlspecialchars($row['nama_isian']) ?></h3>
                                <p class="text-xs text-gray-500 mb-2">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                            </div>
                            <div>
                                <?php if ($stok_habis): ?>
                                    <span class="text-[10px] bg-[#C94F4F]/10 text-[#C94F4F] px-2 py-0.5 rounded-full font-semibold inline-block mb-2">Habis</span>
                                <?php elseif ($stok_hampir): ?>
                                    <span class="text-[10px] bg-[#E89B3C]/15 text-[#E89B3C] px-2 py-0.5 rounded-full font-semibold inline-block mb-2">Sisa <?= $row['stok'] ?></span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-[#4A7C59]/10 text-[#4A7C59] px-2 py-0.5 rounded-full font-semibold inline-block mb-2">Stok: <?= $row['stok'] ?></span>
                                <?php endif; ?>
                                <div class="flex items-center justify-between border border-gray-200 rounded-lg mt-1 px-1 py-1">
                                    <button type="button" onclick="kurangItem(<?= $row['id_isian'] ?>)" class="w-8 h-8 flex items-center justify-center text-[#7B1E1E] font-bold text-lg hover:bg-gray-100 rounded">−</button>
                                    <span id="qty-<?= $row['id_isian'] ?>" class="text-sm font-bold text-[#1A1A1A]">0</span>
                                    <button type="button" onclick="tambahItem(<?= $row['id_isian'] ?>, '<?= addslashes($row['nama_isian']) ?>', <?= $row['harga'] ?>, <?= $row['stok'] ?>)" class="w-8 h-8 flex items-center justify-center text-[#7B1E1E] font-bold text-lg hover:bg-gray-100 rounded <?= $stok_habis ? 'opacity-40 cursor-not-allowed' : '' ?>" <?= $stok_habis ? 'disabled' : '' ?>>+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- KANAN: KERANJANG (DESKTOP) -->
            <div class="hidden lg:block w-80 shrink-0">
                <div class="bg-white rounded-xl shadow-sm border border-[#EAD9CF] sticky top-6 flex flex-col max-h-[calc(100vh-3rem)]">
                    <div class="p-5 overflow-y-auto cart-scroll flex-1">
                        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                            <h2 class="font-bold text-[#1A1A1A]">Pesanan baru</h2>
                            <button type="button" onclick="resetCart()" class="text-xs text-[#7B1E1E] hover:underline">Reset</button>
                        </div>
                        <div class="mb-4">
                            <label class="text-xs font-bold text-[#4A4A4A] block mb-1">Nama Pelanggan</label>
                            <input type="text" id="namaPembeli" placeholder="Masukkan nama" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#7B1E1E]">
                        </div>
                        <div class="mb-4">
                            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Level Pedas</label>
                            <div class="flex gap-2">
                                <button type="button" onclick="setLevel(0)" id="level-0" class="level-btn w-9 h-9 rounded-lg border border-gray-200 text-sm font-bold text-[#7B1E1E] hover:border-[#7B1E1E] transition">0</button>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" onclick="setLevel(<?= $i ?>)" id="level-<?= $i ?>" class="level-btn w-9 h-9 rounded-lg border border-gray-200 text-sm font-bold text-[#7B1E1E] hover:border-[#7B1E1E] transition"><?= $i ?></button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="levelPedas" value="1">
                            <p class="text-[10px] text-gray-400 mt-1 italic">Level 0 = Tidak Pedas</p>
                        </div>
                        <div class="mb-4">
                            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Jenis Kuah</label>
                            <div class="flex gap-2">
                                <button type="button" onclick="setKuah('Nyemek')" id="kuah-Nyemek" class="kuah-btn flex-1 border border-gray-200 rounded-lg py-2 text-xs font-semibold text-[#7B1E1E] hover:border-[#7B1E1E]">Nyemek</button>
                                <button type="button" onclick="setKuah('Kuah')" id="kuah-Kuah" class="kuah-btn flex-1 border border-gray-200 rounded-lg py-2 text-xs font-semibold text-[#7B1E1E] hover:border-[#7B1E1E]">Kuah</button>
                            </div>
                            <input type="hidden" id="jenisKuah" value="Nyemek">
                        </div>
                        <div class="mb-4">
                            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Jenis Penyajian</label>
                            <div class="flex gap-2">
                                <button type="button" onclick="setSajian('Dibungkus')" id="sajian-Dibungkus" class="sajian-btn flex-1 border border-gray-200 rounded-lg py-2 text-xs font-semibold text-[#7B1E1E] hover:border-[#7B1E1E]">Dibungkus</button>
                                <button type="button" onclick="setSajian('Di makan di tempat')" id="sajian-Di makan di tempat" class="sajian-btn flex-1 border border-gray-200 rounded-lg py-2 text-xs font-semibold text-[#7B1E1E] hover:border-[#7B1E1E]">Makan di tempat</button>
                            </div>
                            <input type="hidden" id="jenisPenyajian" value="Dibungkus">
                        </div>
                        <div class="mb-4 pt-4 border-t border-gray-100">
                            <h3 class="text-xs font-bold text-[#4A4A4A] mb-3">Detail pesanan</h3>
                            <div id="cartItems" class="space-y-3">
                                <p class="text-xs text-gray-400 italic text-center py-4">Belum ada isian dipilih</p>
                            </div>
                        </div>
                        <div class="bg-[#FDF6F0] border border-[#EAD9CF] rounded-lg p-3 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-[#E89B3C]"></i>
                            <p class="text-xs text-[#4A4A4A]">Minimal pembelian <strong>Rp 10.000</strong></p>
                        </div>
                    </div>
                    <div class="p-5 border-t border-gray-100 bg-white rounded-b-xl shrink-0">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-bold text-[#1A1A1A]">Total</span>
                            <span id="totalPrice" class="font-bold text-lg text-[#7B1E1E]">Rp 0</span>
                        </div>
                        <button type="button" onclick="bayarSekarang()" class="w-full bg-[#7B1E1E] text-white text-sm font-bold py-3 rounded-lg hover:bg-[#5E1616] transition shadow-md flex justify-center items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave"></i> Bayar Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOMBOL FLOATING KERANJANG MOBILE -->
<button id="btnKeranjangMobile" type="button" onclick="bukaSheetMobile()" class="lg:hidden fixed bottom-4 left-4 right-4 bg-[#7B1E1E] text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center justify-between active:scale-[.98] transition safe-bottom">
    <span class="flex items-center gap-2 font-semibold text-sm">
        <span class="relative"><i class="fa-solid fa-cart-shopping"></i><span id="badgeMobile" class="hidden absolute -top-2 -right-2 bg-[#E89B3C] text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span></span>
        Keranjang
    </span>
    <span class="flex items-center gap-3"><span id="totalMobile" class="font-bold">Rp 0</span><i class="fa-solid fa-chevron-up text-xs opacity-80"></i></span>
</button>

<!-- BOTTOM SHEET KERANJANG MOBILE -->
<div id="overlaySheetMobile" onclick="tutupSheetMobile()" class="hidden lg:hidden fixed inset-0 bg-black/50"></div>
<div id="sheetMobile" class="sheet-mobile lg:hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl max-h-[92vh] flex flex-col">
    <div class="flex justify-center pt-3 pb-1 shrink-0"><div class="w-12 h-1.5 bg-gray-300 rounded-full"></div></div>
    <div class="flex items-center justify-between px-5 pt-2 pb-3 border-b border-gray-100 shrink-0">
        <h2 class="font-bold text-[#1A1A1A]">Pesanan Baru</h2>
        <div class="flex items-center gap-3">
            <button type="button" onclick="resetCart()" class="text-xs text-[#7B1E1E] hover:underline">Reset</button>
            <button type="button" onclick="tutupSheetMobile()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>
    </div>
    <div class="overflow-y-auto flex-1 px-5 py-4">
        <div class="mb-4">
            <label class="text-xs font-bold text-[#4A4A4A] block mb-1">Nama Pelanggan</label>
            <input type="text" id="namaPembeliMobile" placeholder="Masukkan nama" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E]">
        </div>
        <div class="mb-4">
            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Level Pedas</label>
            <div class="flex gap-2">
                <button type="button" onclick="setLevel(0)" id="level-0-m" class="level-btn-m w-9 h-9 rounded-lg border border-gray-200 text-sm font-bold text-[#7B1E1E] transition">0</button>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" onclick="setLevel(<?= $i ?>)" id="level-<?= $i ?>-m" class="level-btn-m w-9 h-9 rounded-lg border border-gray-200 text-sm font-bold text-[#7B1E1E] transition"><?= $i ?></button>
                <?php endfor; ?>
            </div>
        </div>
        <div class="mb-4">
            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Jenis Kuah</label>
            <div class="flex gap-2">
                <button type="button" onclick="setKuah('Nyemek')" id="kuah-Nyemek-m" class="kuah-btn-m flex-1 border border-gray-200 rounded-lg py-2.5 text-xs font-semibold text-[#7B1E1E] transition">Nyemek</button>
                <button type="button" onclick="setKuah('Kuah')" id="kuah-Kuah-m" class="kuah-btn-m flex-1 border border-gray-200 rounded-lg py-2.5 text-xs font-semibold text-[#7B1E1E] transition">Kuah</button>
            </div>
        </div>
        <div class="mb-4">
            <label class="text-xs font-bold text-[#4A4A4A] block mb-2">Jenis Penyajian</label>
            <div class="flex gap-2">
                <button type="button" onclick="setSajian('Dibungkus')" id="sajian-Dibungkus-m" class="sajian-btn-m flex-1 border border-gray-200 rounded-lg py-2.5 text-xs font-semibold text-[#7B1E1E] transition">Dibungkus</button>
                <button type="button" onclick="setSajian('Di makan di tempat')" id="sajian-Di makan di tempat-m" class="sajian-btn-m flex-1 border border-gray-200 rounded-lg py-2.5 text-xs font-semibold text-[#7B1E1E] transition">Makan di tempat</button>
            </div>
        </div>
        <div class="mb-4 pt-4 border-t border-gray-100">
            <h3 class="text-xs font-bold text-[#4A4A4A] mb-3">Detail pesanan</h3>
            <div id="cartItemsMobile" class="space-y-3">
                <p class="text-xs text-gray-400 italic text-center py-4">Belum ada isian dipilih</p>
            </div>
        </div>
    </div>
    <div class="border-t border-gray-100 px-5 py-4 safe-bottom bg-white shrink-0">
        <div class="flex justify-between items-center mb-3">
            <span class="font-bold text-[#1A1A1A]">Total</span>
            <span id="totalPriceMobile" class="font-bold text-lg text-[#7B1E1E]">Rp 0</span>
        </div>
        <button type="button" onclick="bayarSekarangMobile()" class="w-full bg-[#7B1E1E] text-white text-sm font-bold py-3.5 rounded-xl hover:bg-[#5E1616] transition shadow-md flex justify-center items-center gap-2"><i class="fa-solid fa-money-bill-wave"></i> Bayar Sekarang</button>
    </div>
</div>

<!-- MODAL PEMBAYARAN -->
<div id="modalBayar" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-5 pb-3 shrink-0 border-b border-gray-100">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-2xl text-[#1A1A1A]">Pembayaran <span id="kodePesanan" class="text-[#7B1E1E]">#00</span></h3>
                <button onclick="closePayment()" class="text-gray-400 hover:text-gray-600 text-sm">Tutup</button>
            </div>
            <p id="ringkasanPesanan" class="text-sm text-gray-500 mt-1">-</p>
        </div>
        
        <div class="p-5 overflow-y-auto cart-scroll flex-1 bg-gray-50">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Rincian Pesanan</h4>
            <div id="modalRincianItems" class="space-y-3 mb-4">
                <!-- Diisi via JS -->
            </div>
        </div>

        <div class="p-5 shrink-0 border-t border-gray-100 bg-white">
            <div class="mb-4 bg-gray-100 rounded-xl p-3 flex justify-between items-center">
                <span class="text-gray-600 font-bold">Total Bayar</span>
                <span id="modalTotal" class="font-bold text-2xl text-[#7B1E1E]">Rp 0</span>
            </div>
            <div class="mb-4">
                <div class="flex gap-2 bg-gray-100 rounded-xl p-1">
                    <button type="button" onclick="setMetode('Tunai')" id="tab-Tunai" class="flex-1 py-2.5 rounded-lg font-bold text-sm bg-[#D94A3D] text-white transition">Tunai</button>
                    <button type="button" onclick="setMetode('QRIS')" id="tab-QRIS" class="flex-1 py-2.5 rounded-lg font-bold text-sm text-gray-500 transition">QRIS</button>
                </div>
                <input type="hidden" id="metodePembayaran" value="Tunai">
            </div>
            <div id="panelTunai">
                <label class="block text-sm text-gray-600 mb-2">Uang Diterima (Rp)</label>
                <input type="number" id="uangDiterima" value="0" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xl font-bold text-[#1A1A1A] focus:outline-none focus:border-[#7B1E1E]">
                <div class="mt-3 bg-[#FDECEA] rounded-xl p-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600 font-bold">Kembalian</span>
                    <span id="kembalian" class="font-bold text-xl text-[#D94A3D]">Rp 0</span>
                </div>
            </div>
            <div id="panelQRIS" class="hidden">
                <div class="bg-[#FDECEA] rounded-xl p-3 text-center">
                    <p class="text-xs text-gray-600 mb-2">Silakan scan QRIS di bawah ini:</p>
                    <div class="bg-white p-2 rounded-xl border border-[#EAD9CF] inline-block mb-2">
                        <img src="assets/qris-toko.jpg" alt="QRIS Toko" class="w-48 h-48 object-contain rounded-lg">
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <button type="button" onclick="processPayment()" class="w-full bg-[#D94A3D] hover:bg-[#B8382D] text-white font-bold py-3.5 rounded-xl transition shadow-md">Selesaikan Pesanan</button>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    let total = 0;
    let nomorAntreanBerikutnya = <?= $next_id ?>;
    const MINIMAL_BELI = 10000;

    function setLevel(n) {
        document.getElementById('levelPedas').value = n;
        document.querySelectorAll('.level-btn, .level-btn-m').forEach(b => { b.classList.remove('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); b.classList.add('text-[#7B1E1E]'); });
        const btn = document.getElementById('level-' + n); if (btn) { btn.classList.remove('text-[#7B1E1E]'); btn.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
        const btnM = document.getElementById('level-' + n + '-m'); if (btnM) { btnM.classList.remove('text-[#7B1E1E]'); btnM.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
    }
    setLevel(1);

    function setKuah(val) {
        document.getElementById('jenisKuah').value = val;
        document.querySelectorAll('.kuah-btn, .kuah-btn-m').forEach(b => { b.classList.remove('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); b.classList.add('text-[#7B1E1E]'); });
        const btn = document.getElementById('kuah-' + val); if (btn) { btn.classList.remove('text-[#7B1E1E]'); btn.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
        const btnM = document.getElementById('kuah-' + val + '-m'); if (btnM) { btnM.classList.remove('text-[#7B1E1E]'); btnM.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
    }
    setKuah('Nyemek');

    function setSajian(val) {
        document.getElementById('jenisPenyajian').value = val;
        document.querySelectorAll('.sajian-btn, .sajian-btn-m').forEach(b => { b.classList.remove('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); b.classList.add('text-[#7B1E1E]'); });
        const btn = document.getElementById('sajian-' + val); if (btn) { btn.classList.remove('text-[#7B1E1E]'); btn.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
        const btnM = document.getElementById('sajian-' + val + '-m'); if (btnM) { btnM.classList.remove('text-[#7B1E1E]'); btnM.classList.add('bg-[#7B1E1E]', 'text-white', 'border-[#7B1E1E]'); }
    }
    setSajian('Dibungkus');

    function tambahItem(id, name, price, stok) {
        const existing = cart.find(i => i.id === id);
        if (existing) { if (existing.qty >= stok) return alert("Stok tidak cukup!"); existing.qty++; } 
        else { cart.push({ id, name, price, qty: 1, stok }); }
        renderCart();
    }

    function kurangItem(id) {
        const idx = cart.findIndex(i => i.id === id);
        if (idx === -1) return;
        cart[idx].qty--;
        if (cart[idx].qty <= 0) cart.splice(idx, 1);
        renderCart();
    }

    function tambahDariKeranjang(id) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        if (item.qty >= item.stok) return alert("Stok tidak cukup!");
        item.qty++;
        renderCart();
    }

    function kurangDariKeranjang(id) {
        const idx = cart.findIndex(i => i.id === id);
        if (idx === -1) return;
        cart[idx].qty--;
        if (cart[idx].qty <= 0) cart.splice(idx, 1);
        renderCart();
    }

    function renderCart() {
        document.querySelectorAll('[id^="qty-"]').forEach(el => el.innerText = '0');
        cart.forEach(item => { const el = document.getElementById('qty-' + item.id); if (el) el.innerText = item.qty; });

        const c = document.getElementById('cartItems');
        if (c) {
            c.innerHTML = ''; total = 0;
            if (cart.length === 0) c.innerHTML = '<p class="text-xs text-gray-400 italic text-center py-4">Belum ada isian dipilih</p>';
            cart.forEach((item) => {
                total += item.price * item.qty;
                c.innerHTML += `<div class="flex justify-between items-center"><div><p class="font-bold text-xs text-[#1A1A1A]">${item.name}</p><p class="text-[10px] text-gray-500">Rp ${item.price.toLocaleString()}</p></div><div class="flex items-center gap-1"><button onclick="kurangDariKeranjang(${item.id})" class="w-6 h-6 bg-gray-100 rounded text-[#7B1E1E] font-bold text-xs">−</button><span class="text-xs font-bold w-5 text-center">${item.qty}</span><button onclick="tambahDariKeranjang(${item.id})" class="w-6 h-6 bg-gray-100 rounded text-[#7B1E1E] font-bold text-xs">+</button></div></div>`;
            });
            document.getElementById('totalPrice').innerText = 'Rp ' + total.toLocaleString();
        }

        const cm = document.getElementById('cartItemsMobile');
        if (cm) {
            cm.innerHTML = '';
            if (cart.length === 0) cm.innerHTML = '<p class="text-xs text-gray-400 italic text-center py-4">Belum ada isian dipilih</p>';
            cart.forEach((item) => {
                cm.innerHTML += `<div class="flex justify-between items-center"><div><p class="font-bold text-xs text-[#1A1A1A]">${item.name}</p><p class="text-[10px] text-gray-500">Rp ${item.price.toLocaleString()}</p></div><div class="flex items-center gap-1"><button onclick="kurangDariKeranjang(${item.id})" class="w-7 h-7 bg-gray-100 rounded text-[#7B1E1E] font-bold text-sm">−</button><span class="text-xs font-bold w-5 text-center">${item.qty}</span><button onclick="tambahDariKeranjang(${item.id})" class="w-7 h-7 bg-gray-100 rounded text-[#7B1E1E] font-bold text-sm">+</button></div></div>`;
            });
            document.getElementById('totalPriceMobile').innerText = 'Rp ' + total.toLocaleString();
        }

        const tm = document.getElementById('totalMobile'); if (tm) tm.innerText = 'Rp ' + total.toLocaleString();
        const badge = document.getElementById('badgeMobile');
        if (badge) { const qtySum = cart.reduce((s, i) => s + i.qty, 0); if (qtySum > 0) { badge.innerText = qtySum; badge.classList.remove('hidden'); } else { badge.classList.add('hidden'); } }
        
        if (!document.getElementById('modalBayar').classList.contains('hidden')) {
            renderModalRincian();
        }
    }

    function renderModalRincian() {
        let html = '';
        total = 0;
        if (cart.length === 0) {
            html = '<p class="text-sm text-gray-400 italic text-center py-4">Pesanan kosong.</p>';
        } else {
            cart.forEach(item => {
                total += item.price * item.qty;
                html += `
                    <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-gray-100 shadow-sm">
                        <div>
                            <p class="font-bold text-sm text-[#1A1A1A]">${item.name}</p>
                            <p class="text-xs text-gray-500">Rp ${item.price.toLocaleString()}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="kurangDariKeranjang(${item.id})" class="w-7 h-7 bg-gray-100 rounded-md text-[#7B1E1E] font-bold text-sm">−</button>
                            <span class="text-sm font-bold w-5 text-center">${item.qty}</span>
                            <button type="button" onclick="tambahDariKeranjang(${item.id})" class="w-7 h-7 bg-gray-100 rounded-md text-[#7B1E1E] font-bold text-sm">+</button>
                        </div>
                    </div>
                `;
            });
        }
        document.getElementById('modalRincianItems').innerHTML = html;
        document.getElementById('modalTotal').innerText = 'Rp ' + total.toLocaleString();
        document.getElementById('uangDiterima').value = total;
        updateKembalian();
    }

    function resetCart() {
        if (!confirm("Reset semua pesanan?")) return;
        cart = [];
        document.getElementById('namaPembeli').value = '';
        const nm = document.getElementById('namaPembeliMobile'); if (nm) nm.value = '';
        setLevel(1); setKuah('Nyemek'); setSajian('Dibungkus'); renderCart();
    }

    function validasi() {
        if (cart.length === 0) { alert("Pilih isian dulu!"); return false; }
        const nama = document.getElementById('namaPembeli').value || (document.getElementById('namaPembeliMobile')?.value || '');
        if (!nama) { alert("Isi nama pelanggan!"); return false; }
        if (total < MINIMAL_BELI) { alert(`Minimal pembelian adalah Rp ${MINIMAL_BELI.toLocaleString()}!\nTotal pesanan kamu baru Rp ${total.toLocaleString()}.`); return false; }
        return true;
    }

    function bayarSekarang() {
        if (!validasi()) return;
        document.getElementById('kodePesanan').innerText = '#' + String(nomorAntreanBerikutnya).padStart(2, '0');
        const nama = document.getElementById('namaPembeli').value || (document.getElementById('namaPembeliMobile')?.value || '');
        const level = document.getElementById('levelPedas').value;
        const kuah = document.getElementById('jenisKuah').value;
        const sajian = document.getElementById('jenisPenyajian').value;
        document.getElementById('ringkasanPesanan').innerText = `${nama} · Level ${level} · ${kuah} · ${sajian}`;
        
        renderModalRincian();
        setMetode('Tunai');
        document.getElementById('modalBayar').classList.remove('hidden');
    }

    function closePayment() { document.getElementById('modalBayar').classList.add('hidden'); }

    function setMetode(metode) {
        document.getElementById('metodePembayaran').value = metode;
        const tabTunai = document.getElementById('tab-Tunai'); const tabQRIS  = document.getElementById('tab-QRIS');
        const panelTunai = document.getElementById('panelTunai'); const panelQRIS  = document.getElementById('panelQRIS');
        if (metode === 'Tunai') {
            tabTunai.className = 'flex-1 py-2.5 rounded-lg font-bold text-sm bg-[#D94A3D] text-white transition';
            tabQRIS.className  = 'flex-1 py-2.5 rounded-lg font-bold text-sm text-gray-500 transition';
            panelTunai.classList.remove('hidden'); panelQRIS.classList.add('hidden');
        } else {
            tabQRIS.className  = 'flex-1 py-2.5 rounded-lg font-bold text-sm bg-[#D94A3D] text-white transition';
            tabTunai.className = 'flex-1 py-2.5 rounded-lg font-bold text-sm text-gray-500 transition';
            panelQRIS.classList.remove('hidden'); panelTunai.classList.add('hidden');
        }
    }

    function updateKembalian() {
        const uang = parseInt(document.getElementById('uangDiterima').value) || 0;
        const kembali = uang - total;
        document.getElementById('kembalian').innerText = 'Rp ' + (kembali > 0 ? kembali : 0).toLocaleString();
    }
    document.getElementById('uangDiterima').addEventListener('input', updateKembalian);

    function processPayment() {
        if (!validasi()) return;
        const metode = document.getElementById('metodePembayaran').value;
        let uangDiterima = 0;
        if (metode === 'Tunai') {
            uangDiterima = parseInt(document.getElementById('uangDiterima').value) || 0;
            if (uangDiterima < total) { alert("Uang diterima kurang dari total!"); return; }
        } else {
            uangDiterima = total;
        }
        kirimPesanan(metode, uangDiterima);
    }

    function kirimPesanan(metode, uangDiterima) {
        const nama = document.getElementById('namaPembeli').value || (document.getElementById('namaPembeliMobile')?.value || '');
        const data = {
            nama_pembeli: nama, level_pedas: document.getElementById('levelPedas').value,
            jenis_kuah: document.getElementById('jenisKuah').value, jenis_penyajian: document.getElementById('jenisPenyajian').value,
            metode_pembayaran: metode, uang_diterima: uangDiterima, total: total, items: cart
        };
        fetch('simpan_pesanan.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                window.location.href = 'nota.php?id=' + res.id_pesanan + '&uang=' + uangDiterima;
            } else {
                alert('Gagal: ' + res.message);
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function bukaSidebarMobile() { document.getElementById('sidebarAsli').classList.remove('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.remove('hidden'); }
    function tutupSidebarMobile() { document.getElementById('sidebarAsli').classList.add('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.add('hidden'); }
    function bukaSheetMobile() { document.getElementById('overlaySheetMobile').classList.remove('hidden'); document.getElementById('sheetMobile').classList.add('open'); const namaD = document.getElementById('namaPembeli').value; const namaM = document.getElementById('namaPembeliMobile'); if (namaD && !namaM.value) namaM.value = namaD; }
    function tutupSheetMobile() { document.getElementById('sheetMobile').classList.remove('open'); document.getElementById('overlaySheetMobile').classList.add('hidden'); const namaM = document.getElementById('namaPembeliMobile').value; if (namaM) document.getElementById('namaPembeli').value = namaM; }
    function bayarSekarangMobile() { const namaM = document.getElementById('namaPembeliMobile').value.trim(); if (!namaM) { alert("Isi nama pelanggan!"); return; } document.getElementById('namaPembeli').value = namaM; tutupSheetMobile(); bayarSekarang(); }

    renderCart();
</script>
</body>
</html>