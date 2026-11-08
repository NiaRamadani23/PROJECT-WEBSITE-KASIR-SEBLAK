<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

// Pastikan folder assets ada
if (!is_dir('assets')) { mkdir('assets', 0777, true); }

// ==========================================
// FUNGSI BANTU CARI GAMBAR
// ==========================================
function getGambar($nama, $gambar_db = null) {
    if (!empty($gambar_db) && file_exists('assets/'.$gambar_db)) {
        return 'assets/'.$gambar_db;
    }

    $nama_bersih = strtolower(trim($nama));
    $mapping = [
        'kerupuk bawang' => 'kerupuk bawang', 'kerupuk udang' => 'kerupuk udang', 'kerupuk mawar putih' => 'kerupuk mawar putih',
        'kerupuk mawar warna-warni' => 'kerupuk mawar warna warni', 'makroni' => 'makroni', 'makroni spiral' => 'makaroni spiral',
        'kerupuk rantai' => 'kerupuk rantai', 'cikuwa' => 'cikuwa', 'fishball' => 'fish ball', 'salmonball' => 'salmon ball',
        'odeng' => 'odeng', 'crabb stick' => 'crab stick', 'otak-otak' => 'otak otak', 'scallob' => 'scallop', 'fish roll' => 'fish roll',
        'twister' => 'twister', 'tempura' => 'tempura', 'telur' => 'telur', 'cumi' => 'cumi', 'tofu' => 'tofu', 'bihun' => 'mie bihun',
        'bakso' => 'bakso', 'ceker' => 'ceker', 'sosis kecil' => 'sosis kecil', 'sosis merah' => 'sosis merah', 'sosis besar' => 'sosis besar',
        'cuanki lidah' => 'cuanki lidah', 'pilus cikur' => 'pilus cikur', 'siomay kering' => 'siomay kering', 'jamur enoki' => 'jamur enoki',
        'pokcoy' => 'pokcoy', 'dumpling ayam' => 'dumpling ayam', 'dumpling keju' => 'dumpling keju', 'mie kuning' => 'mie kuning',
        'sawi putih' => 'sawi putih', 'sawi hijau' => 'sawi hijau'
    ];

    $file_name = $mapping[$nama_bersih] ?? $nama_bersih;
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG'];
    
    foreach ($extensions as $ext) {
        $path = 'assets/' . $file_name . '.' . $ext;
        if (file_exists($path)) return $path;
    }
    return 'https://via.placeholder.com/100?text=No+Image';
}

// ==========================================
// PROSES CRUD
// ==========================================
if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama_isian']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $status = $conn->real_escape_string($_POST['status_isian']);
    
    $gambar = NULL;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '_' . rand(100,999) . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], 'assets/' . $gambar);
    }

    $stmt = $conn->prepare("INSERT INTO isian (nama_isian, gambar, harga, stok, status_isian) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $nama, $gambar, $harga, $stok, $status);
    $stmt->execute();
    header("Location: kelola_isian.php");
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $q = $conn->query("SELECT gambar FROM isian WHERE id_isian = $id")->fetch_assoc();
    if ($q && $q['gambar'] && file_exists('assets/'.$q['gambar'])) {
        unlink('assets/'.$q['gambar']);
    }
    $conn->query("DELETE FROM isian WHERE id_isian = $id");
    header("Location: kelola_isian.php");
}

if (isset($_POST['update'])) {
    $id = (int)$_POST['id_isian'];
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $status = $conn->real_escape_string($_POST['status_isian']);
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '_' . rand(100,999) . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], 'assets/' . $gambar);
        
        $q = $conn->query("SELECT gambar FROM isian WHERE id_isian = $id")->fetch_assoc();
        if ($q && $q['gambar'] && file_exists('assets/'.$q['gambar'])) {
            unlink('assets/'.$q['gambar']);
        }
        
        $stmt = $conn->prepare("UPDATE isian SET harga=?, stok=?, status_isian=?, gambar=? WHERE id_isian=?");
        $stmt->bind_param("dissi", $harga, $stok, $status, $gambar, $id);
    } else {
        $stmt = $conn->prepare("UPDATE isian SET harga=?, stok=?, status_isian=? WHERE id_isian=?");
        $stmt->bind_param("diss", $harga, $stok, $status, $id);
    }
    $stmt->execute();
    header("Location: kelola_isian.php");
}

