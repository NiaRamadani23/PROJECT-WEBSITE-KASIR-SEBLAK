-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Okt 2026 pada 17.47
-- Versi server: 11.8.3-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_seblak`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_isian` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_isian`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(11, 5, 15, 6, 2000.00, 12000.00),
(12, 6, 33, 7, 2000.00, 14000.00),
(13, 7, 8, 8, 1000.00, 8000.00),
(14, 7, 10, 1, 2000.00, 2000.00),
(15, 7, 11, 1, 2000.00, 2000.00),
(16, 8, 12, 8, 2000.00, 16000.00),
(17, 9, 4, 4, 1000.00, 4000.00),
(18, 9, 7, 3, 1000.00, 3000.00),
(19, 9, 11, 1, 2000.00, 2000.00),
(20, 9, 32, 1, 2000.00, 2000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `isian`
--

CREATE TABLE `isian` (
  `id_isian` int(11) NOT NULL,
  `nama_isian` varchar(100) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `status_isian` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `isian`
--

INSERT INTO `isian` (`id_isian`, `nama_isian`, `gambar`, `harga`, `stok`, `status_isian`) VALUES
(1, 'Kerupuk Bawang', NULL, 1000.00, 100, 'Tersedia'),
(2, 'Kerupuk Udang', NULL, 1000.00, 100, 'Tersedia'),
(3, 'Kerupuk Mawar Putih', NULL, 1000.00, 100, 'Tersedia'),
(4, 'Kerupuk Mawar Warna Warni', NULL, 1000.00, 96, 'Tersedia'),
(5, 'Makaroni', NULL, 1000.00, 100, 'Tersedia'),
(6, 'Makaroni Spiral', NULL, 1000.00, 100, 'Tersedia'),
(7, 'Kerupuk Rantai', NULL, 1000.00, 97, 'Tersedia'),
(8, 'Cikuwa', NULL, 1000.00, 92, 'Tersedia'),
(9, 'Fishball', NULL, 2000.00, 100, 'Tersedia'),
(10, 'Salmonball', NULL, 2000.00, 99, 'Tersedia'),
(11, 'Odeng', NULL, 2000.00, 98, 'Tersedia'),
(12, 'Crabb Stick', NULL, 2000.00, 92, 'Tersedia'),
(13, 'Otak-Otak', NULL, 1000.00, 100, 'Tersedia'),
(14, 'Scallob', NULL, 1000.00, 100, 'Tersedia'),
(15, 'Fish Roll', NULL, 2000.00, 94, 'Tersedia'),
(16, 'Twister', NULL, 2000.00, 100, 'Tersedia'),
(17, 'Tempura', NULL, 1000.00, 100, 'Tersedia'),
(18, 'Telur', NULL, 3000.00, 50, 'Tersedia'),
(19, 'Cumi', NULL, 1000.00, 100, 'Tersedia'),
(20, 'Tofu', NULL, 1000.00, 100, 'Tersedia'),
(21, 'Bihun', NULL, 1000.00, 100, 'Tersedia'),
(22, 'Bakso', NULL, 1000.00, 80, 'Tersedia'),
(23, 'Ceker', NULL, 2000.00, 60, 'Tersedia'),
(24, 'Sosis Kecil', NULL, 2000.00, 70, 'Tersedia'),
(25, 'Sosis Merah', NULL, 2000.00, 100, 'Tersedia'),
(26, 'Sosis Besar', NULL, 2000.00, 100, 'Tersedia'),
(27, 'Cuanki Lidah', NULL, 2000.00, 100, 'Tersedia'),
(28, 'Pilus Cikur', NULL, 1000.00, 100, 'Tersedia'),
(29, 'Siomay Kering', NULL, 1000.00, 100, 'Tersedia'),
(30, 'Jamur Enoki', NULL, 2000.00, 100, 'Tersedia'),
(31, 'Pokcoy', NULL, 2000.00, 100, 'Tersedia'),
(32, 'Dumpling Ayam', NULL, 2000.00, 99, 'Tersedia'),
(33, 'Dumpling Keju', NULL, 2000.00, 93, 'Tersedia'),
(34, 'Mie Kuning', NULL, 1000.00, 100, 'Tersedia'),
(35, 'Sawi Putih', NULL, 2000.00, 100, 'Tersedia'),
(36, 'Sawi Hijau', NULL, 2000.00, 40, 'Tersedia');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `metode_pembayaran` varchar(20) DEFAULT NULL,
  `uang_diterima` decimal(12,2) DEFAULT 0.00,
  `kembalian` decimal(12,2) DEFAULT 0.00,
  `status_pembayaran` varchar(30) NOT NULL,
  `tanggal_pembayaran` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pesanan`, `metode_pembayaran`, `uang_diterima`, `kembalian`, `status_pembayaran`, `tanggal_pembayaran`) VALUES
