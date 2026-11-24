<?php

require_once __DIR__ . "/../connection.php";
$id_admin = $_SESSION['user_id'];

$query_admin = mysqli_query($conn, "SELECT name FROM users WHERE id = '$id_admin'");

$data_admin = mysqli_fetch_assoc($query_admin);

$query_pelamar = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'pelamar'");

$pelamar = mysqli_fetch_assoc($query_pelamar)['total'];

$query_lowongan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM jobs WHERE status = 'published'");

$lowongan = mysqli_fetch_assoc($query_lowongan)['total'];

$query_total_lamaran = mysqli_query($conn, "SELECT COUNT(*) AS total FROM applications");

$lamaran = mysqli_fetch_assoc($query_total_lamaran)['total'];

$query_review = mysqli_query($conn, "SELECT COUNT(*) AS total FROM applications WHERE status = 'submitted'");

$review = mysqli_fetch_assoc($query_review)['total'];

$query_lamaran = mysqli_query($conn, "
    SELECT
        applications.id,
        users.name AS applicant_name,
        jobs.title AS job_title,
        applications.applied_at,
        applications.status
    FROM applications
    INNER JOIN users
        ON applications.applicant_id = users.id
    INNER JOIN jobs
        ON applications.job_id = jobs.id
    ORDER BY applications.applied_at DESC
    LIMIT 5
");

?>