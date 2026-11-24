<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../backend/admin/applicants_detail_data.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pelamar - TalentTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 28px;
            color: #0f172a;
        }

        section {
            background: #ffffff;
            padding: 25px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        section h2 {
            font-size: 19px;
            margin: 0 0 18px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        section p {
            font-size: 14px;
            line-height: 1.7;
            color: #475569;
            margin: 12px 0;
        }

        section p strong {
            display: inline-block;
            min-width: 165px;
            color: #334155;
        }

        /* TABEL RIWAYAT LAMARAN */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 14px 12px;
            text-align: left;
            color: #64748b;
            white-space: nowrap;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 15px 12px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            line-height: 1.6;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            h1 {
                font-size: 23px;
            }

            section {
                padding: 18px;
            }

            section p strong {
                display: block;
                min-width: auto;
            }

            table {
                min-width: 750px;
            }
        }
    </style>
</head>
<body>

    <main class="container">
        <a href="applicants.php">Kembali ke Data Pelamar</a>
        <h1>Detail Pelamar</h1>

        <!-- Informasi Pribadi -->
        <section>
            <h2>Informasi Pribadi</h2>

            <p>
                <strong>Nama:</strong>
                <?= htmlspecialchars($pelamar['name']) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($pelamar['email']) ?>
            </p>

            <p>
                <strong>Nomor Telepon:</strong>
                <?= htmlspecialchars($pelamar['phone'] ?? '-') ?>
            </p>

            <p>
                <strong>Tanggal Registrasi:</strong>
                <?= htmlspecialchars($pelamar['created_at']) ?>
            </p>
        </section>

        <!-- Informasi Pendidikan -->
        <section>
            <h2>Informasi Pendidikan</h2>

            <p>
                <strong>Tingkat Pendidikan:</strong>
                <?= htmlspecialchars($pelamar['education_level'] ?? '-') ?>
            </p>

            <p>
                <strong>Institusi:</strong>
                <?= htmlspecialchars($pelamar['institution'] ?? '-') ?>
            </p>

            <p>
                <strong>Jurusan:</strong>
                <?= htmlspecialchars($pelamar['major'] ?? '-') ?>
            </p>

            <p>
                <strong>Alamat:</strong>
                <?= nl2br(htmlspecialchars($pelamar['address'] ?? '-')) ?>
            </p>

            <p>
                <strong>Bio:</strong>
                <?= nl2br(htmlspecialchars($pelamar['bio'] ?? '-')) ?>
            </p>
        </section>

        <div class="table-wrapper">
        <!-- Tabel riwayat lamaran yang sebelumnya -->
            <!-- Riwayat Lamaran -->
            <section>
                <h2>Riwayat Lamaran</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Posisi</th>
                            <th>Tanggal Melamar</th>
                            <th>Status</th>
                            <th>Cover Letter</th>
                            <th>Catatan Admin</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (mysqli_num_rows($query_lamaran) > 0) : ?>

                            <?php while ($lamaran = mysqli_fetch_assoc($query_lamaran)) : ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($lamaran['nama_pekerjaan']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($lamaran['applied_at']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($lamaran['status']) ?>
                                    </td>

                                    <td>
                                        <?= nl2br(htmlspecialchars($lamaran['cover_letter'] ?? '-')) ?>
                                    </td>

                                    <td>
                                        <?= nl2br(htmlspecialchars($lamaran['admin_note'] ?? '-')) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else : ?>
                            <tr>
                                <td colspan="5">Pelamar belum mengirim lamaran.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>

    </main>

</body>
</html>