<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

$total_antrian = $conn->query("SELECT COUNT(*) AS total FROM pesanan WHERE status_pesanan = 'Antrian'")->fetch_assoc()['total'];

$sql = "SELECT p.*, pm.metode_pembayaran, pm.status_pembayaran 
        FROM pesanan p 
        LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan 
        WHERE p.status_pesanan != 'Telah Diambil' 
        ORDER BY p.tanggal_pesanan ASC";
$result = $conn->query($sql);

$data_antrian = []; $data_proses = []; $data_selesai = [];
while ($row = $result->fetch_assoc()) {
    $detail = $conn->query("SELECT d.*, i.nama_isian FROM detail_pesanan d JOIN isian i ON d.id_isian = i.id_isian WHERE d.id_pesanan = {$row['id_pesanan']}");
    $items = [];
    while ($d = $detail->fetch_assoc()) { $items[] = $d['nama_isian'] . ($d['jumlah'] > 1 ? ' x' . $d['jumlah'] : ''); }
    $row['items'] = implode(', ', $items);
    
    // Ambil nomor antrean, kalau kosong pakai ID
    $nomor_tampil = $row['nomor_antrean'] ?? $row['id_pesanan'];
    if ($row['status_pesanan'] == 'Antrian') { $data_antrian[] = $row; }
    elseif ($row['status_pesanan'] == 'Diproses') { $data_proses[] = $row; }
    elseif ($row['status_pesanan'] == 'Selesai') { $data_selesai[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Antrian - Seblak 3 Saudara</title>
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
    <button type="button" onclick="bukaSidebarMobile()" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i class="fa-solid fa-bars"></i></button>
    <div class="text-center"><p class="text-xs opacity-80 leading-none">Antrean</p><p class="font-bold text-sm leading-tight">Seblak 3 Saudara</p></div>
    <div class="w-10 h-10"></div>
</header>
<div id="overlaySidebarMobile" onclick="tutupSidebarMobile()" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden"></div>

<div class="flex min-h-screen">
    <aside id="sidebarAsli" class="w-56 bg-[#7B1E1E] text-white flex flex-col p-4 fixed h-full z-50 -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col items-center mb-8">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none"><path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z" stroke="white" stroke-width="3" fill="none"/><path d="M50 20 C50 15 48 12 50 8" stroke="white" stroke-width="3" stroke-linecap="round"/><path d="M38 22 C38 17 36 14 38 10" stroke="white" stroke-width="3" stroke-linecap="round"/><path d="M62 22 C62 17 60 14 62 10" stroke="white" stroke-width="3" stroke-linecap="round"/><ellipse cx="50" cy="45" rx="35" ry="5" stroke="white" stroke-width="3" fill="none"/></svg>
            <h2 class="text-lg font-bold mt-2">Seblak</h2><p class="text-sm">3 Saudara</p>
        </div>
        <nav class="space-y-2 text-sm flex-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-cash-register w-4"></i> Kasir</a>
            <a href="antrian.php" class="flex items-center justify-between px-3 py-2 rounded bg-[#5E1616] font-bold"><span class="flex items-center gap-3"><i class="fa-solid fa-list w-4"></i> Antrean</span><?php if ($total_antrian > 0): ?><span class="bg-[#C94F4F] text-white text-xs px-2 rounded-full"><?= $total_antrian ?></span><?php endif; ?></a>
            <a href="laporan.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-chart-line w-4"></i> Catatan Penjualan</a>
            <a href="kelola_isian.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-bowl-food w-4"></i> Kelola Isian</a>
        </nav>
        <div class="mt-4 pt-4 border-t border-white/20">
            <div class="flex items-center gap-3 px-3 py-2 mb-2"><div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center"><i class="fa-solid fa-user text-sm"></i></div><div class="text-sm"><p class="font-bold leading-tight"><?= htmlspecialchars($_SESSION['nama_pengguna']) ?></p><p class="text-[10px] text-white/70 leading-tight">Admin</p></div></div>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] text-sm font-semibold hover:bg-[#4A1111] transition"><i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Logout</a>
        </div>
    </aside>

    <main class="flex-1 ml-0 md:ml-56 pb-6">
        <div class="px-4 md:px-6 pt-4 md:pt-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <span class="text-[10px] md:text-xs font-bold text-[#7B1E1E] tracking-wide uppercase">Manajemen</span>
                    <h1 class="text-xl md:text-2xl font-bold text-[#1A1A1A] mt-1">Antrean Pesanan</h1>
                    <p class="text-xs md:text-sm text-[#4A4A4A] mt-1">Pantau status pesanan dari antrean hingga diambil.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 px-4 md:px-6 pb-6">
            <!-- KOLOM ANTRIAN -->
            <div id="kolomAntrian">
                <div class="flex items-center justify-between mb-3"><h2 class="flex items-center gap-2 font-bold text-[#1A1A1A]"><span class="w-7 h-7 flex items-center justify-center bg-[#C94F4F] text-white rounded-full text-xs"><i class="fa-solid fa-hourglass-half"></i></span> Antrian</h2><span class="bg-[#C94F4F] text-white text-xs px-2.5 py-0.5 rounded-full font-bold"><?= count($data_antrian) ?></span></div>
                <div class="space-y-3">
                    <?php if (empty($data_antrian)): ?><div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center empty-state"><p class="text-xs text-gray-400 italic">Belum ada antrian</p></div>
                    <?php else: foreach ($data_antrian as $row): ?>
                    <div class="bg-white rounded-xl p-4 border border-[#EAD9CF] shadow-sm">
                        <div class="flex justify-between items-start mb-2"><h3 class="font-bold text-[#7B1E1E]">#<?= str_pad($row['nomor_antrean'] ?? $row['id_pesanan'], 2, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($row['nama_pembeli']) ?></h3><span class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal_pesanan'])) ?></span></div>
                        <p class="text-xs text-gray-600 mb-1"><?= $row['items'] ?></p>
                        <p class="text-xs text-gray-500 mb-2">Level <?= $row['level_pedas'] ?> · <?= $row['jenis_kuah'] ?> · <?= $row['jenis_penyajian'] ?></p>
                        <p class="font-bold text-[#7B1E1E] mb-3">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> <span class="text-xs font-normal text-gray-500 italic">· <?= $row['status_pembayaran'] == 'Lunas' ? 'sudah dibayar' : 'belum dibayar' ?> (<?= $row['metode_pembayaran'] ?? 'Tunai' ?>)</span></p>
                        <button type="button" onclick="ubahStatus(<?= $row['id_pesanan'] ?>, 'Diproses', this, 'kolomProses')" class="w-full bg-[#C94F4F] text-white text-sm font-bold py-2.5 rounded-md hover:bg-[#A83E3E] transition"><i class="fa-solid fa-fire mr-1"></i> Proses</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <!-- KOLOM DIPROSES -->
            <div id="kolomProses">
                <div class="flex items-center justify-between mb-3"><h2 class="flex items-center gap-2 font-bold text-[#1A1A1A]"><span class="w-7 h-7 flex items-center justify-center bg-[#E89B3C] text-white rounded-full text-xs"><i class="fa-solid fa-fire"></i></span> Diproses</h2><span class="bg-[#E89B3C] text-white text-xs px-2.5 py-0.5 rounded-full font-bold"><?= count($data_proses) ?></span></div>
                <div class="space-y-3">
                    <?php if (empty($data_proses)): ?><div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center empty-state"><p class="text-xs text-gray-400 italic">Belum ada yang diproses</p></div>
                    <?php else: foreach ($data_proses as $row): ?>
                    <div class="bg-white rounded-xl p-4 border border-[#EAD9CF] shadow-sm">
                        <div class="flex justify-between items-start mb-2"><h3 class="font-bold text-[#7B1E1E]">#<?= str_pad($row['nomor_antrean'] ?? $row['id_pesanan'], 2, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($row['nama_pembeli']) ?></h3><span class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal_pesanan'])) ?></span></div>
                        <p class="text-xs text-gray-600 mb-1"><?= $row['items'] ?></p>
                        <p class="text-xs text-gray-500 mb-2">Level <?= $row['level_pedas'] ?> · <?= $row['jenis_kuah'] ?> · <?= $row['jenis_penyajian'] ?></p>
                        <p class="font-bold text-[#7B1E1E] mb-3">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> <span class="text-xs font-normal text-gray-500 italic">· <?= $row['status_pembayaran'] == 'Lunas' ? 'sudah dibayar' : 'belum dibayar' ?> (<?= $row['metode_pembayaran'] ?? 'Tunai' ?>)</span></p>
                        <button type="button" onclick="ubahStatus(<?= $row['id_pesanan'] ?>, 'Selesai', this, 'kolomSelesai')" class="w-full bg-[#E89B3C] text-white text-sm font-bold py-2.5 rounded-md hover:bg-[#C9832A] transition"><i class="fa-solid fa-check mr-1"></i> Tandai Selesai</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <!-- KOLOM SELESAI -->
            <div id="kolomSelesai">
                <div class="flex items-center justify-between mb-3"><h2 class="flex items-center gap-2 font-bold text-[#1A1A1A]"><span class="w-7 h-7 flex items-center justify-center bg-[#4A7C59] text-white rounded-full text-xs"><i class="fa-solid fa-bag-shopping"></i></span> Selesai</h2><span class="bg-[#4A7C59] text-white text-xs px-2.5 py-0.5 rounded-full font-bold"><?= count($data_selesai) ?></span></div>
                <div class="space-y-3">
                    <?php if (empty($data_selesai)): ?><div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center empty-state"><p class="text-xs text-gray-400 italic">Belum ada yang selesai</p></div>
                    <?php else: foreach ($data_selesai as $row): ?>
                    <div class="bg-white rounded-xl p-4 border border-[#EAD9CF] shadow-sm">
                        <div class="flex justify-between items-start mb-2"><h3 class="font-bold text-[#7B1E1E]">#<?= str_pad($row['nomor_antrean'] ?? $row['id_pesanan'], 2, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($row['nama_pembeli']) ?></h3><span class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal_pesanan'])) ?></span></div>
                        <p class="text-xs text-gray-600 mb-1"><?= $row['items'] ?></p>
                        <p class="text-xs text-gray-500 mb-2">Level <?= $row['level_pedas'] ?> · <?= $row['jenis_kuah'] ?> · <?= $row['jenis_penyajian'] ?></p>
                        <p class="font-bold text-[#7B1E1E] mb-3">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> <span class="text-xs font-normal text-gray-500 italic">· <?= $row['status_pembayaran'] == 'Lunas' ? 'sudah dibayar' : 'belum dibayar' ?> (<?= $row['metode_pembayaran'] ?? 'Tunai' ?>)</span></p>
                        <button type="button" onclick="ubahStatus(<?= $row['id_pesanan'] ?>, 'Telah Diambil', this, null)" class="w-full bg-[#4A7C59] text-white text-sm font-bold py-2.5 rounded-md hover:bg-[#3A6247] transition"><i class="fa-solid fa-hand-holding-heart mr-1"></i> Telah Diambil</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="mt-4 mx-4 md:mx-6 mb-6 text-center md:text-right text-xs italic text-[#7B1E1E]/70">Seblak enak, rasa sederhana,<br>selalu jadi cerita.</div>
    </main>
</div>

<script>
    function bukaSidebarMobile() { document.getElementById('sidebarAsli').classList.remove('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.remove('hidden'); }
    function tutupSidebarMobile() { document.getElementById('sidebarAsli').classList.add('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.add('hidden'); }

    function ubahStatus(id, statusBaru, tombol, targetKolomId) {
        const teksAsli = tombol.innerHTML;
        tombol.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Memproses...'; tombol.disabled = true;
        const formData = new FormData(); formData.append('id_pesanan', id); formData.append('status_pesanan', statusBaru);
        fetch('update_status.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const kartu = tombol.closest('.bg-white');
                if (statusBaru === 'Telah Diambil') {
                    kartu.style.transition = 'all 0.3s ease'; kartu.style.opacity = '0'; kartu.style.transform = 'scale(0.9)';
                    setTimeout(() => { kartu.remove(); cekEmptyState(); }, 300);
                } else {
                    const targetContainer = document.getElementById(targetKolomId).querySelector('.space-y-3');
                    if (targetContainer) {
                        kartu.style.transition = 'all 0.3s ease'; kartu.style.opacity = '0'; kartu.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            targetContainer.prepend(kartu);
                            if (statusBaru === 'Diproses') { tombol.outerHTML = `<button type="button" onclick="ubahStatus(${id}, 'Selesai', this, 'kolomSelesai')" class="w-full bg-[#E89B3C] text-white text-sm font-bold py-2.5 rounded-md hover:bg-[#C9832A] transition"><i class="fa-solid fa-check mr-1"></i> Tandai Selesai</button>`; } 
                            else if (statusBaru === 'Selesai') { tombol.outerHTML = `<button type="button" onclick="ubahStatus(${id}, 'Telah Diambil', this, null)" class="w-full bg-[#4A7C59] text-white text-sm font-bold py-2.5 rounded-md hover:bg-[#3A6247] transition"><i class="fa-solid fa-hand-holding-heart mr-1"></i> Telah Diambil</button>`; }
                            setTimeout(() => { kartu.style.opacity = '1'; kartu.style.transform = 'translateY(0)'; cekEmptyState(); }, 50);
                        }, 300);
                    }
                }
            } else { alert('Gagal: ' + data.message); tombol.innerHTML = teksAsli; tombol.disabled = false; }
        })
        .catch(err => { alert('Error: ' + err.message); tombol.innerHTML = teksAsli; tombol.disabled = false; });
    }

    function cekEmptyState() {
        ['kolomAntrian', 'kolomProses', 'kolomSelesai'].forEach(kolomId => {
            const container = document.getElementById(kolomId).querySelector('.space-y-3');
            const kartu = container.querySelectorAll('.bg-white:not(.empty-state)');
            const empty = container.querySelector('.empty-state');
            if (kartu.length === 0) {
                if (!empty) {
                    const pesan = { kolomAntrian: 'Belum ada antrian', kolomProses: 'Belum ada yang diproses', kolomSelesai: 'Belum ada yang selesai' };
                    container.innerHTML = `<div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center empty-state"><p class="text-xs text-gray-400 italic">${pesan[kolomId]}</p></div>`;
                }
            } else { if (empty) empty.remove(); }
        });
    }
</script>
</body>
</html>