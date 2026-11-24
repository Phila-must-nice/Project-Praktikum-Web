<?php
session_start();

// CV dapat diakses admin dari dashboard lamaran. Pelamar mengakses CV miliknya
// sendiri melalui detail.php yang memiliki pemeriksaan applicant_id.
if (
    !isset($_SESSION['user_id'], $_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../backend/connection.php';

$applicationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$applicationId || $applicationId <= 0) {
    http_response_code(400);
    exit('ID lamaran tidak valid.');
}

$stmt = $conn->prepare("
    SELECT d.original_name, d.file_path, d.mime_type
    FROM application_documents d
    INNER JOIN applications a ON a.id = d.application_id
    WHERE a.id = ? AND d.document_type = 'cv'
    ORDER BY d.id DESC
    LIMIT 1
");
if (!$stmt) {
    http_response_code(500);
    exit('Tidak dapat menyiapkan data CV.');
}

$stmt->bind_param('i', $applicationId);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document || empty($document['file_path'])) {
    http_response_code(404);
    exit('CV untuk lamaran ini tidak ditemukan.');
}

// Hanya izinkan file dari direktori penyimpanan CV yang digunakan proyek.
$relativePath = ltrim(str_replace('\\', '/', $document['file_path']), '/');
$allowedPrefixes = [
    'frontend/pelamar/cv-storage/',
    'uploads/cv/' // Kompatibilitas dengan CV yang mungkin sudah diunggah sebelumnya.
];
$pathAllowed = false;
foreach ($allowedPrefixes as $prefix) {
    if (str_starts_with($relativePath, $prefix)) {
        $pathAllowed = true;
        break;
    }
}

$projectRoot = realpath(__DIR__ . '/../../');
$fullPath = ($pathAllowed && $projectRoot)
    ? realpath($projectRoot . DIRECTORY_SEPARATOR . $relativePath)
    : false;

if (
    !$fullPath || !$projectRoot ||
    !str_starts_with($fullPath, $projectRoot . DIRECTORY_SEPARATOR) ||
    !is_file($fullPath) || !is_readable($fullPath)
) {
    http_response_code(404);
    exit('File CV tidak tersedia.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($fullPath);
if ($detectedMime !== 'application/pdf') {
    http_response_code(415);
    exit('Dokumen bukan file PDF yang valid.');
}

$downloadName = basename(str_replace('\\', '/', $document['original_name'] ?: 'CV.pdf'));
$safeAsciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName);
$disposition = isset($_GET['download']) && $_GET['download'] === '1' ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Content-Disposition: ' . $disposition . '; filename="' . ($safeAsciiName ?: 'CV.pdf') . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
?>
