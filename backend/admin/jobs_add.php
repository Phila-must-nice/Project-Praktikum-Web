<?php

session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../../frontend/login.php");
    exit;
}
require_once __DIR__ . '/../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $employment_type = mysqli_real_escape_string($conn, $_POST['employment_type']);
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $requirements = mysqli_real_escape_string($conn, trim($_POST['requirements']));
    $responsibilities = mysqli_real_escape_string($conn, trim($_POST['responsibilities']));

    $salary_min = $_POST['salary_min'] !== ''
        ? (float) $_POST['salary_min']
        : "NULL";

    $salary_max = $_POST['salary_max'] !== ''
        ? (float) $_POST['salary_max']
        : "NULL";

    $deadline = $_POST['deadline'] !== ''
        ? "'" . mysqli_real_escape_string($conn, $_POST['deadline']) . "'"
        : "NULL";


    $status = mysqli_real_escape_string($conn, $_POST['status']);


    $created_by = (int) $_SESSION['user_id'];

    $query = mysqli_query($conn, "
        INSERT INTO jobs 
        (title,category,employment_type,
        location,description,requirements,
        responsibilities,salary_min,salary_max,
        deadline,status,created_by)
        VALUES (
            '$title','$category','$employment_type',
            '$location','$description','$requirements',
            '$responsibilities',$salary_min,$salary_max,
            $deadline,'$status',$created_by
        )
    ");

    if ($query) {
        header("Location: ../../frontend/admin/jobs.php?pesan=ditambahkan");
        exit;
    } else {
        echo "Gagal menambahkan lowongan: " . mysqli_error($conn);
    }
}else {
    header("Location: ../../frontend/admin/jobs.php");
    exit;
}

?>