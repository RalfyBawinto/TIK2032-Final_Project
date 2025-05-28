<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
header('Content-Type: application/json');

// Koneksi database
$conn = new mysqli("localhost", "root", "", "futsal_db");
if ($conn->connect_error) {
  echo json_encode(['error' => 'Koneksi database gagal.']);
  exit;
}

// Tangani permintaan berdasarkan `action` jika ada
$action = $_GET['action'] ?? null;

// ✅ 1. Buat pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
  $input = file_get_contents("php://input");
  $data = json_decode($input, true);

  $lapangan_id = $data['lapangan_id'] ?? null;
  $tanggal = $data['tanggal'] ?? null;
  $waktu_mulai = $data['waktu_mulai'] ?? null;
  $waktu_selesai = $data['waktu_selesai'] ?? null;

  if (!$lapangan_id || !$tanggal || !$waktu_mulai || !$waktu_selesai) {
    echo json_encode(['error' => 'Data tidak lengkap.']);
    exit;
  }

  $today = date('Y-m-d');
  if ($tanggal < $today) {
    echo json_encode(['error' => 'Tanggal pemesanan tidak boleh sebelum hari ini.']);
    exit;
  }

  if ($waktu_mulai >= $waktu_selesai) {
    echo json_encode(['error' => 'Waktu selesai harus lebih dari waktu mulai.']);
    exit;
  }

  // Ambil harga lapangan
  $stmt = $conn->prepare("SELECT harga FROM lapangan WHERE id = ?");
  $stmt->bind_param("i", $lapangan_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $lapangan = $res->fetch_assoc();

  if (!$lapangan) {
    echo json_encode(['error' => 'Lapangan tidak ditemukan.']);
    exit;
  }

  $harga_per_jam = (int)$lapangan['harga'];
  $start = new DateTime($waktu_mulai);
  $end = new DateTime($waktu_selesai);
  $interval = $start->diff($end);
  $durasi_jam = (int)$interval->format('%h');
  $total_harga = $harga_per_jam * $durasi_jam;

  $user_id = $_SESSION['user_id'];

  // Simpan pemesanan
  $stmt = $conn->prepare("INSERT INTO pemesanan (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, total_harga, status) VALUES (?, ?, ?, ?, ?, ?, 'Dibooking')");
  $stmt->bind_param("iisssi", $user_id, $lapangan_id, $tanggal, $waktu_mulai, $waktu_selesai, $total_harga);

  if ($stmt->execute()) {
    // Update status lapangan
    $update = $conn->prepare("UPDATE lapangan SET status = 'Dibooking' WHERE id = ?");
    $update->bind_param("i", $lapangan_id);
    $update->execute();

    echo json_encode(['success' => true, 'total_harga' => $total_harga]);
  } else {
    echo json_encode(['error' => 'Gagal menyimpan pemesanan.']);
  }

  $conn->close();
  exit;
}

// 🔄 2. Admin konfirmasi jadi Dipakai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'start') {
  $pemesanan_id = $_POST['pemesanan_id'] ?? null;

  if (!$pemesanan_id) {
    echo json_encode(['error' => 'ID pemesanan tidak ditemukan.']);
    exit;
  }

  // Ubah status pemesanan
  $stmt = $conn->prepare("UPDATE pemesanan SET status = 'Dipakai' WHERE id = ?");
  $stmt->bind_param("i", $pemesanan_id);
  $stmt->execute();

  // Ambil lapangan_id
  $stmt = $conn->prepare("SELECT lapangan_id FROM pemesanan WHERE id = ?");
  $stmt->bind_param("i", $pemesanan_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  $lapangan_id = $result['lapangan_id'] ?? null;

  if ($lapangan_id) {
    $stmt2 = $conn->prepare("UPDATE lapangan SET status = 'Dipakai' WHERE id = ?");
    $stmt2->bind_param("i", $lapangan_id);
    $stmt2->execute();
  }

  echo json_encode(['success' => true]);
  $conn->close();
  exit;
}

// ⏳ 3. Update otomatis jadi Tersedia (cron atau manual trigger)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete') {
  $sql = "
    SELECT id, lapangan_id FROM pemesanan 
    WHERE status = 'Dipakai' AND CONCAT(tanggal, ' ', waktu_selesai) < NOW()
  ";
  $result = $conn->query($sql);

  while ($row = $result->fetch_assoc()) {
    $pemesanan_id = $row['id'];
    $lapangan_id = $row['lapangan_id'];

    $conn->query("UPDATE pemesanan SET status = 'Selesai' WHERE id = $pemesanan_id");
    $conn->query("UPDATE lapangan SET status = 'Tersedia' WHERE id = $lapangan_id");
  }

  echo json_encode(['success' => true, 'message' => 'Lapangan dan pemesanan diperbarui.']);
  $conn->close();
  exit;
}

// Jika action tidak dikenali
echo json_encode(['error' => 'Aksi tidak valid.']);
$conn->close();
