<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "futsal_db");
if ($conn->connect_error) {
  echo json_encode(['error' => 'Koneksi gagal']);
  exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'check-session') {
  if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Belum login']);
    exit;
  }
  $id = $_SESSION['user_id'];
  $res = $conn->query("SELECT role, name, email FROM users WHERE id = $id");
  $user = $res->fetch_assoc();
  echo json_encode([
    'id' => $id,
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role']
  ]);
  exit;
}

if ($action === 'get_users') {
  $result = $conn->query("SELECT id, name as username, email, role FROM users");
  $users = [];
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
  echo json_encode($users);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($action === 'add_user') {
  $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
  $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
  $stmt->bind_param("ssss", $data['username'], $data['email'], $passwordHash, $data['role']);
  $stmt->execute();
  echo json_encode(['success' => true]);
  exit;
}

if ($action === 'update_user') {
  $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
  $stmt->bind_param("sssi", $data['username'], $data['email'], $data['role'], $data['id']);
  $stmt->execute();
  echo json_encode(['success' => true]);
  exit;
}

if ($action === 'delete_user') {
  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->bind_param("i", $data['id']);
  $stmt->execute();
  echo json_encode(['success' => true]);
  exit;
}

// --- Login dan Register Tetap Di Sini ---
switch ($action) {
  case 'register':
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm-password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($password !== $confirm) {
      echo json_encode(["error" => "Password tidak cocok"]);
      break;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
      echo json_encode(["error" => "Email sudah terdaftar"]);
      break;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hash, $role);
    $success = $stmt->execute();

    echo json_encode(["success" => $success]);
    break;

  case 'login':
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
      if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        echo json_encode(["success" => true]);
      } else {
        echo json_encode(["error" => "Password salah"]);
      }
    } else {
      echo json_encode(["error" => "Email tidak ditemukan"]);
    }
    break;

  case 'logout':
    session_destroy();
    echo json_encode(["success" => true]);
    break;

  default:
    echo json_encode(["error" => "Aksi tidak dikenal"]);
}

$conn->close();
