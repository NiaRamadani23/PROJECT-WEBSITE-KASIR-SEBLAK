<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { header("Location: index.php"); exit; }

$total_antrian = $conn->query("SELECT COUNT(*) AS total FROM pesanan WHERE status_pesanan = 'Antrian'")->fetch_assoc()['total'];

// ===== LOGIKA FILTER PERIODE =====
$periode = $_GET['periode'] ?? 'minggu';
$tgl_spesifik = $_GET['tgl_spesifik'] ?? date('Y-m-d');

if ($periode === 'minggu') {
    $tgl_mulai = date('Y-m-d', strtotime('monday this week'));
    $tgl_selesai = date('Y-m-d', strtotime('sunday this week'));
    $label_periode = "Minggu Ini";
} elseif ($periode === 'bulan') {
    $tgl_mulai = date('Y-m-01');
    $tgl_selesai = date('Y-m-t');
    $label_periode = "Bulan Ini (" . date('F Y') . ")";
} elseif ($periode === 'tahun') {
    $tgl_mulai = date('Y-01-01');
    $tgl_selesai = date('Y-12-31');
    $label_periode = "Tahun Ini (" . date('Y') . ")";
} elseif ($periode === 'tanggal') {
    $tgl_mulai = $tgl_spesifik;
    $tgl_selesai = $tgl_spesifik;
    $label_periode = "Tanggal " . date('d/m/Y', strtotime($tgl_spesifik));
}

// Query Ringkasan
$sql_summary = "SELECT SUM(total_harga) as total, COUNT(*) as jml FROM pesanan WHERE DATE(tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
$total = $conn->query($sql_summary)->fetch_assoc();

// ===== QUERY GRAFIK DINAMIS =====
$data_grafik = [];
$tipe_grafik = 'line';

if ($periode === 'tanggal') {
    // GRAFIK PER JAM (17:00 - 22:00)
    $tipe_grafik = 'bar';
    $sql_grafik = "SELECT HOUR(tanggal_pesanan) as jam, SUM(total_harga) as total 
                   FROM pesanan 
                   WHERE DATE(tanggal_pesanan) = '$tgl_spesifik' 
                   GROUP BY HOUR(tanggal_pesanan) 
                   ORDER BY jam ASC";
    $grafik = $conn->query($sql_grafik);
    
    $jam_operasional = [];
    for ($i = 17; $i <= 22; $i++) { 
        $jam_operasional[sprintf('%02d:00', $i)] = 0; 
    }
    
    while($g = $grafik->fetch_assoc()) {
        $label_jam = sprintf('%02d:00', $g['jam']);
        if (isset($jam_operasional[$label_jam])) {
            $jam_operasional[$label_jam] = (int)$g['total'];
        }
    }
    
    foreach ($jam_operasional as $jam => $val) { 
        $data_grafik[] = ['label' => $jam, 'total' => $val]; 
    }

} elseif ($periode === 'minggu') {
    $sql_grafik = "SELECT DATE(tanggal_pesanan) as tgl, SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai' GROUP BY DATE(tanggal_pesanan) ORDER BY tgl ASC";
    $grafik = $conn->query($sql_grafik);
    $harian = [];
    while($g = $grafik->fetch_assoc()) { $harian[$g['tgl']] = $g['total']; }
    
    $hari_indo = ['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min'];
    $current = strtotime($tgl_mulai);
    $end = strtotime($tgl_selesai);
    
    while($current <= $end) {
        $tgl_cek = date('Y-m-d', $current);
        $label = $hari_indo[date('D', $current)];
        $data_grafik[] = ['label' => $label, 'total' => (int)($harian[$tgl_cek] ?? 0)];
        $current = strtotime('+1 day', $current);
    }

} elseif ($periode === 'bulan') {
    $sql_grafik = "SELECT WEEK(tanggal_pesanan, 1) - WEEK(DATE_FORMAT(tanggal_pesanan, '%Y-%m-01'), 1) + 1 as minggu, SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai' GROUP BY minggu ORDER BY minggu ASC";
    $grafik = $conn->query($sql_grafik);
    $mingguan = [];
    while($g = $grafik->fetch_assoc()) { $mingguan[$g['minggu']] = $g['total']; }
    
    for ($i = 1; $i <= 5; $i++) { 
        $data_grafik[] = ['label' => 'Minggu ' . $i, 'total' => (int)($mingguan[$i] ?? 0)]; 
    }

} else {
    $sql_grafik = "SELECT MONTH(tanggal_pesanan) as bln, SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai' GROUP BY MONTH(tanggal_pesanan) ORDER BY bln ASC";
    $grafik = $conn->query($sql_grafik);
    $bulanan = [];
    while($g = $grafik->fetch_assoc()) { $bulanan[$g['bln']] = $g['total']; }
    
    $nama_bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    for ($i = 1; $i <= 12; $i++) { 
        $data_grafik[] = ['label' => $nama_bulan[$i-1], 'total' => (int)($bulanan[$i] ?? 0)]; 
    }
}

