<?php

require_once __DIR__ . '/../connection.php';

// Mengambil seluruh data lowongan
$query_jobs = mysqli_query($conn, "SELECT jobs.*, users.name AS nama_admin FROM jobs 
    INNER JOIN users
        ON jobs.created_by = users.id
    ORDER BY jobs.created_at DESC
");

// Total seluruh lowongan
$query_total_jobs = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jobs
");

$total_jobs = mysqli_fetch_assoc($query_total_jobs)['total'];


// Total lowongan aktif
$query_active_jobs = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jobs
    WHERE status = 'published'
");

$active_jobs = mysqli_fetch_assoc($query_active_jobs)['total'];


// Total lowongan ditutup
$query_closed_jobs = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jobs
    WHERE status = 'closed'
");

$closed_jobs = mysqli_fetch_assoc($query_closed_jobs)['total'];

?>