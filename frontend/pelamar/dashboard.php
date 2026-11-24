<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'pelamar'
) {
    header("Location: ../login.php");
    exit;
}

require '../../backend/connection.php';

$applicant_id = (int) $_SESSION['user_id'];

// 1. Ambil data profil pelamar yang sedang login
$stmtUser = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmtUser->bind_param("i", $applicant_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$namaUser  = $user['name'] ?? 'Pelamar';
$emailUser = $user['email'] ?? '';

// Buat inisial nama untuk avatar topbar (maksimal 2 huruf)
$kataNama = preg_split('/\s+/', trim($namaUser));
$inisial  = '';
foreach (array_slice($kataNama, 0, 2) as $kata) {
    $inisial .= mb_strtoupper(mb_substr($kata, 0, 1));
}
if ($inisial === '') {
    $inisial = 'PL';
}

// 2. Hitung ringkasan statistik lamaran milik pelamar
// - Total Lamaran: seluruh lamaran yang pernah di-input pelamar
// - Sedang Diproses: status 'submitted', 'administrative_review', atau 'interview' (belum keputusan akhir)
// - Diterima: status 'accepted' (sudah di-acc admin)
$sqlStats = "
    SELECT 
        COUNT(*) AS total_lamaran,
        COALESCE(SUM(CASE WHEN status IN ('submitted', 'administrative_review', 'interview') THEN 1 ELSE 0 END), 0) AS sedang_diproses,
        COALESCE(SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END), 0) AS diterima
    FROM applications
    WHERE applicant_id = ?
";
$stmtStats = $conn->prepare($sqlStats);
$stmtStats->bind_param("i", $applicant_id);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
$stmtStats->close();

$totalLamaran   = (int) ($stats['total_lamaran'] ?? 0);
$sedangDiproses = (int) ($stats['sedang_diproses'] ?? 0);
$diterima       = (int) ($stats['diterima'] ?? 0);

// 3. Ambil 2 riwayat lamaran terbaru (sisanya ditampilkan di riwayat.php)
$sqlLatest = "
    SELECT 
        a.id,
        a.applied_at,
        a.status,
        j.title AS job_title,
        j.location AS job_location
    FROM applications a
    INNER JOIN jobs j ON a.job_id = j.id
    WHERE a.applicant_id = ?
    ORDER BY a.applied_at DESC, a.id DESC
    LIMIT 2
";
$stmtLatest = $conn->prepare($sqlLatest);
$stmtLatest->bind_param("i", $applicant_id);
$stmtLatest->execute();
$latestApplications = $stmtLatest->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtLatest->close();

// Helper format tanggal singkat Indonesia (contoh: 1 Jun 2025)
function formatTanggalIndo(string $datetime): string {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $ts = strtotime($datetime);
    if (!$ts) {
        return '-';
    }
    $tgl = (int) date('j', $ts);
    $bln = $bulan[(int) date('n', $ts)];
    $thn = date('Y', $ts);
    return "{$tgl} {$bln} {$thn}";
}