// Query Transaksi Terbaru
$sql_transaksi = "SELECT p.*, pm.metode_pembayaran FROM pesanan p LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan WHERE DATE(p.tanggal_pesanan) BETWEEN '$tgl_mulai' AND '$tgl_selesai' ORDER BY p.tanggal_pesanan DESC LIMIT 20";
$transaksi = $conn->query($sql_transaksi);

$data_transaksi = [];
while($row = $transaksi->fetch_assoc()) {
    $id_p = $row['id_pesanan'];
    $detail_q = $conn->query("SELECT d.jumlah, d.subtotal, i.nama_isian, i.harga FROM detail_pesanan d JOIN isian i ON d.id_isian = i.id_isian WHERE d.id_pesanan = $id_p");
    $items = [];
    while($d = $detail_q->fetch_assoc()) { $items[] = $d; }
    $row['detail_items'] = $items;
    $data_transaksi[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Catatan Penjualan - Seblak 3 Saudara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inria Serif', serif; }
        .grafik-container { height: 320px; width: 100%; }
        @media (min-width: 768px) { .grafik-container { height: 280px; } }
    </style>
</head>
<body class="bg-[#F5F0E8] min-h-screen">

<!-- HEADER MOBILE -->
<header class="md:hidden sticky top-0 z-40 bg-[#7B1E1E] text-white px-4 py-3 flex items-center justify-between shadow-md">
    <button onclick="bukaSidebarMobile()" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i class="fa-solid fa-bars"></i></button>
    <div class="text-center"><p class="text-xs opacity-80 leading-none">Laporan</p><p class="font-bold text-sm leading-tight">Seblak 3 Saudara</p></div>
    <div class="w-10 h-10"></div>
</header>
<div id="overlaySidebarMobile" onclick="tutupSidebarMobile()" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden"></div>

<div class="flex min-h-screen">
    <aside id="sidebarAsli" class="w-56 bg-[#7B1E1E] text-white flex flex-col p-4 fixed h-full z-50 -translate-x-full md:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col items-center mb-8">
            <svg width="50" height="50" viewBox="0 0 100 100" fill="none">
                <path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z" stroke="white" stroke-width="3" fill="none"/>
                <path d="M50 20 C50 15 48 12 50 8" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <path d="M38 22 C38 17 36 14 38 10" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <path d="M62 22 C62 17 60 14 62 10" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <ellipse cx="50" cy="45" rx="35" ry="5" stroke="white" stroke-width="3" fill="none"/>
            </svg>
            <h2 class="text-lg font-bold mt-2">Seblak</h2><p class="text-sm">3 Saudara</p>
        </div>
        <nav class="space-y-2 text-sm flex-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-cash-register w-4"></i> Kasir</a>
            <a href="antrian.php" class="flex items-center justify-between px-3 py-2 rounded hover:bg-[#5E1616]"><span class="flex items-center gap-3"><i class="fa-solid fa-list w-4"></i> Antrean</span><?php if ($total_antrian > 0): ?><span class="bg-[#C94F4F] text-white text-xs px-2 rounded-full"><?= $total_antrian ?></span><?php endif; ?></a>
            <a href="laporan.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] font-bold"><i class="fa-solid fa-chart-line w-4"></i> Catatan Penjualan</a>
            <a href="kelola_isian.php" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-[#5E1616]"><i class="fa-solid fa-bowl-food w-4"></i> Kelola Isian</a>
        </nav>
        <div class="mt-4 pt-4 border-t border-white/20">
            <div class="flex items-center gap-3 px-3 py-2 mb-2"><div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center"><i class="fa-solid fa-user text-sm"></i></div><div class="text-sm"><p class="font-bold leading-tight"><?= htmlspecialchars($_SESSION['nama_pengguna']) ?></p><p class="text-[10px] text-white/70 leading-tight">Admin</p></div></div>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 rounded bg-[#5E1616] text-sm font-semibold hover:bg-[#4A1111] transition"><i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Logout</a>
        </div>
    </aside>

    <div class="flex-1 ml-0 md:ml-56 pb-6">
        <div class="px-4 md:px-6 pt-4 md:pt-6 pb-2">
            <div class="flex flex-row justify-between items-start md:items-end gap-3 mb-6">
                <div>
                    <span class="text-[10px] md:text-xs font-bold text-[#7B1E1E] tracking-wide uppercase">Laporan</span>
                    <h1 class="text-xl md:text-2xl font-bold text-[#1A1A1A] mt-1">Catatan Penjualan</h1>
                    <p class="text-[10px] md:text-sm text-[#4A4A4A] mt-1">Pantau pendapatan dan riwayat transaksi.</p>
                </div>
                <a href="export_excel.php?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>" class="flex items-center justify-center gap-1.5 md:gap-2 bg-[#4A7C59] text-white px-3 py-1.5 md:px-5 md:py-2.5 rounded-lg md:rounded-xl text-[10px] md:text-sm font-bold hover:bg-[#3A6247] transition shadow-sm shrink-0 mt-1 md:mt-0"><i class="fa-solid fa-file-excel"></i> <span class="hidden sm:inline">Export Excel</span><span class="sm:hidden">Excel</span></a>
            </div>

            <div class="bg-white p-2 rounded-2xl border border-[#EAD9CF] shadow-sm flex flex-col md:flex-row items-center justify-between gap-3 overflow-hidden">
                <div class="flex gap-1 md:gap-2 w-full overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
                    <a href="?periode=minggu" class="<?= $periode == 'minggu' ? 'bg-[#7B1E1E] text-white shadow-md' : 'bg-transparent text-gray-500 hover:bg-gray-100' ?> flex-1 text-center px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition whitespace-nowrap">Minggu Ini</a>
                    <a href="?periode=bulan" class="<?= $periode == 'bulan' ? 'bg-[#7B1E1E] text-white shadow-md' : 'bg-transparent text-gray-500 hover:bg-gray-100' ?> flex-1 text-center px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition whitespace-nowrap">Bulan Ini</a>
                    <a href="?periode=tahun" class="<?= $periode == 'tahun' ? 'bg-[#7B1E1E] text-white shadow-md' : 'bg-transparent text-gray-500 hover:bg-gray-100' ?> flex-1 text-center px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition whitespace-nowrap">Tahun Ini</a>
                    <a href="?periode=tanggal" class="<?= $periode == 'tanggal' ? 'bg-[#7B1E1E] text-white shadow-md' : 'bg-transparent text-gray-500 hover:bg-gray-100' ?> flex-1 text-center px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold transition whitespace-nowrap"><i class="fa-regular fa-calendar-days mr-1"></i> Pilih Tanggal</a>
                </div>
                <?php if ($periode == 'tanggal'): ?>
                <form method="GET" class="w-full md:w-auto flex items-center gap-2 border-t md:border-t-0 md:border-l border-gray-200 pt-3 md:pt-0 md:pl-3">
                    <input type="hidden" name="periode" value="tanggal">
                    <input type="date" name="tgl_spesifik" value="<?= $tgl_spesifik ?>" onchange="this.form.submit()" class="w-full md:w-auto border border-gray-200 bg-gray-50 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:border-[#7B1E1E]">
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="px-4 md:px-6 pb-8 space-y-4 md:space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-[#EAD9CF] relative overflow-hidden group"><div class="absolute -right-4 -top-4 w-16 h-16 bg-[#7B1E1E]/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div><p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Total Penjualan (<?= $label_periode ?>)</p><p class="text-2xl md:text-3xl font-bold text-[#7B1E1E]">Rp <?= number_format($total['total'] ?? 0, 0, ',', '.') ?></p></div>
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-[#EAD9CF] relative overflow-hidden group"><div class="absolute -right-4 -top-4 w-16 h-16 bg-gray-500/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div><p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jumlah Transaksi (<?= $label_periode ?>)</p><p class="text-2xl md:text-3xl font-bold text-[#1A1A1A]"><?= $total['jml'] ?> <span class="text-sm font-normal text-gray-400">pesanan</span></p></div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-[#EAD9CF]">
                <h3 class="font-bold text-sm md:text-lg text-[#1A1A1A] mb-4">Grafik Penjualan (<?= $label_periode ?>)</h3>
                <div class="grafik-container"><canvas id="grafikChart"></canvas></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-[#EAD9CF] overflow-hidden">
                <div class="p-5 border-b border-[#EAD9CF] flex justify-between items-center"><h3 class="font-bold text-sm md:text-lg text-[#1A1A1A]">Transaksi Terbaru (<?= $label_periode ?>)</h3></div>
                <div class="block md:hidden divide-y divide-[#EAD9CF]">
                    <?php if (empty($data_transaksi)): ?><div class="p-6 text-center text-gray-400 italic text-sm">Belum ada transaksi.</div>
                    <?php else: foreach($data_transaksi as $r): ?>
                    <div class="p-4 hover:bg-[#FDF6F0] transition">
                        <!-- GANTI ID JADI NOMOR ANTREAN -->
                        <div class="flex justify-between items-start mb-1"><span class="text-[#7B1E1E] font-bold bg-[#7B1E1E]/10 px-2 py-0.5 rounded text-xs">#<?= str_pad($r['nomor_antrean'] ?? $r['id_pesanan'], 2, '0', STR_PAD_LEFT) ?></span><span class="text-[10px] text-gray-500"><?= date('d/m H:i', strtotime($r['tanggal_pesanan'])) ?></span></div>
                        <p class="text-sm font-bold text-[#1A1A1A] mt-2"><?= htmlspecialchars($r['nama_pembeli']) ?></p>
                        <p class="text-[#7B1E1E] font-bold text-sm mb-3">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></p>
                        <div class="flex justify-between items-center"><?php $status = $r['status_pesanan']; $warna = 'bg-gray-100 text-gray-700'; if ($status == 'Antrian') $warna = 'bg-red-100 text-red-700'; elseif ($status == 'Diproses') $warna = 'bg-orange-100 text-orange-700'; elseif ($status == 'Selesai') $warna = 'bg-green-100 text-green-700'; elseif ($status == 'Telah Diambil') $warna = 'bg-blue-100 text-blue-700'; ?><span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $warna ?>"><?= $status ?></span><button onclick='bukaNota(<?= json_encode($r) ?>)' class="text-xs text-[#7B1E1E] font-bold hover:underline flex items-center gap-1"><i class="fa-solid fa-receipt"></i> Nota</button></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#FDF6F0] text-[#4A4A4A]"><tr class="text-left"><th class="py-3 px-5 font-bold border-b border-[#EAD9CF]">ID</th><th class="py-3 px-5 font-bold border-b border-[#EAD9CF]">Pembeli</th><th class="py-3 px-5 font-bold border-b border-[#EAD9CF]">Total</th><th class="py-3 px-5 font-bold border-b border-[#EAD9CF]">Status</th><th class="py-3 px-5 font-bold border-b border-[#EAD9CF]">Tanggal</th><th class="py-3 px-5 font-bold border-b border-[#EAD9CF] text-center">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-[#EAD9CF]">
                            <?php if (empty($data_transaksi)): ?><tr><td colspan="6" class="py-6 text-center text-gray-400 italic">Belum ada transaksi.</td></tr>
                            <?php else: foreach($data_transaksi as $r): ?>
                            <tr class="hover:bg-[#FDF6F0] transition">
                                <!-- GANTI ID JADI NOMOR ANTREAN -->
                                <td class="py-3 px-5 text-[#7B1E1E] font-bold">#<?= str_pad($r['nomor_antrean'] ?? $r['id_pesanan'], 2, '0', STR_PAD_LEFT) ?></td>
                                <td class="py-3 px-5 text-[#1A1A1A] font-semibold"><?= htmlspecialchars($r['nama_pembeli']) ?></td>
                                <td class="py-3 px-5 text-[#7B1E1E] font-bold">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                <td class="py-3 px-5"><?php $status = $r['status_pesanan']; $warna = 'bg-gray-100 text-gray-700'; if ($status == 'Antrian') $warna = 'bg-red-100 text-red-700'; elseif ($status == 'Diproses') $warna = 'bg-orange-100 text-orange-700'; elseif ($status == 'Selesai') $warna = 'bg-green-100 text-green-700'; elseif ($status == 'Telah Diambil') $warna = 'bg-blue-100 text-blue-700'; ?><span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $warna ?>"><?= $status ?></span></td>
                                <td class="py-3 px-5 text-gray-500 text-xs"><?= date('d/m/Y H:i', strtotime($r['tanggal_pesanan'])) ?></td>
                                <td class="py-3 px-5 text-center"><button onclick='bukaNota(<?= json_encode($r) ?>)' class="text-xs text-[#7B1E1E] font-bold hover:underline"><i class="fa-solid fa-receipt"></i> Nota</button></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NOTA -->
<div id="modalNota" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[80] p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl p-6 text-center max-h-[90vh] overflow-y-auto">
        <div class="w-16 h-16 bg-[#7B1E1E]/10 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-receipt text-3xl text-[#7B1E1E]"></i></div>
        <h3 class="font-bold text-xl text-[#1A1A1A] mb-1">Nota Pesanan</h3>
        <p class="text-sm text-gray-500 mb-4">Terima kasih telah memesan!</p>
        <div class="bg-[#FDF6F0] rounded-xl p-4 mb-4 text-left text-sm space-y-2 border border-[#EAD9CF]">
            <div class="flex justify-between pb-2 border-b border-[#EAD9CF] mb-2"><span class="text-gray-500">Nomor Antrean</span><span class="font-bold text-[#7B1E1E]" id="notaId">#00</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Nama Pembeli</span><span class="font-bold text-[#1A1A1A]" id="notaNama">-</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><span class="font-bold text-[#1A1A1A]" id="notaTgl">-</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Level/Kuah/Sajian</span><span class="font-bold text-[#1A1A1A]" id="notaDetail">-</span></div>
            <div class="border-t border-[#EAD9CF] pt-2 mt-2"><p class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Rincian Pesanan</p><div id="notaItems" class="space-y-2"></div></div>
            <div class="border-t border-[#EAD9CF] pt-2 mt-2 flex justify-between items-center"><span class="text-gray-500 font-bold">Total Bayar</span><span class="font-bold text-xl text-[#D94A3D]" id="notaTotal">Rp 0</span></div>
            <div class="flex justify-between text-xs text-gray-500 mt-2"><span>Metode: <span id="notaMetode" class="font-bold">-</span></span><span>Status: <span id="notaStatus" class="font-bold">-</span></span></div>
        </div>
        <button onclick="tutupNota()" class="w-full bg-[#7B1E1E] text-white font-bold py-3 rounded-xl hover:bg-[#5E1616] transition">Tutup</button>
    </div>
</div>

<script>
    function bukaSidebarMobile() { document.getElementById('sidebarAsli').classList.remove('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.remove('hidden'); }
    function tutupSidebarMobile() { document.getElementById('sidebarAsli').classList.add('-translate-x-full'); document.getElementById('overlaySidebarMobile').classList.add('hidden'); }

    function bukaNota(data) {
        // PAKAI NOMOR ANTREAN
        document.getElementById('notaId').innerText = '#' + String(data.nomor_antrean || data.id_pesanan).padStart(2, '0');
        document.getElementById('notaNama').innerText = data.nama_pembeli;
        document.getElementById('notaTgl').innerText = new Date(data.tanggal_pesanan).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        document.getElementById('notaDetail').innerText = `Lvl ${data.level_pedas} · ${data.jenis_kuah} · ${data.jenis_penyajian}`;
        document.getElementById('notaTotal').innerText = 'Rp ' + parseInt(data.total_harga).toLocaleString('id-ID');
        document.getElementById('notaMetode').innerText = data.metode_pembayaran || 'Tunai';
        document.getElementById('notaStatus').innerText = data.status_pesanan;
        let htmlItems = '';
        if (data.detail_items && data.detail_items.length > 0) { data.detail_items.forEach(item => { htmlItems += `<div class="flex justify-between items-center text-sm mb-1"><div><p class="font-bold text-[#1A1A1A]">${item.nama_isian}</p><p class="text-[10px] text-gray-500">${item.jumlah} x Rp ${parseInt(item.harga).toLocaleString('id-ID')}</p></div><span class="font-bold text-[#7B1E1E]">Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</span></div>`; }); } else { htmlItems = '<p class="text-xs text-gray-400 italic">Rincian tidak ditemukan.</p>'; }
        document.getElementById('notaItems').innerHTML = htmlItems;
        document.getElementById('modalNota').classList.remove('hidden');
    }
    function tutupNota() { document.getElementById('modalNota').classList.add('hidden'); }

    const ctx = document.getElementById('grafikChart');
    const tipeGrafik = '<?= $tipe_grafik ?>';
    
    new Chart(ctx, {
        type: tipeGrafik,
        data: {
            labels: <?= json_encode(array_column($data_grafik, 'label')) ?>,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: <?= json_encode(array_column($data_grafik, 'total')) ?>,
                borderColor: '#7B1E1E',
                backgroundColor: tipeGrafik === 'bar' ? 'rgba(123, 30, 30, 0.8)' : 'rgba(123, 30, 30, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#7B1E1E',
                pointRadius: 4,
                tension: 0.4,
                fill: tipeGrafik === 'line' ? true : false,
                borderRadius: tipeGrafik === 'bar' ? 6 : 0,
                barPercentage: 0.6
            }]
        },
        options: { 
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        stepSize: 10000,
                        callback: function(value) { 
                            if(value === 0) return '0';
                            let valInRb = value / 1000;
                            return Number.isInteger(valInRb) ? valInRb + 'rb' : value.toLocaleString('id-ID'); 
                        } 
                    } 
                }, 
                x: { grid: { display: false } } 
            }
        }
    });
</script>
</body>
</html>