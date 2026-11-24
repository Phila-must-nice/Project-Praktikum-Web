
<?php
// Frontend Applications - TalentTrack
// Data masih dummy, hubungkan dengan backend nantinya.

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

            <a href="../logout.php">
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

                <p>Total Lamaran</p>

                <h2>156</h2>

            </div>

            <div class="stat-card">

                <div class="stat-icon orange">◷</div>

                <p>Menunggu Review</p>

                <h2>42</h2>

            </div>

            <div class="stat-card">

                <div class="stat-icon green">✓</div>

                <p>Lamaran Diterima</p>

                <h2>18</h2>

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
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>

                </thead>

                <tbody>

                    <tr data-status="submitted">

                        <td class="applicant">
                            Aditya Pratama
                        </td>

                        <td class="job-name">
                            Frontend Developer
                        </td>

                        <td>21/11/2026</td>

                        <td>
                            <span class="status submitted">
                                Dikirim
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                    <tr data-status="administrative_review">

                        <td class="applicant">
                            Nadia Putri
                        </td>

                        <td class="job-name">
                            UI/UX Designer
                        </td>

                        <td>20/11/2026</td>

                        <td>
                            <span class="status administrative_review">
                                Review Administrasi
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                    <tr data-status="interview">

                        <td class="applicant">
                            Rizky Ramadhan
                        </td>

                        <td class="job-name">
                            Backend Developer
                        </td>

                        <td>19/11/2026</td>

                        <td>
                            <span class="status interview">
                                Interview
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                    <tr data-status="accepted">

                        <td class="applicant">
                            Siti Rahma
                        </td>

                        <td class="job-name">
                            Digital Marketing
                        </td>

                        <td>17/11/2026</td>

                        <td>
                            <span class="status accepted">
                                Diterima
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                    <tr data-status="rejected">

                        <td class="applicant">
                            Fajar Maulana
                        </td>

                        <td class="job-name">
                            Frontend Developer
                        </td>

                        <td>15/11/2026</td>

                        <td>
                            <span class="status rejected">
                                Ditolak
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                    <tr data-status="withdrawn">

                        <td class="applicant">
                            Dewi Anggraini
                        </td>

                        <td class="job-name">
                            UI/UX Designer
                        </td>

                        <td>14/11/2026</td>

                        <td>
                            <span class="status withdrawn">
                                Dibatalkan
                            </span>
                        </td>

                        <td>
                            <a href="#" class="action" onclick="return showNotice()">
                                Detail
                            </a>
                        </td>

                    </tr>

                </tbody>

            </table>

            <div id="emptyMessage" class="empty" style="display:none">
                Data lamaran tidak ditemukan.
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
