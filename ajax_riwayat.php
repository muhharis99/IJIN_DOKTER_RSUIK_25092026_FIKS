<?php

header('Content-Type: application/json; charset=utf-8');

$hostname = "192.168.0.33";
$username = "admin";
$password = "admin3dp";
$database = "rsiklaten";

$koneksi = mysqli_connect($hostname, $username, $password, $database);

if (mysqli_connect_errno()) {
    echo json_encode([
        "draw" => 1, "recordsTotal" => 0,
        "recordsFiltered" => 0, "data" => [],
        "error" => mysqli_connect_error()
    ]);
    exit();
}

mysqli_set_charset($koneksi, 'utf8');

$draw   = isset($_POST['draw'])   ? (int)$_POST['draw']   : 1;
$start  = isset($_POST['start'])  ? (int)$_POST['start']  : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;

if ($length > 100) $length = 100;

$noReg      = trim($_POST['no_reg']      ?? '');
$noTelp     = trim($_POST['no_telp']     ?? '');
$poli       = trim($_POST['poli']        ?? '');
$namaDokter = trim($_POST['nama_dokter'] ?? '');
$tglKirim   = trim($_POST['tgl_kirim']   ?? '');
$status     = trim($_POST['status']      ?? '');

$conditions = [];
$bindTypes  = '';
$bindValues = [];

if ($noReg !== '') {
    $conditions[] = 'b.no_reg LIKE ?';
    $bindTypes   .= 's';
    $bindValues[] = "%$noReg%";
}
if ($noTelp !== '') {
    $conditions[] = 'b.no_hp LIKE ?';
    $bindTypes   .= 's';
    $bindValues[] = "%$noTelp%";
}
if ($poli !== '') {
    $conditions[] = 'b.nama_poli LIKE ?';
    $bindTypes   .= 's';
    $bindValues[] = "%$poli%";
}
if ($namaDokter !== '') {
    $conditions[] = 'b.nama_dokter LIKE ?';
    $bindTypes   .= 's';
    $bindValues[] = "%$namaDokter%";
}
if ($tglKirim !== '') {
    $conditions[] = 'b.tgl_kirim_pesan >= ? AND b.tgl_kirim_pesan < DATE_ADD(?, INTERVAL 1 DAY)';
    $bindTypes   .= 'ss';
    $bindValues[] = $tglKirim . ' 00:00:00';
    $bindValues[] = $tglKirim;
}
if ($status !== '') {
    $conditions[] = 'b.status = ?';
    $bindTypes   .= 's';
    $bindValues[] = $status;
}

$whereSQL = count($conditions) > 0
    ? 'WHERE ' . implode(' AND ', $conditions)
    : '';

session_start();
$cacheKey = 'total_batal_wa_' . $database;
$cacheTTL = 300;
if (!isset($_SESSION[$cacheKey]) || (time() - ($_SESSION[$cacheKey . '_ts'] ?? 0)) > $cacheTTL) {
    $rTotal = mysqli_query($koneksi,
        "SELECT TABLE_ROWS FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = '$database'
           AND TABLE_NAME = 'batal_praktek_detil_wa'
         LIMIT 1"
    );
    $approx = (int)(mysqli_fetch_row($rTotal)[0] ?? 0);
    if ($approx === 0) {
        $rExact = mysqli_query($koneksi, "SELECT COUNT(*) FROM batal_praktek_detil_wa");
        $approx = (int)mysqli_fetch_row($rExact)[0];
    }
    $_SESSION[$cacheKey]         = $approx;
    $_SESSION[$cacheKey . '_ts'] = time();
}
$totalAll = $_SESSION[$cacheKey];

$sql = "SELECT SQL_CALC_FOUND_ROWS
            b.no_reg,
            b.no_hp,
            b.nama_pasien,
            b.pesan,
            b.nama_poli,
            b.nama_dokter,
            b.tgl_kirim_pesan,
            b.status
        FROM batal_praktek_detil_wa b
        $whereSQL
        ORDER BY b.id DESC
        LIMIT ?, ?";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    echo json_encode(["draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => mysqli_error($koneksi)]);
    exit();
}

$bindTypes   .= 'ii';
$bindValues[] = $start;
$bindValues[] = $length;

if (!empty($bindValues)) {
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindValues);
}

mysqli_stmt_execute($stmt);
$dataResult = mysqli_stmt_get_result($stmt);

$foundResult  = mysqli_query($koneksi, "SELECT FOUND_ROWS()");
$totalFilter  = (int)mysqli_fetch_row($foundResult)[0];

$rows = [];
while ($row = mysqli_fetch_assoc($dataResult)) {
    if ($row['status'] == 1) {
        $badge = "<span class='badge-status sent'><i class='bi bi-check-circle-fill'></i> Terkirim</span>";
    } elseif ($row['status'] == 2) {
        $badge = "<span class='badge-status failed'><i class='bi bi-x-circle-fill'></i> Gagal</span>";
    } elseif ($row['status'] == 3) {
        $badge = "<span class='badge-status unknown'><i class='bi bi-shield-exclamation'></i> Ditahan</span>";
    } elseif ($row['status'] == 4) {
        $badge = "<span class='badge-status failed'><i class='bi bi-person-slash'></i> Opt-out</span>";
    } elseif ($row['status'] == 5) {
        $badge = "<span class='badge-status unknown'><i class='bi bi-copy'></i> Duplikat</span>";
    } else {
        $badge = "<span class='badge-status unknown'><i class='bi bi-dash-circle'></i> N/A</span>";
    }

    $rows[] = [
        "<span style='font-size:.8rem;font-weight:600;'>" . htmlspecialchars($row['no_reg'])       . "</span>",
        htmlspecialchars($row['no_hp']),
        "<span style='font-weight:500;'>"                  . htmlspecialchars($row['nama_pasien']) . "</span>",
        "<span style='font-size:.8rem;color:#555;'>" . nl2br(htmlspecialchars($row['pesan'])) . "</span>",
        "<span style='background:#e8f5f0;color:#0d6e4f;padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600;white-space:nowrap;'>" . htmlspecialchars($row['nama_poli'])    . "</span>",
        "<span style='font-size:.85rem;'>"                 . htmlspecialchars($row['nama_dokter']) . "</span>",
        "<span style='font-size:.82rem;white-space:nowrap;'>" . htmlspecialchars($row['tgl_kirim_pesan']) . "</span>",
        $badge,
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);

$response = [
    "draw"            => $draw,
    "recordsTotal"    => $totalAll,
    "recordsFiltered" => $totalFilter,
    "data"            => $rows,
];

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    $json = json_encode($response);
    header('Content-Encoding: gzip');
    echo gzencode($json, 6);
} else {
    echo json_encode($response);
}