<?php
session_start();
require 'config/database.php';

if (isset($_SESSION['id_penjual'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $nama_pengguna = trim($_POST['nama_pengguna']);
    $kata_sandi    = $_POST['kata_sandi'];

    if ($nama_pengguna === "" || $kata_sandi === "") {
        $error = "Nama pengguna dan kata sandi wajib diisi!";
    } else {
        $stmt = $conn->prepare("SELECT id_penjual, nama_pengguna, kata_sandi FROM penjual WHERE nama_pengguna = ?");
        $stmt->bind_param("s", $nama_pengguna);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($kata_sandi, $row['kata_sandi'])) {
                $_SESSION['id_penjual']    = $row['id_penjual'];
                $_SESSION['nama_pengguna'] = $row['nama_pengguna'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Kata sandi salah!";
            }
        } else {
            $error = "Nama pengguna tidak ditemukan!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Masuk Penjual - Seblak 3 Saudara</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * { font-family: 'Inria Serif', serif; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes floaty {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-12px) rotate(3deg); }
        }
        @keyframes floatySlow {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-18px) rotate(-4deg); }
        }
        @keyframes shine {
            0%   { transform: translateX(-120%) skewX(-20deg); }
            60%  { transform: translateX(220%) skewX(-20deg); }
            100% { transform: translateX(220%) skewX(-20deg); }
        }
        @keyframes pulseRing {
            0%   { box-shadow: 0 0 0 0 rgba(123, 30, 30, 0.35); }
            70%  { box-shadow: 0 0 0 14px rgba(123, 30, 30, 0); }
            100% { box-shadow: 0 0 0 0 rgba(123, 30, 30, 0); }
        }
        @keyframes steam {
            0%   { opacity: 0; transform: translateY(0) scale(1); }
            30%  { opacity: .7; }
            100% { opacity: 0; transform: translateY(-28px) scale(1.4); }
        }
        @keyframes kenburns {
            0%   { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.14) translate(-1.5%, -1.5%); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        @keyframes reveal {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim-fade-up { animation: fadeUp .8s cubic-bezier(.22,.9,.35,1) both; }
        .anim-fade-in { animation: fadeIn 1s ease both; }
        .delay-1 { animation-delay: .1s; }
        .delay-2 { animation-delay: .25s; }
        .delay-3 { animation-delay: .4s; }
        .delay-4 { animation-delay: .55s; }
        .delay-5 { animation-delay: .7s; }

        .floaty      { animation: floaty 6s ease-in-out infinite; }
        .floaty-slow { animation: floatySlow 9s ease-in-out infinite; }

        .btn-shine { position: relative; overflow: hidden; }
        .btn-shine::after {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 40%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent);
            transform: translateX(-120%) skewX(-20deg);
        }
        .btn-shine:hover::after { animation: shine 1s ease; }
        .btn-shine:hover { animation: pulseRing 1.4s ease-out infinite; }

        .kenburns { animation: kenburns 18s ease-in-out infinite alternate; }

        .input-wrap:focus-within {
            border-color: #7B1E1E;
            box-shadow: 0 0 0 4px rgba(123, 30, 30, .10);
            transform: translateY(-1px);
        }
        .input-wrap { transition: all .25s ease; }

        .steam span {
            position: absolute;
            width: 6px; height: 6px;
            background: rgba(255,255,255,.75);
            border-radius: 9999px;
            filter: blur(1px);
            animation: steam 2.6s ease-out infinite;
        }
        .steam span:nth-child(1) { left: 30%; animation-delay: 0s; }
        .steam span:nth-child(2) { left: 50%; animation-delay: .6s; }
        .steam span:nth-child(3) { left: 68%; animation-delay: 1.2s; }

        .shake { animation: shake .45s ease; }

        .dots-bg {
            background-image: radial-gradient(rgba(123,30,30,.14) 1.5px, transparent 1.5px);
            background-size: 18px 18px;
        }

        /* Kartu form mobile (naik ke atas gambar) */
        .mobile-card {
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
            margin-top: -28px;
            position: relative;
            z-index: 10;
            box-shadow: 0 -12px 30px -12px rgba(0,0,0,.25);
            animation: reveal 0.9s cubic-bezier(.22,.9,.35,1) both;
            animation-delay: .25s;
        }

        /* Hero mobile tinggi */
        .hero-mobile { height: 46vh; min-height: 300px; max-height: 460px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }

        @media (max-width: 380px) {
            .mobile-compact { padding-left: 1rem; padding-right: 1rem; }
            .mobile-title { font-size: 1.55rem; line-height: 1.2; }
        }
    </style>
</head>
<body class="bg-[#F5F0E8] min-h-screen overflow-x-hidden">

    <!-- ============================================================
         DESKTOP (> md): split 2 kolom
         MOBILE (< md): hero gambar di atas + kartu form naik
    ============================================================ -->

    <!-- ================= HERO (mobile + desktop kiri) ================= -->
    <!-- Di desktop, hero ini hanya setengah kiri; di mobile full width atas -->
    <div class="md:hidden relative hero-mobile overflow-hidden">
        <img src="assets/gambar%20seblak%20full%20toping.jpg"
             alt="Seblak 3 Saudara full topping"
             class="absolute inset-0 w-full h-full object-cover kenburns">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/55 via-black/40 to-[#F5F0E8]/95"></div>

        <!-- Aksen lingkaran -->
        <div class="absolute -top-16 -left-16 w-56 h-56 rounded-full bg-[#7B1E1E]/50 blur-3xl floaty-slow"></div>
        <div class="absolute bottom-4 right-0 w-48 h-48 rounded-full bg-[#C97B4A]/50 blur-3xl floaty"></div>

        <!-- Konten hero -->
        <div class="relative h-full flex flex-col items-center justify-center text-white text-center px-6 pt-6">
            <div class="relative mb-3 anim-fade-in floaty">
                <svg width="68" height="68" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z"
                          stroke="white" stroke-width="3" fill="none" stroke-linejoin="round"/>
                    <ellipse cx="50" cy="45" rx="35" ry="5" stroke="white" stroke-width="3" fill="none"/>
                </svg>
                <div class="steam absolute -top-1 left-1/2 -translate-x-1/2 w-9 h-6">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <h1 class="text-2xl font-bold anim-fade-up delay-1 tracking-wide drop-shadow">Seblak</h1>
            <h2 class="text-3xl font-bold mb-2 anim-fade-up delay-2 tracking-wide drop-shadow">3 Saudara</h2>
            <p class="italic text-sm text-white/90 anim-fade-up delay-3">
                Rasa pedas, selalu jadi cerita.
            </p>
        </div>
    </div>

    <!-- ================= WRAPPER UTAMA ================= -->
    <div class="md:grid md:grid-cols-2 md:min-h-screen">

        <!-- ================= KIRI DESKTOP: gambar penuh ================= -->
        <div class="relative hidden md:block overflow-hidden">
            <img src="assets/gambar%20seblak%20full%20toping.jpg"
                 alt="Seblak 3 Saudara full topping"
                 class="absolute inset-0 w-full h-full object-cover kenburns">

            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/45 to-black/80"></div>

            <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-[#7B1E1E]/40 blur-3xl floaty-slow"></div>
            <div class="absolute bottom-10 right-0 w-64 h-64 rounded-full bg-[#C97B4A]/40 blur-3xl floaty"></div>

            <div class="relative h-full flex flex-col items-center justify-center text-white px-10 text-center">
                <div class="relative mb-5 anim-fade-in floaty">
                    <svg width="96" height="96" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 45 H85 C85 65 70 80 50 80 C30 80 15 65 15 45 Z"
                              stroke="white" stroke-width="3" fill="none" stroke-linejoin="round"/>
                        <ellipse cx="50" cy="45" rx="35" ry="5" stroke="white" stroke-width="3" fill="none"/>
                    </svg>
                    <div class="steam absolute -top-2 left-1/2 -translate-x-1/2 w-10 h-8">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <h1 class="text-4xl font-bold mb-1 anim-fade-up delay-1 tracking-wide">Seblak</h1>
                <h2 class="text-5xl font-bold mb-5 anim-fade-up delay-2 tracking-wide">3 Saudara</h2>
                <div class="w-16 h-[3px] bg-white/70 rounded-full mb-5 anim-fade-in delay-3"></div>
                <p class="italic text-2xl text-white/90 leading-snug anim-fade-up delay-3">
                    Rasa pedas,<br>selalu jadi cerita.
                </p>
                <div class="mt-8 flex gap-2 anim-fade-up delay-4">
                    <span class="text-xs px-3 py-1.5 rounded-full bg-white/15 border border-white/25 backdrop-blur">🌶️ Pedas mantap</span>
                    <span class="text-xs px-3 py-1.5 rounded-full bg-white/15 border border-white/25 backdrop-blur">🍜 Fresh tiap hari</span>
                </div>
            </div>
        </div>

        <!-- ================= KANAN: FORM LOGIN ================= -->
        <div class="relative flex items-start md:items-center justify-center bg-[#F5F0E8]">
            <!-- dekorasi -->
            <div class="absolute inset-0 dots-bg opacity-60 pointer-events-none"></div>

            <!-- Di mobile, ini kartu yang naik ke atas gambar -->
            <div class="relative w-full md:max-w-md bg-[#F5F0E8] mobile-card md:!mt-0 md:!rounded-none md:!shadow-none">
                <div class="w-full max-w-md mx-auto px-5 sm:px-8 pt-8 sm:pt-10 pb-10 mobile-compact">

                    <!-- Badge -->
                    <div class="mb-5 anim-fade-up">
                        <span class="inline-flex items-center gap-2 bg-[#7B1E1E] text-white text-xs font-semibold px-4 py-1.5 rounded-full shadow-lg shadow-[#7B1E1E]/20">
                            <i class="fa-solid fa-store text-[10px]"></i>
                            Khusus penjual
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-[#1A1A1A] mb-2 anim-fade-up delay-1 mobile-title">Masuk Penjual</h1>
                    <p class="text-[#4A4A4A] text-sm mb-7 leading-relaxed anim-fade-up delay-1">
                        Masuk untuk memakai kasir dan melihat catatan penjualan.
                    </p>

                    <?php if ($error): ?>
                        <div class="shake bg-red-100 border border-red-300 text-red-700 text-sm p-3 rounded-xl mb-5 flex items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="anim-fade-up delay-2">
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-[#6B6B6B] mb-1.5 ml-1">Nama pengguna</label>
                            <div class="input-wrap flex items-center bg-white border border-gray-200 rounded-xl px-4 py-3">
                                <i class="fa-regular fa-user text-gray-400 mr-3"></i>
                                <input type="text" name="nama_pengguna"
                                       placeholder="Masukkan nama pengguna"
                                       class="w-full outline-none text-sm text-gray-700 placeholder-gray-400 bg-transparent"
                                       required>
                            </div>
                        </div>

                        <div class="mb-7">
                            <label class="block text-xs font-semibold text-[#6B6B6B] mb-1.5 ml-1">Kata sandi</label>
                            <div class="input-wrap flex items-center bg-white border border-gray-200 rounded-xl px-4 py-3">
                                <i class="fa-solid fa-lock text-gray-400 mr-3"></i>
                                <input type="password" name="kata_sandi" id="kataSandi"
                                       placeholder="Masukkan kata sandi"
                                       class="w-full outline-none text-sm text-gray-700 placeholder-gray-400 bg-transparent"
                                       required>
                                <button type="button" onclick="togglePassword()"
                                        class="text-gray-400 ml-2 focus:outline-none hover:text-[#7B1E1E] transition"
                                        aria-label="Tampilkan kata sandi">
                                    <i class="fa-regular fa-eye" id="ikonMata"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="login"
                                class="btn-shine w-full bg-[#7B1E1E] hover:bg-[#5E1616] text-white font-semibold py-3 rounded-xl transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 shadow-lg shadow-[#7B1E1E]/25 flex items-center justify-center gap-2">
                            <span>Masuk</span>
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </button>
                    </form>

                    <p class="text-center text-xs text-[#8A8A8A] mt-7 anim-fade-in delay-4">
                        © <?= date('Y') ?> Seblak 3 Saudara
                    </p>
                </div>
            </div>

            <!-- Dekorasi bawah (desktop saja) -->
            <div class="hidden md:block absolute bottom-4 right-6 opacity-70 floaty-slow pointer-events-none">
                <svg width="120" height="100" viewBox="0 0 120 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M70 90 C70 60 90 40 110 30" stroke="#7B1E1E" stroke-width="1.5" fill="none"/>
                    <path d="M85 55 C85 50 90 45 95 45 C95 50 90 55 85 55 Z" fill="#7B1E1E" opacity="0.5"/>
                    <path d="M90 65 C90 60 95 55 100 55 C100 60 95 65 90 65 Z" fill="#7B1E1E" opacity="0.5"/>
                    <path d="M78 70 C75 68 72 68 70 70 C72 72 75 72 78 70 Z" fill="#7B1E1E" opacity="0.5"/>
                    <path d="M95 45 C98 40 103 38 108 40 C105 45 100 47 95 45 Z" fill="#7B1E1E" opacity="0.5"/>
                    <path d="M40 85 C50 70 60 65 75 70 C70 85 55 90 40 85 Z" stroke="#7B1E1E" stroke-width="1.5" fill="none"/>
                    <path d="M40 85 L35 88" stroke="#7B1E1E" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M60 20 L62 25 L67 25 L63 28 L65 33 L60 30 L55 33 L57 28 L53 25 L58 25 Z" fill="#7B1E1E" opacity="0.6"/>
                    <circle cx="75" cy="15" r="1.5" fill="#7B1E1E" opacity="0.6"/>
                </svg>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('kataSandi');
            const icon  = document.getElementById('ikonMata');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>