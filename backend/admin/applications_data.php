<?php
require_once __DIR__ . '/../connection.php';

// Total seluruh lamaran
$query_total = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
");
$total_lamaran = $query_total ? (int) mysqli_fetch_assoc($query_total)['total'] : 0;

// Lamaran yang menunggu review
$query_review = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'submitted'
");
$menunggu_review = $query_review ? (int) mysqli_fetch_assoc($query_review)['total'] : 0;

// Lamaran yang diterima
$query_accepted = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'accepted'
");
$lamaran_diterima = $query_accepted ? (int) mysqli_fetch_assoc($query_accepted)['total'] : 0;

// Ambil satu dokumen CV terbaru untuk setiap lamaran agar baris lamaran tidak berulang.
$query_applications = mysqli_query($conn, "
    SELECT
        a.id,
        a.status,
        a.admin_note,
        a.applied_at,
        u.name AS nama_pelamar,
        u.email AS email_pelamar,
        j.title AS nama_lowongan,
        d.id AS cv_document_id,
        d.original_name AS cv_original_name,
        d.uploaded_at AS cv_uploaded_at
    FROM applications a
    INNER JOIN users u
        ON a.applicant_id = u.id
    INNER JOIN jobs j
        ON a.job_id = j.id
    LEFT JOIN (
        SELECT application_id, MAX(id) AS latest_document_id
        FROM application_documents
        WHERE document_type = 'cv'
        GROUP BY application_id
    ) latest_cv
        ON latest_cv.application_id = a.id
    LEFT JOIN application_documents d
        ON d.id = latest_cv.latest_document_id
    ORDER BY a.applied_at DESC, a.id DESC
");

$applications = $query_applications
    ? mysqli_fetch_all($query_applications, MYSQLI_ASSOC)
    : [];
?>
