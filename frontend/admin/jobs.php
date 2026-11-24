<?php

session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../backend/admin/jobs_data.php';

?>

<?php if (isset($_GET['pesan'])) : ?>

    <?php if ($_GET['pesan'] == 'dihapus') : ?>
        <p>Lowongan berhasil dihapus.</p>

    <?php elseif ($_GET['pesan'] == 'memiliki_lamaran') : ?>
        <p>Lowongan tidak bisa dihapus karena sudah memiliki lamaran.</p>

    <?php elseif ($_GET['pesan'] == 'gagal_hapus') : ?>
        <p>Gagal menghapus lowongan.</p>
    <?php endif; ?>

<?php endif; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan Pekerjaan | TalentTrack</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .sidebar {
            width: 250px;
            background: white;
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

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 25px;
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
            background: #eff6ff;
            color: #2563eb;
        }

        .stat-card p {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .stat-card h2 {
            font-size: 27px;
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
            gap: 15px;
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

        .btn {
            padding: 11px 16px;
            background: #2563eb;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .toolbar {
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search {
            flex: 1;
            max-width: 350px;
        }

        .search input,
        .toolbar select {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            outline: none;
            background: white;
        }

        .search input:focus,
        .toolbar select:focus {
            border-color: #93c5fd;
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
            padding: 15px 20px;
        }

        td {
            padding: 17px 20px;
            font-size: 12px;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
        }

        .job-name {
            font-weight: 600;
            color: #334155;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .active {
            background: #ecfdf5;
            color: #059669;
        }

        .closed {
            background: #fef2f2;
            color: #dc2626;
        }

        .draft {
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

        
        /* MODAL TAMBAH LOWONGAN */
        #modalTambah .modal-dialog {
            max-width: 750px;
        }

        #modalTambah .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.2);
        }

        #modalTambah .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 26px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        #modalTambah .modal-title {
            font-size: 19px;
            font-weight: 700;
            color: #1e293b;
        }

        #modalTambah .btn-close {
            margin-left: auto;
            padding: 10px;
            border: none;
            background-color: transparent;
            opacity: 0.6;
            cursor: pointer;
            box-shadow: none;
        }

        #modalTambah .btn-close:hover {
            opacity: 1;
        }

        #modalTambah .modal-body {
            padding: 25px 26px;
            background: white;
        }

        /* FORM */
        #modalTambah .job-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        #modalTambah .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        #modalTambah .form-group.full-width {
            grid-column: 1 / -1;
        }

        #modalTambah .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin: 0;
        }

        #modalTambah .form-group input,
        #modalTambah .form-group select,
        #modalTambah .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #334155;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
            transition: 0.2s;
        }

        #modalTambah .form-group input:focus,
        #modalTambah .form-group select:focus,
        #modalTambah .form-group textarea:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        #modalTambah .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        #modalTambah .btn-submit {
            grid-column: 1 / -1;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        #modalTambah .btn-submit:hover {
            background: #1d4ed8;
        }

        @media (max-width: 600px) {
            #modalTambah .job-form {
                grid-template-columns: 1fr;
            }

            #modalTambah .form-group.full-width,
            #modalTambah .btn-submit {
                grid-column: 1;
            }
        }
        [id^="modalEdit"] .modal-dialog {
            max-width: 750px;
        }

        [id^="modalEdit"] .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }

        [id^="modalEdit"] .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 26px;
        }

        [id^="modalEdit"] .modal-body {
            padding: 25px 26px;
        }

        [id^="modalEdit"] .job-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        [id^="modalEdit"] .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        [id^="modalEdit"] .form-group.full-width,
        [id^="modalEdit"] .btn-submit {
            grid-column: 1 / -1;
        }

        [id^="modalEdit"] .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        [id^="modalEdit"] .form-group input,
        [id^="modalEdit"] .form-group select,
        [id^="modalEdit"] .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            [id^="modalEdit"] .job-form {
                grid-template-columns: 1fr;
            }

            [id^="modalEdit"] .form-group.full-width,
            [id^="modalEdit"] .btn-submit {
                grid-column: 1;
            }
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

            <a href="jobs.php" class="active">
                <span class="menu-icon">▤</span>
                <span>Lowongan Pekerjaan</span>
            </a>

            <a href="applicants.php">
                <span class="menu-icon">♙</span>
                <span>Data Pelamar</span>
            </a>

            <a href="applications.php">
                <span class="menu-icon">▣</span>
                <span>Data Lamaran</span>
            </a>

        </nav>

        <div class="menu-title">AKUN</div>

        <nav class="menu">
            <a href="../../backend/logout.php"">
                <span class="menu-icon">⇥</span>
                <span>Logout</span>
            </a>
        </nav>

    </aside>

    <!-- Main -->
    <main class="main">

        <header class="topbar">

            <div class="page-title">
                <h1>Lowongan Pekerjaan</h1>
                <p>Kelola lowongan pekerjaan perusahaan.</p>
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
                <div class="stat-icon">▤</div>
                <p>Total Lowongan</p>
                <h2><?= $total_jobs ?></h2>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✓</div>
                <p>Lowongan Aktif</p>
                <h2><?= $active_jobs ?></h2>
            </div>

            <div class="stat-card">
                <div class="stat-icon">◷</div>
                <p>Lowongan Ditutup</p>
                <h2><?= $closed_jobs ?></h2>
            </div>

        </section>

        <!-- Jobs Table -->
        <section class="table-container">

            <div class="table-header">

                <div>
                    <h3>Daftar Lowongan</h3>
                    <p>Daftar posisi pekerjaan yang tersedia di perusahaan.</p>
                </div>

                <button class="btn" type="button"  data-bs-toggle="modal"  data-bs-target="#modalTambah">
                    + Tambah Lowongan
                </button>

            </div>

            <div class="toolbar">

                <div class="search">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Cari posisi atau departemen..."
                    >
                </div>

                <select id="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="published">Published</option>
                    <option value="closed">Closed</option>
                    <option value="draft">Draft</option>
                </select>

            </div>

            <table id="jobsTable">

                <thead>
                    <tr>
                        <th>POSISI</th>
                        <th>DEPARTEMEN</th>
                        <th>LOKASI</th>
                        <th>TIPE PEKERJAAN</th>
                        <th>TANGGAL DIBUKA</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($data = mysqli_fetch_assoc($query_jobs)) : ?>

                        <tr data-status="<?= htmlspecialchars($data['status']) ?>>
                            <td><?= htmlspecialchars($data['title']) ?></td>

                            <td><?= htmlspecialchars($data['category'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($data['location'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($data['employment_type']) ?></td>

                            <td><?= htmlspecialchars($data['deadline'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($data['status']) ?></td>

                            <td>
                                <button
                                    type="button"
                                    class="btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEdit<?= (int) $data['id'] ?>">
                                    Edit
                                </button>

                                <form action="../../backend/admin/jobs_delete.php"
                                    method="POST"
                                    onsubmit="return confirm('Apakah kamu yakin ingin menghapus lowongan ini?')"
                                    style="display: inline;">

                                    <input type="hidden" name="id"
                                        value="<?= (int) $data['id'] ?>">

                                    <button type="submit" class="btn btn-danger">
                                        Hapus
                                    </button>

                                </form>

                                <!-- MODAL EDIT -->
                                <div class="modal fade" id="modalEdit<?= (int) $data['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Lowongan</h5>

                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal">
                                                </button>
                                            </div>

                                            <div class="modal-body">

                                                <form action="../../backend/admin/jobs_edit.php"
                                                    method="POST"
                                                    class="job-form">

                                                    <input type="hidden" name="id"
                                                        value="<?= (int) $data['id'] ?>">

                                                    <div class="form-group">
                                                        <label>Nama Lowongan</label>
                                                        <input type="text" name="title"
                                                            value="<?= htmlspecialchars($data['title']) ?>" required>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Kategori</label>
                                                        <input type="text" name="category"
                                                            value="<?= htmlspecialchars($data['category'] ?? '') ?>">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Tipe Pekerjaan</label>
                                                        <select name="employment_type" required>
                                                            <option value="full_time" <?= $data['employment_type'] == 'full_time' ? 'selected' : '' ?>>Full Time</option>
                                                            <option value="part_time" <?= $data['employment_type'] == 'part_time' ? 'selected' : '' ?>>Part Time</option>
                                                            <option value="internship" <?= $data['employment_type'] == 'internship' ? 'selected' : '' ?>>Internship</option>
                                                            <option value="contract" <?= $data['employment_type'] == 'contract' ? 'selected' : '' ?>>Contract</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Lokasi</label>
                                                        <input type="text" name="location"
                                                            value="<?= htmlspecialchars($data['location'] ?? '') ?>">
                                                    </div>

                                                    <div class="form-group full-width">
                                                        <label>Deskripsi</label>
                                                        <textarea name="description" required><?= htmlspecialchars($data['description']) ?></textarea>
                                                    </div>

                                                    <div class="form-group full-width">
                                                        <label>Persyaratan</label>
                                                        <textarea name="requirements"><?= htmlspecialchars($data['requirements'] ?? '') ?></textarea>
                                                    </div>

                                                    <div class="form-group full-width">
                                                        <label>Tanggung Jawab</label>
                                                        <textarea name="responsibilities"><?= htmlspecialchars($data['responsibilities'] ?? '') ?></textarea>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Gaji Minimum</label>
                                                        <input type="number" name="salary_min" min="0"
                                                            value="<?= htmlspecialchars($data['salary_min'] ?? '') ?>">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Gaji Maksimum</label>
                                                        <input type="number" name="salary_max" min="0"
                                                            value="<?= htmlspecialchars($data['salary_max'] ?? '') ?>">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Deadline</label>
                                                        <input type="date" name="deadline"
                                                            value="<?= htmlspecialchars($data['deadline'] ?? '') ?>">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select name="status" required>
                                                            <option value="draft" <?= $data['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                            <option value="published" <?= $data['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                                                            <option value="closed" <?= $data['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                                                        </select>
                                                    </div>

                                                    <button type="submit" class="btn-submit">
                                                        Simpan Perubahan
                                                    </button>

                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

            <div id="emptyMessage" class="empty" style="display:none">
                Data lowongan tidak ditemukan.
            </div>

        </section>

         <!-- MODAL TAMBAH LOWONGAN -->
        <div class="modal fade" id="modalTambah" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5>Tambah Lowongan</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>

                    <div class="modal-body">

                        
                        <form action="../../backend/admin/jobs_add.php" method="POST" class="job-form">

                            <div class="form-group">
                                <label>Nama Lowongan</label>
                                <input type="text" name="title" placeholder="Contoh: Frontend Developer" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori</label>
                                <input type="text" name="category" placeholder="Contoh: IT">
                            </div>

                            <div class="form-group">
                                <label>Tipe Pekerjaan</label>
                                <select name="employment_type" required>
                                    <option value="full_time">Full Time</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="internship">Internship</option>
                                    <option value="contract">Contract</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Lokasi</label>
                                <input type="text" name="location" placeholder="Contoh: Yogyakarta">
                            </div>

                            <div class="form-group full-width">
                                <label>Deskripsi</label>
                                <textarea name="description" placeholder="Jelaskan posisi pekerjaan..." required></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Persyaratan</label>
                                <textarea name="requirements" placeholder="Tuliskan persyaratan pekerjaan..."></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Tanggung Jawab</label>
                                <textarea name="responsibilities" placeholder="Tuliskan tanggung jawab pekerjaan..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Gaji Minimum</label>
                                <input type="number" name="salary_min" min="0" placeholder="Contoh: 3000000">
                            </div>

                            <div class="form-group">
                                <label>Gaji Maksimum</label>
                                <input type="number" name="salary_max" min="0" placeholder="Contoh: 5000000">
                            </div>

                            <div class="form-group">
                                <label>Deadline</label>
                                <input type="date" name="deadline">
                            </div>

                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" required>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-submit">
                                Simpan Lowongan
                            </button>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>

</div>

<script>

    // Pencarian dan filter frontend

    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('#jobsTable tbody tr');

    function filterJobs() {

        let search = searchInput.value.toLowerCase();
        let status = statusFilter.value;
        let visible = 0;

        rows.forEach(function(row) {

            let text = row.innerText.toLowerCase();
            let rowStatus = row.dataset.status;

            let matchSearch = text.includes(search);
            let matchStatus = status === '' || rowStatus === status;

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

    searchInput.addEventListener('input', filterJobs);
    statusFilter.addEventListener('change', filterJobs);

    function showNotice() {
        alert('Tombol ini belum dihubungkan dengan backend.');
        return false;
    }

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