(1, 5, 'Tunai', 20000.00, 8000.00, 'Lunas', '2026-10-09 15:37:42'),
(2, 6, 'QRIS', 14000.00, 0.00, 'Lunas', '2026-10-09 15:44:21'),
(3, 7, 'Tunai', 0.00, 0.00, 'Belum Lunas', '2026-10-27 11:40:41'),
(4, 8, 'QRIS', 16000.00, 0.00, 'Lunas', '2026-10-27 11:43:16'),
(5, 9, 'QRIS', 11000.00, 0.00, 'Lunas', '2026-10-27 12:31:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjual`
--

CREATE TABLE `penjual` (
  `id_penjual` int(11) NOT NULL,
  `nama_pengguna` varchar(100) NOT NULL,
  `kata_sandi` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penjual`
--

INSERT INTO `penjual` (`id_penjual`, `nama_pengguna`, `kata_sandi`) VALUES
(1, 'admin', '$2y$10$DdaLpudbEk2BRoA6fjGtPu5b0INyJ0eWHjNi0uN41b3yJizLbVKJ6');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_penjual` int(11) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `level_pedas` int(11) NOT NULL,
  `jenis_kuah` varchar(20) NOT NULL,
  `jenis_penyajian` varchar(30) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_pesanan` varchar(30) NOT NULL,
  `tanggal_pesanan` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_penjual`, `nama_pembeli`, `level_pedas`, `jenis_kuah`, `jenis_penyajian`, `total_harga`, `status_pesanan`, `tanggal_pesanan`) VALUES
(5, 1, 'adib', 3, 'Nyemek', 'Dibungkus', 12000.00, 'Telah Diambil', '2026-10-09 15:37:42'),
(6, 1, 'adib', 1, 'Nyemek', 'Dibungkus', 14000.00, 'Telah Diambil', '2026-10-09 15:44:21'),
(7, 1, 'balbal', 1, 'Kuah', 'Dibungkus', 12000.00, 'Telah Diambil', '2026-10-27 11:40:41'),
(8, 1, 'shanum', 5, 'Nyemek', 'Dibungkus', 16000.00, 'Telah Diambil', '2026-10-27 11:43:16'),
(9, 1, 'cinta', 0, 'Nyemek', 'Dibungkus', 11000.00, 'Antrian', '2026-10-27 12:31:18');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_isian` (`id_isian`);

--
-- Indeks untuk tabel `isian`
--
ALTER TABLE `isian`
  ADD PRIMARY KEY (`id_isian`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `penjual`
--
ALTER TABLE `penjual`
  ADD PRIMARY KEY (`id_penjual`),
  ADD UNIQUE KEY `nama_pengguna` (`nama_pengguna`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_penjual` (`id_penjual`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `isian`
--
ALTER TABLE `isian`
  MODIFY `id_isian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `penjual`
--
ALTER TABLE `penjual`
  MODIFY `id_penjual` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_isian`) REFERENCES `isian` (`id_isian`);

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_penjual`) REFERENCES `penjual` (`id_penjual`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
