<?php
$host = '103.181.182.132';
$port = '9969';
$dbname = 'humas';
$username = 'admin';
$password = '@admin3dp';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$records = [
    [
        'id_daftar' => '99',
        'no_reg' => '0000001',
        'no_hp' => '0895376257021',
        'pesan' => 'Pesan contoh 1'
    ],
    [
        'id_daftar' => '100',
        'no_reg' => '0000002',
        'no_hp' => '08972870408',
        'pesan' => 'Pesan contoh 2'
    ],
    [
        'id_daftar' => '101',
        'no_reg' => '0000003',
        'no_hp' => '085876819479',
        'pesan' => 'Pesan contoh 3'
    ]
];

$sql = "INSERT INTO batal_praktek_detil (id_daftar, no_reg, no_hp, pesan) VALUES (:id_daftar, :no_reg, :no_hp, :pesan)";

$stmt = $pdo->prepare($sql);

try {
    foreach ($records as $data) {
        $stmt->execute($data);
    }
    echo "Data berhasil ditambahkan.";
} catch (PDOException $e) {
    echo "Gagal menambahkan data: " . $e->getMessage();
}
?>