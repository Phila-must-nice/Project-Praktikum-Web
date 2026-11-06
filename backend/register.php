<?php
include "connection.php";

$username = $_POST["username"];
$password_real = $_POST["password"];

$password_hash = password_hash($password_real, PASSWORD_DEFAULT);

$stmt = $connection->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password_hash);
if ($stmt->execute()) {
header("Location: index.php");
} else {
echo "Pendaftaran gagal: " . $connection->error;
}
$stmt->close();
?>