<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "talenttrack";
$conn = mysqli_connect($hostname, $username, $password, $database);

if(!$conn){
    die("koneksi gagal: ". mysqli_connect_error());
}else{
    echo "koneksi berhasil";
}

?>
