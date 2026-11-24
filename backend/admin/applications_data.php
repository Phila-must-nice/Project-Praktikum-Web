<?php
require_once __DIR__ . '/../connection.php';

// Total seluruh lamaran
$query_total = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
");

$total_lamaran = mysqli_fetch_assoc($query_total)['total'];

// Lamaran yang menunggu review
$query_review = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'submitted'
");

$menunggu_review = mysqli_fetch_assoc($query_review)['total'];

// Lamaran yang diterima
$query_accepted = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'accepted'
");

$lamaran_diterima = mysqli_fetch_assoc($query_accepted)['total'];

// Data seluruh lamaran
$query_applications = mysqli_query($conn, "
    SELECT
        applications.id,
        applications.status,
        applications.admin_note,
        applications.applied_at,
        users.name AS nama_pelamar,
        users.email AS email_pelamar,
        jobs.title AS nama_lowongan
    FROM applications
    INNER JOIN users
        ON applications.applicant_id = users.id
    INNER JOIN jobs
        ON applications.job_id = jobs.id
    ORDER BY applications.applied_at DESC
");
?>