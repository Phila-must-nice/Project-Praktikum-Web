
<?php
session_start();

// Memastikan yang mengakses adalah admin
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../../frontend/login.php");
    exit;
}

require_once __DIR__ . '/../connection.php';

// Memastikan data dikirim menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../frontend/admin/jobs.php");
    exit;
}

// Mengambil ID lowongan
$id = (int) $_POST['id'];

// Mengambil data dari form
$title = mysqli_real_escape_string($conn, trim($_POST['title']));
$category = mysqli_real_escape_string($conn, trim($_POST['category']));
$employment_type = $_POST['employment_type'];
$location = mysqli_real_escape_string($conn, trim($_POST['location']));
$description = mysqli_real_escape_string($conn, trim($_POST['description']));
$requirements = mysqli_real_escape_string($conn, trim($_POST['requirements']));
$responsibilities = mysqli_real_escape_string($conn, trim($_POST['responsibilities']));

// Memeriksa tipe pekerjaan
$jenis_pekerjaan = ['full_time', 'part_time', 'internship', 'contract'];

if (!in_array($employment_type, $jenis_pekerjaan)) {
    die("Tipe pekerjaan tidak valid.");
}

$employment_type = mysqli_real_escape_string($conn, $employment_type);

// Mengambil data gaji
$salary_min = $_POST['salary_min'] !== ''
    ? (float) $_POST['salary_min']
    : "NULL";

$salary_max = $_POST['salary_max'] !== ''
    ? (float) $_POST['salary_max']
    : "NULL";

// Memeriksa rentang gaji
if (
    $salary_min !== "NULL" &&
    $salary_max !== "NULL" &&
    $salary_min > $salary_max
) {
    die("Gaji minimum tidak boleh lebih besar dari gaji maksimum.");
}

// Mengambil deadline
$deadline = $_POST['deadline'] !== ''
    ? "'" . mysqli_real_escape_string($conn, $_POST['deadline']) . "'"
    : "NULL";

// Memeriksa status lowongan
$status_valid = ['draft', 'published', 'closed'];
$status = $_POST['status'];

if (!in_array($status, $status_valid)) {
    die("Status lowongan tidak valid.");
}

$status = mysqli_real_escape_string($conn, $status);

// Memperbarui data lowongan
$query = mysqli_query($conn, "
    UPDATE jobs SET
        title = '$title',
        category = '$category',
        employment_type = '$employment_type',
        location = '$location',
        description = '$description',
        requirements = '$requirements',
        responsibilities = '$responsibilities',
        salary_min = $salary_min,
        salary_max = $salary_max,
        deadline = $deadline,
        status = '$status'
    WHERE id = $id
");

if ($query) {
    header("Location: ../../frontend/admin/jobs.php?pesan=diubah");
    exit;
} else {
    echo "Gagal mengubah lowongan: " . mysqli_error($conn);
}
?>
