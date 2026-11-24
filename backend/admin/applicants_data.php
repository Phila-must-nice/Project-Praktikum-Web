<?php

require_once __DIR__ . '/../connection.php';

// Total seluruh pelamar
$query_total = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'pelamar'
");

$total_applicants = mysqli_fetch_assoc($query_total)['total'];


// Total pelamar yang sudah memiliki profil
$query_complete = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM users
    INNER JOIN applicant_profiles
        ON users.id = applicant_profiles.user_id
    WHERE users.role = 'pelamar'
");

$complete_applicants = mysqli_fetch_assoc($query_complete)['total'];


// Total pelamar yang belum memiliki profil
$incomplete_applicants = $total_applicants - $complete_applicants;


// Mengambil data pelamar
$query_applicants = mysqli_query($conn, "
    SELECT
        users.id,
        users.name,
        users.email,
        users.phone,
        users.created_at,
        applicant_profiles.education_level,
        applicant_profiles.institution,
        applicant_profiles.major

    FROM users

    LEFT JOIN applicant_profiles
        ON users.id = applicant_profiles.user_id

    WHERE users.role = 'pelamar'

    ORDER BY users.created_at DESC
");

?>