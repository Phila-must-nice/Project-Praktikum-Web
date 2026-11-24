
<?php
session_start();

// Memeriksa hak akses admin
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../../frontend/login.php");
    exit;
}

require_once __DIR__ . '/../connection.php';

// Memastikan request berasal dari form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../frontend/admin/applications.php");
    exit;
}

// Mengambil data dari form
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$status = $_POST['status'] ?? '';
$admin_note = trim($_POST['admin_note'] ?? '');

// Memeriksa ID
if ($id <= 0) {
    header("Location: ../../frontend/admin/applications.php?pesan=id_tidak_valid");
    exit;
}

// Memeriksa status yang diperbolehkan
$status_valid = [
    'submitted',
    'administrative_review',
    'interview',
    'accepted',
    'rejected',
    'withdrawn'
];

if (!in_array($status, $status_valid, true)) {
    header("Location: ../../frontend/admin/applications.php?pesan=status_tidak_valid");
    exit;
}

// Mengamankan input catatan dan status
$status = mysqli_real_escape_string($conn, $status);

$admin_note = mysqli_real_escape_string(
    $conn,
    $admin_note
);

// Memeriksa apakah ID lamaran tersedia
$query_cek = mysqli_query($conn, "
    SELECT id
    FROM applications
    WHERE id = $id
");

if (!$query_cek) {
    die("Gagal memeriksa data lamaran: " . mysqli_error($conn));
}

if (mysqli_num_rows($query_cek) == 0) {
    header("Location: ../../frontend/admin/applications.php?pesan=id_tidak_valid");
    exit;
}

// Memperbarui status dan catatan admin
$query = mysqli_query($conn, "
    UPDATE applications
    SET
        status = '$status',
        admin_note = '$admin_note'
    WHERE id = $id
");

// Memeriksa hasil update
if ($query) {
    header("Location: ../../frontend/admin/applications.php?pesan=berhasil_diubah");
    exit;
} else {
    die("Gagal memperbarui lamaran: " . mysqli_error($conn));
}
?>
