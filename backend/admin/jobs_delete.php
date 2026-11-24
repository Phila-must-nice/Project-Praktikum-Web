
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

if ($id <= 0) {
    header("Location: ../../frontend/admin/jobs.php?pesan=id_tidak_valid");
    exit;
}

// Memeriksa apakah lowongan sudah memiliki lamaran
$query_cek = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE job_id = $id
");

if (!$query_cek) {
    die("Gagal memeriksa lamaran: " . mysqli_error($conn));
}

$data_cek = mysqli_fetch_assoc($query_cek);

// Lowongan tidak boleh dihapus jika sudah memiliki lamaran
if ($data_cek['total'] > 0) {
    header("Location: ../../frontend/admin/jobs.php?pesan=memiliki_lamaran");
    exit;
}

// Menghapus lowongan
$query = mysqli_query($conn, "
    DELETE FROM jobs
    WHERE id = $id
");

if ($query && mysqli_affected_rows($conn) > 0) {
    header("Location: ../../frontend/admin/jobs.php?pesan=dihapus");
    exit;
} else {
    header("Location: ../../frontend/admin/jobs.php?pesan=gagal_hapus");
    exit;
}
?>