$total_antrian = $conn->query("SELECT COUNT(*) AS total FROM pesanan WHERE status_pesanan = 'Antrian'")->fetch_assoc()['total'];
$isian = $conn->query("SELECT * FROM isian ORDER BY id_isian ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kelola Isian - Seblak 3 Saudara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inria Serif', serif; }
    </style>
</head>
<body class="bg-[#F5F0E8] min-h-screen">

<!-- NAVBAR MOBILE -->
<header class="md:hidden sticky top-0 z-40 bg-[#7B1E1E] text-white px-4 py-3 flex items-center justify-between shadow-md">
    <button onclick="bukaSidebarMobile()" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="text-center">
        <p class="text-xs opacity-80 leading-none">Isian</p>
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
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-cash-register w-4"></i> Kasir</a>
            <a href="antrian.php" class="flex items-center justify-between px-3 py-2 rounded hover:bg-[#5E1616]">
                <span class="flex items-center gap-3"><i class="fa-solid fa-list w-4"></i> Antrean</span>
                <?php if ($total_antrian > 0): ?><span class="bg-[#C94F4F] text-white text-xs px-2 rounded-full"><?= $total_antrian ?></span><?php endif; ?>
            </a>
            <a href="laporan.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-chart-line w-4"></i> Catatan Penjualan</a>
            <a href="kelola_isian.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] font-bold"><i class="fa-solid fa-bowl-food w-4"></i> Kelola Isian</a>
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
    <div class="flex-1 ml-0 md:ml-56 pb-6">

        <!-- HEADER HALAMAN -->
        <div class="px-4 md:px-6 pt-4 md:pt-6 pb-2">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
                <div>
                    <span class="text-[10px] md:text-xs font-bold text-[#7B1E1E] tracking-wide uppercase">Manajemen</span>
                    <h1 class="text-xl md:text-2xl font-bold text-[#1A1A1A] mt-1">Kelola Isian</h1>
                    <p class="text-xs md:text-sm text-[#4A4A4A] mt-1">Atur stok, harga, gambar, dan ketersediaan isian.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" 
                        class="w-full md:w-auto bg-[#7B1E1E] hover:bg-[#5E1616] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tambah Isian
                </button>
            </div>
        </div>

        <!-- KONTEN -->
        <div class="px-4 md:px-6 pb-8 space-y-6">
            
            <!-- TAMPILAN MOBILE (KARTU) -->
            <div class="md:hidden space-y-3">
                <?php 
                $isian->data_seek(0); 
                while($r = $isian->fetch_assoc()): 
                    $is_habis = $r['stok'] <= 0 || $r['status_isian'] == 'Habis';
                    $is_hampir = $r['stok'] > 0 && $r['stok'] <= 5;
                    $img_src = getGambar($r['nama_isian'], $r['gambar'] ?? null);
                ?>
                <div class="bg-white rounded-xl p-4 border border-[#EAD9CF] shadow-sm flex gap-4">
                    <img src="<?= $img_src ?>" class="w-16 h-16 object-cover rounded-lg border border-gray-100 shadow-sm shrink-0">
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-bold text-[#1A1A1A] text-base leading-tight"><?= htmlspecialchars($r['nama_isian']) ?></h3>
                            <div class="flex gap-1">
                                <button onclick='editIsian(<?= json_encode($r) ?>)' class="w-7 h-7 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fa-solid fa-pen text-[10px]"></i></button>
                                <a href="?hapus=<?= $r['id_isian'] ?>" onclick="return confirm('Yakin hapus isian ini?')" class="w-7 h-7 rounded-full bg-red-50 text-red-600 flex items-center justify-center"><i class="fa-solid fa-trash text-[10px]"></i></a>
                            </div>
                        </div>
                        <p class="text-[#7B1E1E] font-bold text-sm mb-2">Rp <?= number_format($r['harga'], 0, ',', '.') ?></p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Stok: <strong class="<?= $is_habis ? 'text-red-500' : ($is_hampir ? 'text-orange-500' : 'text-gray-700') ?>"><?= $r['stok'] ?></strong></span>
                            <?php if ($is_habis): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-700">Habis</span>
                            <?php elseif ($is_hampir): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-orange-100 text-orange-700">Hampir Habis</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-green-100 text-green-700">Tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if ($isian->num_rows == 0): ?>
                    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-400 italic text-sm">Belum ada isian.</div>
                <?php endif; ?>
            </div>

            <!-- TAMPILAN DESKTOP (TABEL) -->
            <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-[#EAD9CF] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#FDF6F0] text-[#4A4A4A] border-b border-[#EAD9CF]">
                            <tr>
                                <th class="py-4 px-6 font-bold w-16">No</th>
                                <th class="py-4 px-6 font-bold w-24">Gambar</th>
                                <th class="py-4 px-6 font-bold">Nama Isian</th>
                                <th class="py-4 px-6 font-bold">Harga</th>
                                <th class="py-4 px-6 font-bold">Stok</th>
                                <th class="py-4 px-6 font-bold">Status</th>
                                <th class="py-4 px-6 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EAD9CF]">
                            <?php 
                            $isian->data_seek(0);
                            $no = 1; 
                            while($r = $isian->fetch_assoc()): 
                                $is_habis = $r['stok'] <= 0 || $r['status_isian'] == 'Habis';
                                $is_hampir = $r['stok'] > 0 && $r['stok'] <= 5;
                                $img_src = getGambar($r['nama_isian'], $r['gambar'] ?? null);
                            ?>
                            <tr class="hover:bg-[#FDF6F0] transition group">
                                <td class="py-4 px-6 text-gray-400 font-semibold"><?= str_pad($no++, 2, '0', STR_PAD_LEFT) ?></td>
                                <td class="py-4 px-6"><img src="<?= $img_src ?>" class="w-14 h-14 object-cover rounded-lg border border-gray-200 shadow-sm"></td>
                                <td class="py-4 px-6 font-bold text-[#1A1A1A] text-base"><?= htmlspecialchars($r['nama_isian']) ?></td>
                                <td class="py-4 px-6 text-[#7B1E1E] font-bold">Rp <?= number_format($r['harga'], 0, ',', '.') ?></td>
                                <td class="py-4 px-6"><span class="font-bold text-base <?= $is_habis ? 'text-red-500' : ($is_hampir ? 'text-orange-500' : 'text-gray-700') ?>"><?= $r['stok'] ?></span></td>
                                <td class="py-4 px-6">
                                    <?php if ($is_habis): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200"><i class="fa-solid fa-circle-xmark mr-1"></i> Habis</span>
                                    <?php elseif ($is_hampir): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 border border-orange-200"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Hampir Habis</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fa-solid fa-circle-check mr-1"></i> Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <button onclick='editIsian(<?= json_encode($r) ?>)' class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition inline-flex items-center justify-center" title="Edit"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <a href="?hapus=<?= $r['id_isian'] ?>" onclick="return confirm('Yakin hapus isian ini?')" class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition inline-flex items-center justify-center" title="Hapus"><i class="fa-solid fa-trash text-xs"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL TAMBAH ================= -->
<div id="modalTambah" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-[#FDF6F0]">
            <h3 class="font-bold text-xl text-[#1A1A1A]">Tambah Isian Baru</h3>
            <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-gray-400 hover:text-[#7B1E1E]"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6">
            <div class="mb-4">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Nama Isian</label>
                <input type="text" name="nama_isian" placeholder="Cth: Kerupuk, Bakso" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition" required>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Upload Gambar (Opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-[#7B1E1E] bg-gray-50 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#7B1E1E] file:text-white hover:file:bg-[#5E1616]">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" placeholder="3000" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Stok Awal</label>
                    <input type="number" name="stok" placeholder="100" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Status Ketersediaan</label>
                <select name="status_isian" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition bg-white">
                    <option value="Tersedia">Tersedia</option>
                    <option value="Habis">Habis</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition">Batal</button>
                <button type="submit" name="tambah" class="flex-1 bg-[#7B1E1E] hover:bg-[#5E1616] text-white font-bold py-2.5 rounded-lg transition shadow-md">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="modalEdit" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-[#FDF6F0]">
            <h3 class="font-bold text-xl text-[#1A1A1A]">Edit Isian</h3>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-gray-400 hover:text-[#7B1E1E]"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="id_isian" id="edit_id">
            <div class="mb-4">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Nama Isian</label>
                <input type="text" id="edit_nama" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
                <p class="text-[10px] text-gray-400 mt-1 italic">Nama tidak dapat diubah.</p>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Ganti Gambar (Opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-[#7B1E1E] bg-gray-50 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#7B1E1E] file:text-white hover:file:bg-[#5E1616]">
                <p class="text-[10px] text-gray-400 mt-1 italic">Biarkan kosong jika tidak ingin mengubah gambar.</p>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Stok Tersedia</label>
                    <input type="number" name="stok" id="edit_stok" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-[#4A4A4A] mb-1">Status Ketersediaan</label>
                <select name="status_isian" id="edit_status" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#7B1E1E] transition bg-white">
                    <option value="Tersedia">Tersedia</option>
                    <option value="Habis">Habis</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition">Batal</button>
                <button type="submit" name="update" class="flex-1 bg-[#7B1E1E] hover:bg-[#5E1616] text-white font-bold py-2.5 rounded-lg transition shadow-md">Update Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ===== NAVBAR MOBILE =====
    function bukaSidebarMobile() { 
        document.getElementById('sidebarAsli').classList.remove('-translate-x-full'); 
        document.getElementById('overlaySidebarMobile').classList.remove('hidden'); 
    }
    function tutupSidebarMobile() { 
        document.getElementById('sidebarAsli').classList.add('-translate-x-full'); 
        document.getElementById('overlaySidebarMobile').classList.add('hidden'); 
    }

    // ===== EDIT DATA =====
    function editIsian(data) {
        document.getElementById('edit_id').value = data.id_isian;
        document.getElementById('edit_nama').value = data.nama_isian;
        document.getElementById('edit_harga').value = data.harga;
        document.getElementById('edit_stok').value = data.stok;
        document.getElementById('edit_status').value = data.status_isian;
        document.getElementById('modalEdit').classList.remove('hidden');
    }
</script>

</body>
</html>