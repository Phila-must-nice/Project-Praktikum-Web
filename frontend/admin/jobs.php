
<?php
// Frontend Jobs - TalentTrack
// Data masih dummy, silakan hubungkan dengan backend.

session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan Pekerjaan | TalentTrack</title>

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

        @media(max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .table-header {
                align-items: flex-start;
                flex-direction: column;
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
            <a href="../logout.php">
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
                <h2>8</h2>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✓</div>
                <p>Lowongan Aktif</p>
                <h2>5</h2>
            </div>

            <div class="stat-card">
                <div class="stat-icon">◷</div>
                <p>Lowongan Ditutup</p>
                <h2>3</h2>
            </div>

        </section>

        <!-- Jobs Table -->
        <section class="table-container">

            <div class="table-header">

                <div>
                    <h3>Daftar Lowongan</h3>
                    <p>Daftar posisi pekerjaan yang tersedia di perusahaan.</p>
                </div>

                <button class="btn" onclick="alert('Hubungkan tombol ini dengan backend tambah lowongan.')">
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
                    <option value="Aktif">Aktif</option>
                    <option value="Ditutup">Ditutup</option>
                    <option value="Draft">Draft</option>
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

                    <tr data-status="Aktif">
                        <td class="job-name">Frontend Developer</td>
                        <td>Technology</td>
                        <td>Yogyakarta</td>
                        <td>Full Time</td>
                        <td>10/11/2026</td>
                        <td><span class="status active">Aktif</span></td>
                        <td><a href="#" class="action" onclick="return showNotice()">Edit</a></td>
                    </tr>

                    <tr data-status="Aktif">
                        <td class="job-name">UI/UX Designer</td>
                        <td>Product</td>
                        <td>Hybrid</td>
                        <td>Full Time</td>
                        <td>12/11/2026</td>
                        <td><span class="status active">Aktif</span></td>
                        <td><a href="#" class="action" onclick="return showNotice()">Edit</a></td>
                    </tr>

                    <tr data-status="Aktif">
                        <td class="job-name">Backend Developer</td>
                        <td>Technology</td>
                        <td>Yogyakarta</td>
                        <td>Full Time</td>
                        <td>14/11/2026</td>
                        <td><span class="status active">Aktif</span></td>
                        <td><a href="#" class="action" onclick="return showNotice()">Edit</a></td>
                    </tr>

                    <tr data-status="Ditutup">
                        <td class="job-name">Digital Marketing</td>
                        <td>Marketing</td>
                        <td>Remote</td>
                        <td>Contract</td>
                        <td>01/10/2026</td>
                        <td><span class="status closed">Ditutup</span></td>
                        <td><a href="#" class="action" onclick="return showNotice()">Lihat</a></td>
                    </tr>

                    <tr data-status="Draft">
                        <td class="job-name">Data Analyst</td>
                        <td>Business Intelligence</td>
                        <td>Yogyakarta</td>
                        <td>Full Time</td>
                        <td>-</td>
                        <td><span class="status draft">Draft</span></td>
                        <td><a href="#" class="action" onclick="return showNotice()">Edit</a></td>
                    </tr>

                </tbody>

            </table>

            <div id="emptyMessage" class="empty" style="display:none">
                Data lowongan tidak ditemukan.
            </div>

        </section>

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

</body>
</html>
