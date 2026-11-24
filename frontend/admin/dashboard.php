<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../backend/admin/dashboard_data.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TalentTrack</title>

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
            margin-bottom: 35px;
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

        /* Welcome */
        .welcome {
            background: linear-gradient(120deg, #2563eb, #1e40af);
            padding: 28px 30px;
            border-radius: 12px;
            color: white;
            margin-bottom: 30px;
        }

        .welcome h2 {
            font-size: 21px;
            margin-bottom: 9px;
        }

        .welcome p {
            font-size: 13px;
            color: #dbeafe;
            line-height: 1.6;
        }

        /* Statistics */
        .section-title {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .blue {
            background: #eff6ff;
        }

        .green {
            background: #ecfdf5;
        }

        .purple {
            background: #f5f3ff;
        }

        .orange {
            background: #fff7ed;
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

        .view-all {
            text-decoration: none;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
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
        }

        .applicant {
            font-weight: 600;
            color: #334155;
        }

        .job-name {
            color: #64748b;
        }

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

        .empty {
            text-align: center;
            padding: 35px;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
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

            .topbar {
                gap: 15px;
            }

            .admin-info {
                display: none;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 650px;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 20px;
            }

            .welcome {
                padding: 22px;
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

            <a href="dashboard.php" class="active">
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

            <a href="applications.php">
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

        <!-- Topbar -->
        <header class="topbar">

            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Overview aktivitas rekrutmen perusahaan.</p>
            </div>

            <div class="admin-profile">

                <div class="avatar">
                    <?php echo strtoupper(substr($data_admin['name'] ?? 'A', 0, 1)); ?>
                </div>

                <div class="admin-info">
                    <h4>
                        <?php echo htmlspecialchars($data_admin['name'] ?? 'Administrator'); ?>
                    </h4>
                    <p>Administrator</p>
                </div>

            </div>

        </header>

        <!-- Welcome -->
        <section class="welcome">
            <h2>
                Selamat Datang,
                <?php echo htmlspecialchars($data_admin['name'] ?? 'Admin'); ?>!
            </h2>

            <p>
                Kelola proses rekrutmen, lowongan pekerjaan, dan data
                pelamar perusahaan melalui dashboard TalentTrack.
            </p>
        </section>

        <!-- Statistics -->
        <h3 class="section-title">Ringkasan Data</h3>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p>Total Pelamar</p>
                        <h2><?php echo $pelamar; ?></h2>
                    </div>
                    <div class="stat-icon blue">♙</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p>Lowongan Aktif</p>
                        <h2><?php echo $lowongan; ?></h2>
                    </div>
                    <div class="stat-icon green">▤</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p>Total Lamaran</p>
                        <h2><?php echo $lamaran; ?></h2>
                    </div>
                    <div class="stat-icon purple">▣</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p>Menunggu Review</p>
                        <h2><?php echo $review; ?></h2>
                    </div>
                    <div class="stat-icon orange">◷</div>
                </div>
            </div>

        </section>

        <!-- Recent Applications -->
        <section class="table-container">

            <div class="table-header">
                <h3>Lamaran Terbaru</h3>
                <a href="applications.php" class="view-all">Lihat Semua →</a>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>PELAMAR</th>
                        <th>POSISI</th>
                        <th>TANGGAL</th>
                        <th>STATUS</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($query_lamaran && mysqli_num_rows($query_lamaran) > 0) { ?>

                        <?php while ($row = mysqli_fetch_assoc($query_lamaran)) { ?>

                            <tr>
                                <td class="applicant">
                                    <?php echo htmlspecialchars($row['applicant_name']); ?>
                                </td>

                                <td class="job-name">
                                    <?php echo htmlspecialchars($row['job_title']); ?>
                                </td>

                                <td>
                                    <?php echo date('d/m/Y', strtotime($row['applied_at'])); ?>
                                </td>

                                <td>
                                    <span class="status <?php echo htmlspecialchars($row['status']); ?>">
                                        <?php
                                        $status = [
                                            'submitted' => 'Dikirim',
                                            'administrative_review' => 'Review Administrasi',
                                            'interview' => 'Interview',
                                            'accepted' => 'Diterima',
                                            'rejected' => 'Ditolak',
                                            'withdrawn' => 'Dibatalkan'
                                        ];

                                        echo $status[$row['status']] ?? $row['status'];
                                        ?>
                                    </span>
                                </td>
                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="4" class="empty">
                                Belum ada lamaran masuk.
                            </td>
                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

</body>
</html>