// Helper pemetaan status ENUM database ke label & warna badge UI
function getStatusMeta(string $status): array {
    switch ($status) {
        case 'accepted':
            return [
                'label' => 'Diterima',
                'class' => 'bg-emerald-100 text-emerald-700',
                'notif' => 'Selamat, lamaran Anda diterima'
            ];
        case 'rejected':
            return [
                'label' => 'Ditolak',
                'class' => 'bg-rose-100 text-rose-700',
                'notif' => 'Mohon maaf, lamaran Anda belum lolos'
            ];
        case 'withdrawn':
            return [
                'label' => 'Dibatalkan',
                'class' => 'bg-slate-200 text-slate-700',
                'notif' => 'Lamaran telah dibatalkan'
            ];
        case 'interview':
            return [
                'label' => 'Sedang Diproses',
                'class' => 'bg-blue-100 text-blue-700',
                'notif' => 'Anda masuk tahap wawancara'
            ];
        case 'administrative_review':
            return [
                'label' => 'Sedang Diproses',
                'class' => 'bg-blue-100 text-blue-700',
                'notif' => 'Lamaran sedang ditinjau admin'
            ];
        case 'submitted':
        default:
            return [
                'label' => 'Sedang Diproses',
                'class' => 'bg-blue-100 text-blue-700',
                'notif' => 'Lamaran terkirim, menunggu tinjauan admin'
            ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard | TalentTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
  tailwind.config = {
    theme: { extend: {
      fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
      colors: { navy: { 800: '#102a6b', 900: '#0b1f55' } }
    } }
  };
</script>
<style>
/* ===== Gaya bersama (sama di semua page pelamar) ===== */
body { background: #e9f0fb; }
.card { background: #fff; border: 1px solid #dfe8f6; border-radius: 1rem; box-shadow: 0 8px 24px -12px rgba(16, 42, 107, .18); }
.nav-item { display: flex; align-items: center; gap: .75rem; padding: .7rem .9rem; border-radius: .65rem; font-size: .875rem; font-weight: 500; color: #c3d2f3; transition: background .15s, color .15s; }
.nav-item:hover { background: rgba(255, 255, 255, .08); color: #fff; }
.nav-item.active { background: #1d4ed8; color: #fff; }
.btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; background: #1d4ed8; color: #fff; font-weight: 600; font-size: .875rem; padding: .6rem 1rem; border-radius: .6rem; transition: background .15s; }
.btn-primary:hover { background: #1e40af; }
.btn-primary:disabled { background: #93aee8; cursor: not-allowed; }
.btn-outline { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; background: #fff; color: #1d4ed8; border: 1px solid #bcd0f5; font-weight: 600; font-size: .875rem; padding: .55rem 1rem; border-radius: .6rem; transition: background .15s; }
.btn-outline:hover { background: #eff6ff; }
.field { width: 100%; border: 1px solid #d5e0f3; border-radius: .6rem; padding: .6rem .85rem; font-size: .875rem; background: #fff; transition: border-color .15s, box-shadow .15s; }
.field:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .18); }
:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
</style>
</head>
<body class="font-sans text-slate-800 antialiased">

<div class="min-h-screen lg:flex">
  <div id="overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

  <!-- Sidebar -->
  <aside id="sidebar" class="fixed left-0 top-0 z-40 flex h-screen w-64 shrink-0 -translate-x-full flex-col gap-4 p-4 transition-transform lg:sticky lg:translate-x-0">
    <a href="dashboard.php" class="flex items-center gap-2.5 px-1 pt-1">
      <svg viewBox="0 0 32 32" class="h-9 w-9" aria-hidden="true">
        <defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
        <rect width="32" height="32" rx="9" fill="url(#lg)"/>
        <path d="M8 9.5h16v4.2h-5.9V24h-4.2V13.7H8z" fill="#fff"/>
      </svg>
      <span class="text-lg font-bold tracking-tight text-navy-800">TalentTrack</span>
    </a>
    <nav class="flex flex-1 flex-col rounded-2xl bg-navy-900 p-3" aria-label="Menu pelamar">
      <div class="flex flex-col gap-1">
        <a href="dashboard.php" class="nav-item active"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg> Dashboard</a>
        <a href="lowongan.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg> Lowongan</a>
        <a href="riwayat.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg> Riwayat Lamaran</a>
        <a href="profil.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg> Profil</a>
      </div>
      <a href="../../backend/logout.php" class="nav-item mt-auto"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg> Keluar</a>
    </nav>
  </aside>

  <div class="flex min-w-0 flex-1 flex-col">
    <!-- Topbar -->
    <header class="flex h-16 items-center justify-between px-4 lg:px-8">
      <button id="btnMenu" type="button" class="-ml-2 rounded-lg p-2 text-slate-600 hover:bg-white/70 lg:hidden" aria-label="Buka menu"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg></button>
      <div class="ml-auto flex items-center gap-2">
        <div class="relative">
          <button type="button" data-dropdown="panelNotif" class="relative rounded-full p-2 text-slate-600 hover:bg-white/70" aria-label="Notifikasi">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
            <?php if (!empty($latestApplications)): ?>
              <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-[#e9f0fb]"></span>
            <?php endif; ?>
          </button>
          <div id="panelNotif" class="dropdown-panel card absolute right-0 z-20 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] p-2">
            <p class="px-3 py-2 text-sm font-semibold text-slate-900">Status lamaran terbaru</p>
            <?php if (empty($latestApplications)): ?>
              <p class="px-3 py-2 text-xs text-slate-500">Belum ada pembaruan status lamaran.</p>
            <?php else: ?>
              <?php foreach ($latestApplications as $notifItem): 
                $meta = getStatusMeta($notifItem['status']);
              ?>
                <a href="detail.php?id=<?= (int) $notifItem['id'] ?>" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                  <span class="block truncate text-sm font-medium text-slate-800"><?= htmlspecialchars($notifItem['job_title'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="block text-xs text-slate-500"><?= htmlspecialchars($meta['notif'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="relative">
          <button type="button" data-dropdown="panelUser" class="flex items-center gap-1.5 rounded-full p-1 pr-2 hover:bg-white/70" aria-label="Menu akun">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 text-slate-500" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
          </button>
          <div id="panelUser" class="dropdown-panel card absolute right-0 z-20 mt-2 hidden w-56 p-2">
            <div class="px-3 py-2">
              <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8') ?></p>
              <p class="truncate text-xs text-slate-500"><?= htmlspecialchars($emailUser, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="profil.php" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-50">Profil saya</a>
            <a href="../../backend/logout.php" class="block rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Keluar</a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 px-4 pb-10 lg:px-8">

      <section class="pt-2">
        <h1 class="text-2xl font-bold text-slate-900">Halo, <?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8') ?>! <span aria-hidden="true">👋</span></h1>
        <p class="mt-1 text-sm text-slate-500">Selamat datang di TalentTrack. Berikut ringkasan aktivitas Anda.</p>
      </section>

      <!-- Ringkasan Statistik Lamaran -->
      <section class="mt-6 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan lamaran">
        <div class="card p-5">
          <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg></span>
          <p class="mt-3 text-sm text-slate-600">Total Lamaran</p>
          <p class="text-3xl font-bold text-slate-900"><?= $totalLamaran ?></p>
        </div>
        <div class="card p-5">
          <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
          <p class="mt-3 text-sm text-slate-600">Sedang Diproses</p>
          <p class="text-3xl font-bold text-slate-900"><?= $sedangDiproses ?></p>
        </div>
        <div class="card p-5">
          <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg></span>
          <p class="mt-3 text-sm text-slate-600">Diterima</p>
          <p class="text-3xl font-bold text-slate-900"><?= $diterima ?></p>
        </div>
      </section>

      <!-- 2 Lamaran Terbaru -->
      <section class="card mt-6 p-5">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-slate-900">Lamaran Terbaru</h2>
          <a href="riwayat.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:underline">Lihat semua <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-3.5 h-3.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        </div>
        <div class="mt-4 space-y-3">
          <?php if (empty($latestApplications)): ?>
            <div class="rounded-xl bg-slate-50 p-6 text-center">
              <p class="text-sm text-slate-500">Anda belum mengajukan lamaran pekerjaan.</p>
              <a href="lowongan.php" class="btn-primary mt-3">Cari Lowongan</a>
            </div>
          <?php else: ?>
            <?php foreach ($latestApplications as $app): 
              $badge = getStatusMeta($app['status']);
              $lokasi = !empty($app['job_location']) ? 'PT Talent Nusantara • ' . $app['job_location'] : 'PT Talent Nusantara';
            ?>
              <a href="detail.php?id=<?= (int) $app['id'] ?>" class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl bg-slate-50 p-4 transition hover:bg-blue-50">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg></span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate font-semibold text-slate-900"><?= htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="block truncate text-sm text-slate-500"><?= htmlspecialchars($lokasi, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-500"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg> <?= formatTanggalIndo($app['applied_at']) ?></span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $badge['class'] ?>"><?= htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8') ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</div>

<script>
/* ===== Skrip bersama ===== */
const $ = (s) => document.querySelector(s);
const sidebar = $('#sidebar'), overlay = $('#overlay');
function bukaSidebar(buka) {
  sidebar.classList.toggle('-translate-x-full', !buka);
  overlay.classList.toggle('hidden', !buka);
}
$('#btnMenu').addEventListener('click', () => bukaSidebar(true));
overlay.addEventListener('click', () => bukaSidebar(false));

function tutupDropdown() { document.querySelectorAll('.dropdown-panel').forEach((p) => p.classList.add('hidden')); }
document.querySelectorAll('[data-dropdown]').forEach((btn) => {
  btn.addEventListener('click', (ev) => {
    ev.stopPropagation();
    const panel = document.getElementById(btn.dataset.dropdown);
    const sudahBuka = !panel.classList.contains('hidden');
    tutupDropdown();
    if (!sudahBuka) panel.classList.remove('hidden');
  });
});
document.addEventListener('click', (ev) => { if (!ev.target.closest('.dropdown-panel')) tutupDropdown(); });

function tampilToast(pesan, jenis) {
  const el = document.createElement('div');
  el.setAttribute('role', 'status');
  el.className = 'fixed top-4 right-4 z-[60] max-w-sm rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg transition duration-300 ' + (jenis === 'error' ? 'bg-rose-600' : 'bg-emerald-600');
  el.textContent = pesan;
  document.body.appendChild(el);
  setTimeout(() => el.classList.add('opacity-0'), 3500);
  setTimeout(() => el.remove(), 3900);
}
</script>
</body>
</html>