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

// Pilihan tingkat pendidikan (harus sama dengan filter di halaman admin)
$daftarPendidikan = ['SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'];

// 1. Proses pembaruan data profil & foto (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $institusi  = trim($_POST['institusi'] ?? '');
    $jurusan    = trim($_POST['jurusan'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');

    $errors = [];
    if ($nama === '' || mb_strlen($nama) > 100) {
        $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email belum benar.';
    }
    if ($telepon !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $telepon)) {
        $errors[] = 'Nomor telepon hanya boleh berisi angka, spasi, + dan -.';
    }

    if ($pendidikan !== '' && !in_array($pendidikan, $daftarPendidikan, true)) {
        $errors[] = 'Tingkat pendidikan tidak valid.';
    }
    if (mb_strlen($institusi) > 150) {
        $errors[] = 'Nama institusi maksimal 150 karakter.';
    }
    if (mb_strlen($jurusan) > 150) {
        $errors[] = 'Jurusan maksimal 150 karakter.';
    }
    if (mb_strlen($alamat) > 500) {
        $errors[] = 'Alamat maksimal 500 karakter.';
    }
    if (mb_strlen($bio) > 1000) {
        $errors[] = 'Bio maksimal 1000 karakter.';
    }

    // Cek email tidak dipakai akun lain
    if (empty($errors)) {
        $stmtCekEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmtCekEmail->bind_param("si", $email, $applicant_id);
        $stmtCekEmail->execute();
        if ($stmtCekEmail->get_result()->fetch_assoc()) {
            $errors[] = 'Email sudah digunakan oleh akun lain.';
        }
        $stmtCekEmail->close();
    }

    // Validasi foto jika diunggah
    $newPhotoDbPath = null;
    $newPhotoFull   = null;
    if (empty($errors) && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah foto profil.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($_FILES['foto']['tmp_name']);
            $extMap  = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];
            if (!isset($extMap[$mime])) {
                $errors[] = 'Foto harus berformat JPG, PNG, atau WebP.';
            } else {
                $uploadDir = __DIR__ . '/../../uploads/photos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileName       = 'avatar_' . $applicant_id . '_' . bin2hex(random_bytes(8)) . '.' . $extMap[$mime];
                $newPhotoFull   = $uploadDir . '/' . $fileName;
                $newPhotoDbPath = 'uploads/photos/' . $fileName;

                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $newPhotoFull)) {
                    $errors[] = 'Gagal menyimpan foto di server.';
                }
            }
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Ambil path foto lama sebelum diperbarui
    $stmtOldPhoto = $conn->prepare("SELECT photo_path FROM applicant_profiles WHERE user_id = ? LIMIT 1");
    $stmtOldPhoto->bind_param("i", $applicant_id);
    $stmtOldPhoto->execute();
    $oldProfile = $stmtOldPhoto->get_result()->fetch_assoc();
    $stmtOldPhoto->close();

    $conn->begin_transaction();
    try {
        $phoneParam = $telepon !== '' ? $telepon : null;
        $stmtUpdUser = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmtUpdUser->bind_param("sssi", $nama, $email, $phoneParam, $applicant_id);
        $stmtUpdUser->execute();
        $stmtUpdUser->close();

        // Simpan data pendidikan, alamat, dan bio (kosong disimpan sebagai NULL)
        $pendParam   = $pendidikan !== '' ? $pendidikan : null;
        $instParam   = $institusi !== '' ? $institusi : null;
        $jurParam    = $jurusan !== '' ? $jurusan : null;
        $alamatParam = $alamat !== '' ? $alamat : null;
        $bioParam    = $bio !== '' ? $bio : null;
        $stmtUpdProfile = $conn->prepare("
            INSERT INTO applicant_profiles
                (user_id, education_level, institution, major, address, bio)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                education_level = VALUES(education_level),
                institution = VALUES(institution),
                major = VALUES(major),
                address = VALUES(address),
                bio = VALUES(bio)
        ");
        $stmtUpdProfile->bind_param("isssss", $applicant_id, $pendParam, $instParam, $jurParam, $alamatParam, $bioParam);
        $stmtUpdProfile->execute();
        $stmtUpdProfile->close();

        if ($newPhotoDbPath !== null) {
            $stmtUpdPhoto = $conn->prepare("
                INSERT INTO applicant_profiles (user_id, photo_path)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE photo_path = VALUES(photo_path)
            ");
            $stmtUpdPhoto->bind_param("is", $applicant_id, $newPhotoDbPath);
            $stmtUpdPhoto->execute();
            $stmtUpdPhoto->close();

            // Hapus file foto lama jika ada
            if (!empty($oldProfile['photo_path'])) {
                $oldFull = __DIR__ . '/../../' . ltrim($oldProfile['photo_path'], '/\\');
                if (is_file($oldFull)) {
                    unlink($oldFull);
                }
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Perubahan profil disimpan.'
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        if ($newPhotoFull && is_file($newPhotoFull)) {
            unlink($newPhotoFull);
        }
        echo json_encode(['success' => false, 'errors' => ['Terjadi kesalahan saat menyimpan profil.']]);
    }
    exit;
}

// 2. Ambil data profil user + foto dari users & applicant_profiles
$sqlProfil = "
    SELECT u.name, u.email, u.phone, ap.photo_path,
           ap.education_level, ap.institution, ap.major, ap.address, ap.bio
    FROM users u
    LEFT JOIN applicant_profiles ap ON ap.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
";
$stmtProfil = $conn->prepare($sqlProfil);
$stmtProfil->bind_param("i", $applicant_id);
$stmtProfil->execute();
$user = $stmtProfil->get_result()->fetch_assoc();
$stmtProfil->close();

$namaUser    = $user['name'] ?? 'Pelamar';
$emailUser   = $user['email'] ?? '';
$teleponUser = $user['phone'] ?? '';
$photoPath   = $user['photo_path'] ?? '';
$pendidikanUser = $user['education_level'] ?? '';
$institusiUser  = $user['institution'] ?? '';
$jurusanUser    = $user['major'] ?? '';
$alamatUser     = $user['address'] ?? '';
$bioUser        = $user['bio'] ?? '';

$kataNama = preg_split('/\s+/', trim($namaUser));
$inisial  = '';
foreach (array_slice($kataNama, 0, 2) as $kata) {
    $inisial .= mb_strtoupper(mb_substr($kata, 0, 1));
}
if ($inisial === '') {
    $inisial = 'PL';
}

$adaFoto = false;
$urlFoto = '';
if ($photoPath !== '') {
    $relFoto = ltrim($photoPath, '/\\');
    if (is_file(__DIR__ . '/../../' . $relFoto)) {
        $adaFoto = true;
        $urlFoto = '../../' . $relFoto;
    }
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
<title>Profil Pelamar | TalentTrack</title>
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
        <a href="dashboard.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg> Dashboard</a>
        <a href="lowongan.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg> Lowongan</a>
        <a href="riwayat.php" class="nav-item "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg> Riwayat Lamaran</a>
        <a href="profil.php" class="nav-item active"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg> Profil</a>
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
              <p class="truncate text-sm font-semibold text-slate-900" id="topbarNama"><?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8') ?></p>
              <p class="truncate text-xs text-slate-500" id="topbarEmail"><?= htmlspecialchars($emailUser, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="profil.php" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-50">Profil saya</a>
            <a href="../../backend/logout.php" class="block rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">Keluar</a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 px-4 pb-10 lg:px-8">

      <section class="pt-2">
        <h1 class="text-2xl font-bold text-slate-900">Profil Pelamar</h1>
        <p class="mt-1 text-sm text-slate-500">Lengkapi informasi profil Anda untuk melamar pekerjaan.</p>
      </section>

      <form id="formProfil" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-[280px_1fr]" novalidate>
        <section class="card flex flex-col items-center p-6">
          <div class="relative">
            <div id="kotakAvatar">
              <?php if ($adaFoto): ?>
                <img src="<?= htmlspecialchars($urlFoto, ENT_QUOTES, 'UTF-8') ?>" alt="Foto profil" class="h-28 w-28 rounded-full object-cover">
              <?php else: ?>
                <span class="inline-flex h-28 w-28 items-center justify-center rounded-full bg-blue-600 text-3xl font-semibold text-white"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>
            <label for="foto" class="absolute bottom-0 right-0 inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-blue-600 text-white ring-2 ring-white hover:bg-blue-700" title="Ubah foto">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg><span class="sr-only">Ubah foto</span>
            </label>
          </div>
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" class="sr-only">
          <label for="foto" class="mt-3 cursor-pointer text-sm font-semibold text-slate-800 hover:text-blue-700">Ubah Foto</label>
          <p id="infoFoto" class="mt-1 text-center text-xs text-slate-500">JPG, PNG, atau WebP, maksimal 2 MB</p>
          <p id="errorFoto" class="mt-1 hidden text-center text-xs text-rose-600" role="alert"></p>
        </section>

        <section class="card p-6">
          <div id="errorForm" class="mb-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
            <p class="font-semibold">Perubahan belum disimpan:</p>
            <ul id="daftarError" class="mt-1 list-disc pl-5"></ul>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label for="nama" class="mb-1 block text-xs font-semibold text-slate-700">Nama lengkap</label>
              <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8') ?>" maxlength="100" required class="field">
            </div>
            <div>
              <label for="email" class="mb-1 block text-xs font-semibold text-slate-700">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($emailUser, ENT_QUOTES, 'UTF-8') ?>" required class="field">
            </div>
            <div class="sm:col-span-2">
              <label for="telepon" class="mb-1 block text-xs font-semibold text-slate-700">Nomor telepon</label>
              <input type="tel" id="telepon" name="telepon" value="<?= htmlspecialchars($teleponUser, ENT_QUOTES, 'UTF-8') ?>" placeholder="0812 3456 7890" class="field">
            </div>
            <div>
              <label for="pendidikan" class="mb-1 block text-xs font-semibold text-slate-700">Tingkat pendidikan</label>
              <select id="pendidikan" name="pendidikan" class="field">
                <option value="">Pilih tingkat pendidikan</option>
                <?php foreach ($daftarPendidikan as $opsi): ?>
                  <option value="<?= htmlspecialchars($opsi, ENT_QUOTES, 'UTF-8') ?>"<?= $pendidikanUser === $opsi ? ' selected' : '' ?>><?= htmlspecialchars($opsi, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="institusi" class="mb-1 block text-xs font-semibold text-slate-700">Institusi / sekolah</label>
              <input type="text" id="institusi" name="institusi" value="<?= htmlspecialchars($institusiUser, ENT_QUOTES, 'UTF-8') ?>" maxlength="150" placeholder="Contoh: Universitas Gadjah Mada" class="field">
            </div>
            <div class="sm:col-span-2">
              <label for="jurusan" class="mb-1 block text-xs font-semibold text-slate-700">Jurusan</label>
              <input type="text" id="jurusan" name="jurusan" value="<?= htmlspecialchars($jurusanUser, ENT_QUOTES, 'UTF-8') ?>" maxlength="150" placeholder="Contoh: Teknik Informatika" class="field">
            </div>
            <div class="sm:col-span-2">
              <label for="alamat" class="mb-1 block text-xs font-semibold text-slate-700">Alamat</label>
              <textarea id="alamat" name="alamat" rows="2" maxlength="500" class="field"><?= htmlspecialchars($alamatUser, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="sm:col-span-2">
              <label for="bio" class="mb-1 block text-xs font-semibold text-slate-700">Bio singkat</label>
              <textarea id="bio" name="bio" rows="4" maxlength="1000" placeholder="Ceritakan singkat tentang diri dan keahlian Anda" class="field"><?= htmlspecialchars($bioUser, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
          </div>

          <button type="submit" id="btnSimpan" class="btn-primary mt-6 w-full">Simpan perubahan</button>
        </section>
      </form>
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
const inputFoto = $('#foto'), errorFoto = $('#errorFoto'), infoFoto = $('#infoFoto'), kotakAvatar = $('#kotakAvatar');

inputFoto.addEventListener('change', () => {
  const file = inputFoto.files[0];
  if (!file) return;
  let err = '';
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) err = 'Foto harus berformat JPG, PNG, atau WebP.';
  else if (file.size > 2 * 1024 * 1024) err = 'Ukuran foto maksimal 2 MB.';

  errorFoto.textContent = err;
  errorFoto.classList.toggle('hidden', !err);
  if (err) { inputFoto.value = ''; return; }

  const pembaca = new FileReader();
  pembaca.onload = (ev) => {
    kotakAvatar.innerHTML = '<img src="' + ev.target.result + '" alt="Pratinjau foto" class="h-28 w-28 rounded-full object-cover">';
  };
  pembaca.readAsDataURL(file);
  infoFoto.textContent = 'Foto baru dipilih. Klik "Simpan perubahan" untuk menyimpan.';
});

$('#formProfil').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const nama = $('#nama').value.trim(), email = $('#email').value.trim(), telepon = $('#telepon').value.trim();
  const errors = [];
  if (!nama || nama.length > 100) errors.push('Nama lengkap wajib diisi (maksimal 100 karakter).');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Format email belum benar.');
  if (telepon && !/^[0-9+\-\s]{8,20}$/.test(telepon)) errors.push('Nomor telepon hanya boleh berisi angka, spasi, + dan -.');

  const kotak = $('#errorForm');
  $('#daftarError').innerHTML = errors.map((e) => '<li>' + e.replace(/</g, '&lt;') + '</li>').join('');
  kotak.classList.toggle('hidden', errors.length === 0);
  if (errors.length) return;

  const btnSimpan = $('#btnSimpan');
  btnSimpan.disabled = true;
  btnSimpan.textContent = 'Menyimpan…';

  try {
    const resp = await fetch('profil.php', {
      method: 'POST',
      body: new FormData(ev.target)
    });
    const hasil = await resp.json();

    if (!hasil.success) {
      const serverErrors = hasil.errors || ['Gagal menyimpan perubahan profil.'];
      $('#daftarError').innerHTML = serverErrors.map((e) => '<li>' + e.replace(/</g, '&lt;') + '</li>').join('');
      kotak.classList.remove('hidden');
      return;
    }

    $('#topbarNama').textContent = nama;
    $('#topbarEmail').textContent = email;
    infoFoto.textContent = 'JPG, PNG, atau WebP, maksimal 2 MB';
    tampilToast(hasil.message);
  } catch (err) {
    tampilToast('Terjadi kesalahan koneksi ke server.', 'error');
  } finally {
    btnSimpan.disabled = false;
    btnSimpan.textContent = 'Simpan perubahan';
  }
});
</script>
</body>
</html>