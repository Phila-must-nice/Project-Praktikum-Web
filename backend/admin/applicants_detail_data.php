<?php
require_once __DIR__ . '/../connection.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("ID pelamar tidak valid.");
}

// Mengambil data pelamar dan profilnya
$query_pelamar = mysqli_query($conn, "
    SELECT
        users.id,
        users.name,
        users.email,
        users.phone,
        users.created_at,
        applicant_profiles.address,
        applicant_profiles.education_level,
        applicant_profiles.institution,
        applicant_profiles.major,
        applicant_profiles.bio,
        applicant_profiles.photo_path
    FROM users
    LEFT JOIN applicant_profiles
        ON users.id = applicant_profiles.user_id
    WHERE users.id = $id
      AND users.role = 'pelamar'
");

if (!$query_pelamar) {
    die("Gagal mengambil data pelamar: " . mysqli_error($conn));
}

$pelamar = mysqli_fetch_assoc($query_pelamar);

if (!$pelamar) {
    die("Data pelamar tidak ditemukan.");
}

// Mengambil riwayat lamaran pelamar
$query_lamaran = mysqli_query($conn, "
    SELECT
        applications.id,
        applications.applied_at,
        applications.status,
        applications.cover_letter,
        applications.admin_note,
        jobs.title AS nama_pekerjaan
    FROM applications
    INNER JOIN jobs
        ON applications.job_id = jobs.id
    WHERE applications.applicant_id = $id
    ORDER BY applications.applied_at DESC
");

if (!$query_lamaran) {
    die("Gagal mengambil riwayat lamaran: " . mysqli_error($conn));
}
?>