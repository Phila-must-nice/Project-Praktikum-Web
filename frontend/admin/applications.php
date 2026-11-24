<?php

session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../backend/admin/applications_data.php';

$statusLabels = [
    'submitted' => 'Dikirim',
    'administrative_review' => 'Review Administrasi',
    'interview' => 'Wawancara',
    'accepted' => 'Diterima',
    'rejected' => 'Ditolak',
    'withdrawn' => 'Dibatalkan'
];

?>

<?php if (isset($_GET['pesan'])) : ?>

    <?php if ($_GET['pesan'] == 'berhasil_diubah') : ?>
        <p>Status lamaran berhasil diperbarui.</p>

    <?php elseif ($_GET['pesan'] == 'gagal_diubah') : ?>
        <p>Gagal memperbarui status lamaran.</p>

    <?php elseif ($_GET['pesan'] == 'id_tidak_valid') : ?>
        <p>ID lamaran tidak valid.</p>

    <?php elseif ($_GET['pesan'] == 'status_tidak_valid') : ?>
        <p>Status lamaran tidak valid.</p>
    <?php endif; ?>

<?php endif; ?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Lamaran | TalentTrack</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */

        .sidebar {
            width: 250px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 28px 20px;
            position: fixed;
            height: 100vh;
        }

        .logo {
            font-size: 23px;
            font-weight: 800;
            color: #2563eb;
            margin-bottom: 45px;
            padding-left: 10px;
        }

        .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            margin: 25px 10px 12px;
            letter-spacing: 1px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            margin-bottom: 7px;
            border-radius: 8px;
            text-decoration: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #eff6ff;
            color: #2563eb;
        }

        .menu a.active {
            font-weight: 700;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        /* Main Content */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 35px;
        }

        /* Navbar */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .page-title p {
            color: #94a3b8;
            font-size: 13px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 17px;
        }

        .admin-info h4 {
            font-size: 13px;
            margin-bottom: 4px;
        }

        .admin-info p {
            color: #94a3b8;
            font-size: 11px;
        }

        /* Statistics */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 18px;
        }

        .blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .orange {
            background: #fff7ed;
            color: #ea580c;
        }

        .green {
            background: #ecfdf5;
            color: #059669;
        }

        .stat-card p {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .stat-card h2 {
            font-size: 27px;
            font-weight: 700;
        }

        /* Table */

        .table-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-header {
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header h3 {
            font-size: 16px;
        }

        .table-header p {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 6px;
        }

        .toolbar {
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search {
            flex: 1;
            max-width: 350px;
        }

        .search input {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            outline: none;
        }

        .search input:focus,
        .toolbar select:focus {
            border-color: #93c5fd;
        }

        .toolbar select {
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            background: white;
            outline: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            padding: 15px 20px;
        }

        td {
            padding: 17px 20px;
            font-size: 12px;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
        }

        tbody tr:hover {
            background: #fafcff;
        }

        .applicant {
            font-weight: 600;
            color: #334155;
        }

        .job-name {
            color: #64748b;
        }

        /* Status */

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .submitted {
            background: #eff6ff;
            color: #2563eb;
        }

        .administrative_review {
            background: #fff7ed;
            color: #c2410c;
        }

        .interview {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .accepted {
            background: #ecfdf5;
            color: #059669;
        }

        .rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .withdrawn {
            background: #f1f5f9;
            color: #64748b;
        }

        .action {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .action:hover {
            text-decoration: underline;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #94a3b8;
        }

        /* Responsive */

        @media(max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .sidebar {
                width: 65px;
                padding: 25px 8px;
            }

            .logo {
                font-size: 0;
                padding: 0;
                text-align: center;
            }

            .logo:first-letter {
                font-size: 22px;
            }

            .menu-title,
            .menu a span:last-child {
                display: none;
            }

            .menu a {
                justify-content: center;
                padding: 14px 5px;
            }

            .main {
                margin-left: 65px;
                width: calc(100% - 65px);
                padding: 25px 18px;
            }

            .admin-info {
                display: none;
            }

            .table-container {
                overflow-x: auto;
            }
        }

        @media(max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 20px;
            }
        }

        /* Tombol Update */

        .btn-update {
            background: #eff6ff;
            color: #2563eb;
            border: none;
            padding: 9px 13px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-update:hover {
            background: #dbeafe;
        }

        .btn-cv {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 8px 11px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            margin: 2px 3px 2px 0;
        }

        .btn-cv:hover {
            background: #d1fae5;
        }

        .cv-missing {
            color: #94a3b8;
            font-size: 11px;
        }

        /* Modal */

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 999;
            padding: 20px;
        }

        .modal-content {
            background: white;
            width: 100%;
            max-width: 480px;
            padding: 28px;
            border-radius: 12px;
        }

        .modal-content h3 {
            font-size: 19px;
            margin-bottom: 12px;
        }

        .modal-content p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 22px;
        }

        .modal-content label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            margin-top: 15px;
        }

        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            font-family: inherit;
            font-size: 13px;
        }

        .modal-content textarea {
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .btn-cancel,
        .btn-save {
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-save {
            background: #2563eb;
            color: white;
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- Sidebar -->

    <aside class="sidebar">

        <div class="logo">TalentTrack.</div>

        <div class="menu-title">MENU UTAMA</div>

        <nav class="menu">

            <a href="dashboard.php">
                <span class="menu-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="jobs.php">
                <span class="menu-icon">▤</span>
                <span>Lowongan Pekerjaan</span>
            </a>

            <a href="applicants.php">
                <span class="menu-icon">♙</span>
                <span>Data Pelamar</span>
            </a>

            <a href="applications.php" class="active">
                <span class="menu-icon">▣</span>
                <span>Data Lamaran</span>
            </a>

        </nav>

        <div class="menu-title">AKUN</div>

        <nav class="menu">

            <a href="../../backend/logout.php">
                <span class="menu-icon">⇥</span>
                <span>Logout</span>
            </a>

        </nav>

    </aside>

    <!-- Main Content -->

    <main class="main">

        <!-- Navbar -->

        <header class="topbar">

            <div class="page-title">
                <h1>Data Lamaran</h1>
                <p>Pantau status dan perkembangan proses rekrutmen.</p>
            </div>

            <div class="admin-profile">

                <div class="avatar">A</div>

                <div class="admin-info">
                    <h4>Administrator</h4>
                    <p>Administrator</p>
                </div>

            </div>

        </header>

        <!-- Statistics -->

        <section class="stats">

            <div class="stat-card">
                <div class="stat-icon blue">▣</div>

                <div class="stat-info">
                    <p>Total Lamaran</p>
                    <h2><?= $total_lamaran ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">◷</div>

                <div class="stat-info">
                    <p>Menunggu Review</p>
                    <h2><?= $menunggu_review ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✓</div>

                <div class="stat-info">
                    <p>Lamaran Diterima</p>
                    <h2><?= $lamaran_diterima ?></h2>
                </div>
            </div>

        </section>

        <!-- Applications Table -->

        <section class="table-container">

            <div class="table-header">

                <div>
                    <h3>Daftar Lamaran</h3>
                    <p>Kelola dan pantau seluruh lamaran kandidat.</p>
                </div>

            </div>

            <div class="toolbar">

                <div class="search">

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Cari nama pelamar atau posisi..."
                    >

                </div>

                <select id="statusFilter">

                    <option value="">Semua Status</option>

                    <option value="submitted">Dikirim</option>

                    <option value="administrative_review">
                        Review Administrasi
                    </option>

                    <option value="interview">Interview</option>

                    <option value="accepted">Diterima</option>

                    <option value="rejected">Ditolak</option>

                    <option value="withdrawn">Dibatalkan</option>

                </select>

            </div>

            <table id="applicationsTable">

                <thead>

                    <tr>
                        <th>PELAMAR</th>
                        <th>POSISI</th>
                        <th>TANGGAL MELAMAR</th>
                        <th>CV</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>

                </thead>

                
                <tbody>

                    <?php foreach ($applications as $data) : ?>

                        <tr data-status="<?= htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8') ?>">

                            <td>
                                <span class="applicant">
                                    <?= htmlspecialchars($data['nama_pelamar'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <br>
                                <?= htmlspecialchars($data['email_pelamar'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <span class="job-name">
                                    <?= htmlspecialchars($data['nama_lowongan'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($data['applied_at'])) ?>
                            </td>

                            <td>
                                <?php if (!empty($data['cv_document_id'])) : ?>
                                    <a class="btn-cv" href="../pelamar/cv.php?id=<?= (int) $data['id'] ?>" target="_blank" rel="noopener">Lihat CV</a>
                                    <a class="btn-cv" href="../pelamar/cv.php?id=<?= (int) $data['id'] ?>&download=1">Unduh</a>
                                <?php else : ?>
                                    <span class="cv-missing">CV belum tersedia</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status <?= htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($statusLabels[$data['status']] ?? $data['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-update"
                                    onclick="document.getElementById('modal<?= (int) $data['id'] ?>').style.display='flex'">
                                    Update Status
                                </button>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>


            </table>

            <!-- Modal Update Status untuk masing-masing lamaran -->
            <?php foreach ($applications as $data) : ?>
                <div class="modal" id="modal<?= (int) $data['id'] ?>">
                    <div class="modal-content">
                        <h3>Update Status Lamaran</h3>
                        <p>Pelamar: <strong><?= htmlspecialchars($data['nama_pelamar'], ENT_QUOTES, 'UTF-8') ?></strong></p>

                        <form action="../../backend/admin/applications_update.php" method="POST">
                            <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">
                            <label>Status Lamaran</label>
                            <select name="status" required>
                                <option value="submitted" <?= $data['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                                <option value="administrative_review" <?= $data['status'] === 'administrative_review' ? 'selected' : '' ?>>Administrative Review</option>
                                <option value="interview" <?= $data['status'] === 'interview' ? 'selected' : '' ?>>Interview</option>
                                <option value="accepted" <?= $data['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                <option value="rejected" <?= $data['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="withdrawn" <?= $data['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                            </select>

                            <label>Catatan Admin</label>
                            <textarea name="admin_note" rows="4" placeholder="Masukkan catatan untuk pelamar..."><?= htmlspecialchars($data['admin_note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                            <div class="modal-actions">
                                <button type="button" class="btn-cancel" onclick="document.getElementById('modal<?= (int) $data['id'] ?>').style.display='none'">Batal</button>
                                <button type="submit" class="btn-save">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <div id="emptyMessage" class="empty" style="<?= empty($applications) ? '' : 'display:none' ?>">
                <?= empty($applications) ? 'Belum ada lamaran yang masuk.' : 'Data lamaran tidak ditemukan.' ?>
            </div>

        </section>

    </main>

</div>

<script>

    // Pencarian dan filter status lamaran

    const searchInput = document.getElementById('searchInput');

    const statusFilter = document.getElementById('statusFilter');

    const rows = document.querySelectorAll(
        '#applicationsTable tbody tr'
    );

    function filterApplications() {

        let search = searchInput.value.toLowerCase();

        let status = statusFilter.value;

        let visible = 0;

        rows.forEach(function(row) {

            let text = row.innerText.toLowerCase();

            let rowStatus = row.dataset.status;

            let matchSearch = text.includes(search);

            let matchStatus =
                status === '' || rowStatus === status;

            if (matchSearch && matchStatus) {

                row.style.display = '';

                visible++;

            } else {

                row.style.display = 'none';

            }

        });

        document.getElementById('emptyMessage').style.display =
            visible === 0 ? 'block' : 'none';

    }

    searchInput.addEventListener(
        'input',
        filterApplications
    );

    statusFilter.addEventListener(
        'change',
        filterApplications
    );

    function showNotice() {

        alert('Tombol detail belum dihubungkan dengan backend.');

        return false;

    }

</script>

</body>
</html>
