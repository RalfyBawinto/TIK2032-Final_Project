<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['error' => 'Belum login']);
  exit;
}

$conn = new mysqli("localhost", "root", "", "futsal_db");
if ($conn->connect_error) {
  echo json_encode(['error' => 'Koneksi gagal: ' . $conn->connect_error]);
  exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$role = $user['role'] ?? '';

$method = $_SERVER['REQUEST_METHOD'];

// Path absolut untuk log
$logPath = __DIR__ . "/upload_error.log";
$successLogPath = __DIR__ . "/log.txt";

switch ($method) {
  case 'GET':
    $result = $conn->query("SELECT * FROM lapangan ORDER BY created_at DESC");
    $lapangan = [];
    while ($row = $result->fetch_assoc()) {
      $lapangan[] = $row;
    }
    echo json_encode($lapangan);
    break;

  case 'POST':
    if ($role !== 'Admin') {
      echo json_encode(['error' => 'Akses ditolak. Hanya Admin yang dapat menambah atau mengubah data.']);
    exit;
  }
    $nama = $_POST['nama'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $harga = $_POST['harga'] ?? 0;
    $status = $_POST['status'] ?? 'Tersedia';

    $gambarName = null;
    $uploadDir = realpath(__DIR__ . "/../img/uploads");

    // Buat folder jika belum ada
    if (!$uploadDir) {
      $uploadDir = __DIR__ . "/../img/uploads";
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Izin penuh untuk debug
      }
    }

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
      $originalName = $_FILES['gambar']['name'];
      $gambarName = time() . '_' . basename($originalName);
      $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $gambarName;

      file_put_contents($logPath, "Target path: $targetPath" . PHP_EOL, FILE_APPEND);
      file_put_contents($logPath, "Realpath: " . realpath($uploadDir) . PHP_EOL, FILE_APPEND);

      if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
        $uploadError = $_FILES['gambar']['error'];
        file_put_contents($logPath, "Gagal upload: " . $_FILES['gambar']['name'] . " (Error code: $uploadError)" . PHP_EOL, FILE_APPEND);
        echo json_encode(['error' => 'Gagal upload file gambar.']);
        exit;
      }

      file_put_contents($successLogPath, "Upload berhasil: " . $gambarName . PHP_EOL, FILE_APPEND);
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== 4) {
      $uploadError = $_FILES['gambar']['error'];
      file_put_contents($logPath, "Upload error code: $uploadError" . PHP_EOL, FILE_APPEND);
      echo json_encode(['error' => 'Terjadi masalah saat upload gambar (error code: ' . $uploadError . ')']);
      exit;
    }

    // UPDATE jika ada ID
    if (!empty($_POST['id'])) {
      $id = $_POST['id'];
      if ($gambarName) {
        $stmt = $conn->prepare("UPDATE lapangan SET nama=?, lokasi=?, harga=?, status=?, gambar=? WHERE id=?");
        $stmt->bind_param("ssissi", $nama, $lokasi, $harga, $status, $gambarName, $id);
      } else {
        $stmt = $conn->prepare("UPDATE lapangan SET nama=?, lokasi=?, harga=?, status=? WHERE id=?");
        $stmt->bind_param("ssisi", $nama, $lokasi, $harga, $status, $id);
      }
      $stmt->execute();
      echo json_encode(['success' => true, 'action' => 'updated']);
    } else {
      // INSERT
      if (!$gambarName) {
        echo json_encode(['error' => 'Gambar wajib diupload saat tambah data']);
        exit;
      }

      $stmt = $conn->prepare("INSERT INTO lapangan (nama, lokasi, harga, status, gambar) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("ssiss", $nama, $lokasi, $harga, $status, $gambarName);
      $stmt->execute();
      echo json_encode(['success' => true, 'action' => 'created']);
    }
    break;

  case 'DELETE':
    if ($role !== 'Admin') {
      echo json_encode(['error' => 'Akses ditolak. Hanya Admin yang dapat menghapus data.']);
      exit;
    }

    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM lapangan WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;
  }

$conn->close();
