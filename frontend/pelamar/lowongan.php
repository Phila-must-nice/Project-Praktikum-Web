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

// 1. Proses pengajuan lamaran + upload CV (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    if (!$job_id) {
        echo json_encode(['success' => false, 'message' => 'Lowongan tidak valid.']);
        exit;
    }

    // Pastikan lowongan berstatus published dan belum melewati deadline
    $stmtCekJob = $conn->prepare("
        SELECT id 
        FROM jobs 
        WHERE id = ? 
          AND status = 'published' 
          AND (deadline IS NULL OR deadline >= CURDATE())
        LIMIT 1
    ");
    $stmtCekJob->bind_param("i", $job_id);
    $stmtCekJob->execute();
    $jobValid = $stmtCekJob->get_result()->fetch_assoc();
    $stmtCekJob->close();

    if (!$jobValid) {
        echo json_encode(['success' => false, 'message' => 'Lowongan sudah ditutup atau tidak ditemukan.']);
        exit;
    }

    // Cegah lamaran ganda pada lowongan yang sama
    $stmtCekApp = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND applicant_id = ? LIMIT 1");
    $stmtCekApp->bind_param("ii", $job_id, $applicant_id);
    $stmtCekApp->execute();
    $sudahMelamar = $stmtCekApp->get_result()->fetch_assoc();
    $stmtCekApp->close();

    if ($sudahMelamar) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah melamar posisi ini sebelumnya.']);
        exit;
    }

    // Validasi file CV (PDF, maksimal 2 MB)
    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File CV wajib diunggah.']);
        exit;
    }

    $fileCv = $_FILES['cv'];
    if ($fileCv['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file CV maksimal 2 MB.']);
        exit;
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileCv['tmp_name']);
    if ($mimeType !== 'application/pdf') {
        echo json_encode(['success' => false, 'message' => 'File CV harus berformat PDF.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/cv';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $namaFileBaru = 'cv_' . $applicant_id . '_' . $job_id . '_' . bin2hex(random_bytes(8)) . '.pdf';
    $tujuanFile   = $uploadDir . '/' . $namaFileBaru;
    $pathDb       = 'uploads/cv/' . $namaFileBaru;

    if (!move_uploaded_file($fileCv['tmp_name'], $tujuanFile)) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file CV di server.']);
        exit;
    }

    // Simpan ke tabel applications & application_documents
    $conn->begin_transaction();
    try {
        $stmtInsertApp = $conn->prepare("
            INSERT INTO applications (job_id, applicant_id, applied_at, status)
            VALUES (?, ?, NOW(), 'submitted')
        ");
        $stmtInsertApp->bind_param("ii", $job_id, $applicant_id);
        $stmtInsertApp->execute();
        $application_id = (int) $conn->insert_id;
        $stmtInsertApp->close();

        $origName = basename($fileCv['name']);
        $fileSize = (int) $fileCv['size'];

        $stmtInsertDoc = $conn->prepare("
            INSERT INTO application_documents 
                (application_id, document_type, original_name, file_path, mime_type, file_size)
            VALUES (?, 'cv', ?, ?, ?, ?)
        ");
        $stmtInsertDoc->bind_param("isssi", $application_id, $origName, $pathDb, $mimeType, $fileSize);
        $stmtInsertDoc->execute();
        $stmtInsertDoc->close();

        $conn->commit();

        echo json_encode([
            'success'        => true,
            'application_id' => $application_id,
            'message'        => 'Lamaran berhasil dikirim. Admin akan mengecek CV Anda.'
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        if (is_file($tujuanFile)) {
            unlink($tujuanFile);
        }
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan lamaran.']);
    }
    exit;
}

// 2. Ambil data profil pelamar untuk topbar
$stmtUser = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmtUser->bind_param("i", $applicant_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$namaUser  = $user['name'] ?? 'Pelamar';
$emailUser = $user['email'] ?? '';

$kataNama = preg_split('/\s+/', trim($namaUser));
$inisial  = '';
foreach (array_slice($kataNama, 0, 2) as $kata) {
    $inisial .= mb_strtoupper(mb_substr($kata, 0, 1));
}
if ($inisial === '') {
    $inisial = 'PL';
}

// 3. Ambil 2 lamaran terbaru untuk dropdown notifikasi topbar
$sqlLatest = "
    SELECT a.id, a.status, j.title AS job_title
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

// 4. Ambil daftar lowongan yang berstatus published beserta status lamaran pelamar (mysqli list)
$sqlJobs = "
    SELECT 
        j.id,
        j.title,
        j.category,
        j.location,
        j.description,
        j.deadline,
        a.id AS application_id
    FROM jobs j
    LEFT JOIN applications a 
        ON a.job_id = j.id AND a.applicant_id = ?
    WHERE j.status = 'published'
    ORDER BY j.created_at DESC, j.id DESC
";
$stmtJobs = $conn->prepare($sqlJobs);
$stmtJobs->bind_param("i", $applicant_id);
$stmtJobs->execute();
$jobs = $stmtJobs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtJobs->close();

// Kumpulkan kategori unik untuk dropdown filter
$daftarKategori = [];
foreach ($jobs as $job) {
    if (!empty($job['category']) && !in_array($job['category'], $daftarKategori, true)) {
        $daftarKategori[] = $job['category'];
    }
}

function formatTanggalIndo(?string $date): string {
    if (empty($date)) {
        return 'Tanpa batas waktu';
    }
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $ts = strtotime($date);
    if (!$ts) {
        return '-';
    }
    return (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function getNotifText(string $status): string {
    switch ($status) {
        case 'accepted':
            return 'Selamat, lamaran Anda diterima';
        case 'rejected':
            return 'Mohon maaf, lamaran Anda belum lolos';
        case 'withdrawn':
            return 'Lamaran telah dibatalkan';
        case 'interview':
            return 'Anda masuk tahap wawancara';
        case 'administrative_review':
            return 'Lamaran sedang ditinjau admin';
        default:
            return 'Lamaran terkirim, menunggu tinjauan admin';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lowongan Pekerjaan | TalentTrack</title>
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

/* ===== Gaya khusus page ini ===== */
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
#dropzone.drag { background: #eff6ff; border-color: #2563eb; }
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
        <a href="dashboard.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg> Dashboard</a>
        <a href="lowongan.php" class="nav-item active"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg> Lowongan</a>
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
              <?php foreach ($latestApplications as $notifItem): ?>
                <a href="detail.php?id=<?= (int) $notifItem['id'] ?>" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                  <span class="block truncate text-sm font-medium text-slate-800"><?= htmlspecialchars($notifItem['job_title'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="block text-xs text-slate-500"><?= htmlspecialchars(getNotifText($notifItem['status']), ENT_QUOTES, 'UTF-8') ?></span>
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
        <h1 class="text-2xl font-bold text-slate-900">Lowongan Pekerjaan</h1>
        <p class="mt-1 text-sm text-slate-500">Temukan posisi yang sesuai dengan keahlian Anda.</p>
      </section>

      <div class="mt-5 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg></span>
          <input type="search" id="cari" placeholder="Cari lowongan…" class="field pl-9" aria-label="Cari lowongan">
        </div>
        <select id="filterKategori" class="field sm:w-52" aria-label="Filter posisi">
          <option value="">Semua Posisi</option>
          <?php foreach ($daftarKategori as $kat): ?>
            <option value="<?= htmlspecialchars($kat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($kat, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <section class="mt-5 space-y-4" aria-label="Daftar lowongan">
        <?php if (empty($jobs)): ?>
          <div class="card px-6 py-12 text-center">
            <p class="font-medium text-slate-800">Belum ada lowongan yang dibuka saat ini</p>
            <p class="mt-1 text-sm text-slate-500">Silakan cek kembali di lain waktu.</p>
          </div>
        <?php else: ?>
          <?php foreach ($jobs as $job): 
            $kategori   = $job['category'] ?? 'Umum';
            $perusahaan = !empty($job['location']) ? 'PT Talent Nusantara • ' . $job['location'] : 'PT Talent Nusantara';
            $teksCari   = mb_strtolower($job['title'] . ' ' . $perusahaan . ' ' . $job['description']);
            $sudahLamar = !empty($job['application_id']);
          ?>
            <article class="kartu card flex flex-col gap-4 p-5 sm:flex-row sm:items-center" data-kategori="<?= htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8') ?>" data-teks="<?= htmlspecialchars($teksCari, ENT_QUOTES, 'UTF-8') ?>">
              <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-7 h-7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg></span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="font-semibold text-slate-900"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                  <span class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"><?= htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="text-sm text-slate-500"><?= htmlspecialchars($perusahaan, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 line-clamp-2 max-w-prose text-sm text-blue-900/70"><?= htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <div class="flex flex-col gap-3 sm:items-end">
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-500"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg> Deadline: <?= formatTanggalIndo($job['deadline']) ?></span>
                <?php if ($sudahLamar): ?>
                  <a href="detail.php?id=<?= (int) $job['application_id'] ?>" class="btn-outline">Sudah melamar · Lihat status</a>
                <?php else: ?>
                  <button type="button" class="btn-primary btn-lamar" data-id="<?= (int) $job['id'] ?>" data-judul="<?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>" data-perusahaan="<?= htmlspecialchars($perusahaan, ENT_QUOTES, 'UTF-8') ?>">Lamar sekarang <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>

          <div id="kosong" class="card hidden px-6 py-12 text-center">
            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z"/></svg></span>
            <p class="mt-3 font-medium text-slate-800">Tidak ada lowongan yang cocok</p>
            <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau pilih "Semua Posisi".</p>
          </div>
        <?php endif; ?>
      </section>

      <!-- Modal kirim CV -->
      <div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modalJudul">
        <div class="absolute inset-0 bg-slate-900/50" data-tutup></div>
        <form id="formLamar" method="POST" enctype="multipart/form-data" class="card relative w-full max-w-md p-6">
          <input type="hidden" name="job_id" id="jobIdInput" value="">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 id="modalJudul" class="text-lg font-bold text-slate-900">Lamar posisi</h2>
              <p id="modalSub" class="truncate text-sm text-slate-500"></p>
            </div>
            <button type="button" data-tutup class="rounded-lg p-1 text-slate-400 hover:text-slate-700" aria-label="Tutup"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>

          <label id="dropzone" for="cv" class="mt-5 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-blue-200 px-4 py-8 text-center transition hover:bg-blue-50">
            <span class="text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-8 h-8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg></span>
            <span id="teksCv" class="break-all text-sm font-medium text-slate-700">Pilih file CV atau seret ke sini</span>
            <span class="text-xs text-slate-500">Format PDF, maksimal 2 MB</span>
          </label>
          <input type="file" id="cv" name="cv" accept="application/pdf,.pdf" class="sr-only">
          <p id="errorCv" class="mt-2 hidden text-sm text-rose-600" role="alert"></p>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-tutup class="btn-outline">Batal</button>
            <button type="submit" id="btnKirim" class="btn-primary" disabled>Kirim lamaran</button>
          </div>
        </form>
      </div>
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

/* ===== Skrip khusus page ini ===== */
const cari = $('#cari'), filterKategori = $('#filterKategori'), kosong = $('#kosong');
const kartu = [...document.querySelectorAll('.kartu')];
function terapkanFilter() {
  if (!kosong) return;
  const kata = cari.value.trim().toLowerCase();
  const kat = filterKategori.value;
  let tampil = 0;
  kartu.forEach((k) => {
    const cocok = (!kata || k.dataset.teks.includes(kata)) && (!kat || k.dataset.kategori === kat);
    k.classList.toggle('hidden', !cocok);
    if (cocok) tampil++;
  });
  kosong.classList.toggle('hidden', tampil > 0);
}
cari.addEventListener('input', terapkanFilter);
filterKategori.addEventListener('change', terapkanFilter);

const modal = $('#modal'), inputCv = $('#cv'), errorCv = $('#errorCv'), btnKirim = $('#btnKirim'), teksCv = $('#teksCv'), jobIdInput = $('#jobIdInput');
const TEKS_AWAL = 'Pilih file CV atau seret ke sini';
let tombolAktif = null;

function toggleModal(buka) {
  modal.classList.toggle('hidden', !buka);
  modal.classList.toggle('flex', buka);
  document.body.style.overflow = buka ? 'hidden' : '';
  if (buka) inputCv.focus();
}
function resetCv() {
  inputCv.value = '';
  teksCv.textContent = TEKS_AWAL;
  errorCv.classList.add('hidden');
  btnKirim.disabled = true;
  btnKirim.textContent = 'Kirim lamaran';
}
function cekCv(file) {
  if (!file) return 'Pilih file CV terlebih dahulu.';
  if (file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)) return 'File harus berformat PDF.';
  if (file.size > 2 * 1024 * 1024) return 'Ukuran file maksimal 2 MB.';
  return '';
}
function tampilkanCv(file) {
  const err = cekCv(file);
  errorCv.textContent = err;
  errorCv.classList.toggle('hidden', !err);
  btnKirim.disabled = !!err;
  teksCv.textContent = file && !err ? file.name : TEKS_AWAL;
}

document.querySelectorAll('.btn-lamar').forEach((btn) => {
  btn.addEventListener('click', () => {
    tombolAktif = btn;
    jobIdInput.value = btn.dataset.id;
    $('#modalJudul').textContent = 'Lamar sebagai ' + btn.dataset.judul;
    $('#modalSub').textContent = btn.dataset.perusahaan;
    resetCv();
    toggleModal(true);
  });
});
modal.querySelectorAll('[data-tutup]').forEach((el) => el.addEventListener('click', () => toggleModal(false)));
document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') toggleModal(false); });

inputCv.addEventListener('change', () => tampilkanCv(inputCv.files[0]));
const zona = $('#dropzone');
['dragenter', 'dragover'].forEach((n) => zona.addEventListener(n, (ev) => { ev.preventDefault(); zona.classList.add('drag'); }));
['dragleave', 'drop'].forEach((n) => zona.addEventListener(n, (ev) => { ev.preventDefault(); zona.classList.remove('drag'); }));
zona.addEventListener('drop', (ev) => {
  if (ev.dataTransfer.files.length) {
    inputCv.files = ev.dataTransfer.files;
    tampilkanCv(inputCv.files[0]);
  }
});

$('#formLamar').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  btnKirim.disabled = true;
  btnKirim.textContent = 'Mengirim…';

  try {
    const resp = await fetch('lowongan.php', {
      method: 'POST',
      body: new FormData(ev.target)
    });
    const hasil = await resp.json();

    if (!hasil.success) {
      tampilToast(hasil.message || 'Gagal mengirim lamaran.', 'error');
      btnKirim.disabled = false;
      btnKirim.textContent = 'Kirim lamaran';
      return;
    }

    toggleModal(false);
    if (tombolAktif) {
      const link = document.createElement('a');
      link.href = 'detail.php?id=' + hasil.application_id;
      link.className = 'btn-outline';
      link.textContent = 'Sudah melamar · Lihat status';
      tombolAktif.replaceWith(link);
    }
    tampilToast(hasil.message);
  } catch (err) {
    tampilToast('Terjadi kesalahan koneksi ke server.', 'error');
    btnKirim.disabled = false;
    btnKirim.textContent = 'Kirim lamaran';
  }
});
</script>
</body>
</html>