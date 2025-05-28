<?php
session_start();
// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../index.html");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['batal_id'])) {
    $batalId = $_POST['batal_id'];

    // Buat koneksi sementara
    $conn = new mysqli("localhost", "root", "", "futsal_db");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Ambil lapangan_id dari pemesanan yang akan dihapus
    $stmt = $conn->prepare("SELECT lapangan_id FROM pemesanan WHERE id = ?");
    $stmt->bind_param("i", $batalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pemesanan = $result->fetch_assoc();

    if ($pemesanan) {
        $lapanganId = $pemesanan['lapangan_id'];

        // Hapus pemesanan
        $stmt = $conn->prepare("DELETE FROM pemesanan WHERE id = ?");
        $stmt->bind_param("i", $batalId);
        $stmt->execute();

        // Update status lapangan jadi "Tersedia"
        $stmt = $conn->prepare("UPDATE lapangan SET status = 'Tersedia' WHERE id = ?");
        $stmt->bind_param("i", $lapanganId);
        $stmt->execute();
    }

    $conn->close();

    // Redirect agar data terbaru muncul
    header("Location: dashboard.php");
    exit();
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "futsal_db");
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

$conn->query("
  UPDATE lapangan l
  JOIN pemesanan p ON p.lapangan_id = l.id
  SET l.status = 'Tersedia', p.status = 'Selesai'
  WHERE p.status = 'Dipakai' AND CONCAT(p.tanggal, ' ', p.waktu_selesai) < NOW()
");

// Ambil data user dari DB
$userId = $_SESSION['user_id'];
$sql = "SELECT name, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Query untuk mengambil data pemesanan
$sql = "SELECT p.id, p.tanggal, p.waktu_mulai, p.waktu_selesai, l.nama AS lapangan, p.status
        FROM pemesanan p
        JOIN lapangan l ON p.lapangan_id = l.id
        WHERE p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FutsalChamp</title>
  <link rel="stylesheet" href="../css/core.css?v=<?= time(); ?>" />
    <!-- logo futsal-champion -->
    <link
      rel="shortcut icon"
      href="../img/icon/logo_futsal-champion.png"
      type="image/x-icon"
    />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <h2 class="logo"><?= htmlspecialchars($user['role']) ?> Panel</h2>
  <nav>
    <ul>
      <li>
        <a href="../pages/dashboard.php" class="active">Dashboard</a>
      </li>
      <li>
        <a href="../pages/daftar-lapangan.php">Daftar Lapangan</a>
      </li>
      <?php if ($user['role'] === 'Admin'): ?>
        <li><a href="../pages/manajemen-pengguna.php">Manajemen Pengguna</a></li>
      <?php endif; ?>
      <li>
        <a href="../pages/pemesanan.php">Pemesanan</a>
      </li>
      <li>
        <a href="../pages/developer.php">Tim Pengembang</a>
      </li>
    </ul>
  </nav>
  <div class="sidebar-bottom">
    <a href="profile.html" class="profile-link" title="Profil Anda">
      <img src="../img/icon/profile.png" alt="Profile" class="profile-icon" />
      <span style="margin-left:8px; color:white; font-weight:bold;"><?= htmlspecialchars($user['name']) ?></span>
    </a>
    <button id="darkModeToggle" class="dark-toggle">🌓 Dark Mode</button>
  </div>
</aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="dashboard-header">
        <button id="menuToggle" class="menu-toggle" aria-label="Menu">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </button>
        <h1>Selamat Datang <?= htmlspecialchars($user['name']) ?></h1>
      </div>

      <h3>Data Pemesanan Anda</h3>
      <div class="table-responsive">
        <table class="lapangan-table">
              <thead>
                  <tr>
                      <th>No</th>
                      <th>Lapangan</th>
                      <th>Tanggal</th>
                      <th>Waktu Mulai</th>
                      <th>Waktu Selesai</th>
                      <th>Aksi</th>
                  </tr>
              </thead>
              <tbody>
                  <?php
                  $no = 1;
                  while ($row = $result->fetch_assoc()):
                  ?>
                  <tr>
                    <td><?= $no ?></td>
                    <td><?= htmlspecialchars($row['lapangan']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['waktu_mulai']) ?></td>
                    <td><?= htmlspecialchars($row['waktu_selesai']) ?></td>
                    <td>
                      <?php if ($row['status'] !== 'Selesai'): ?>
                        <form method="POST" onsubmit="return confirm('Batalkan pemesanan ini?');">
                          <input type="hidden" name="batal_id" value="<?= $row['id'] ?>">
                          <button type="submit" class="batal-btn">Batal</button>
                        </form>
                      <?php else: ?>
                        <em>Selesai</em>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php
                  $no++;
                  endwhile;
                  ?>
        </table>
      </div>

      <div class="map-container-responsive">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3976.9985672238617!2d124.824439!3d1.4626495!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x328774f05af8cb87%3A0x816a88e1733d514b!2sChampion%20Futsal%20Bahu!5e0!3m2!1sid!2sid!4v1716500000000!5m2!1sid!2sid"
          width="100%"
          height="400"
          style="border: 0"
          allowfullscreen=""
          loading="lazy"
        ></iframe>
      </div>

      <footer>
        <small>© 2025 FutsalChamp. All rights reserved.</small>
      </footer>
    </main>
  </div>

  <script>
  fetch("../php/index.php?action=check-session")
    .then(res => res.json())
    .then(data => {
      if (data.name && data.role) {
        document.querySelector(".logo").textContent = `${data.role} Panel`;
        document.querySelector(".dashboard-header h1").textContent = `Selamat Datang ${data.name}`;
      } else {
        window.location.href = "../../index.html"; // redirect jika belum login
      }
    });

  // Sidebar responsive toggle
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const container = document.querySelector('.container');

  menuToggle.addEventListener('click', function() {
    sidebar.classList.toggle('sidebar-open');
    menuToggle.classList.toggle('open');
    if (sidebar.classList.contains('sidebar-open')) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  });

  // Close sidebar on outside click (mobile)
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768 && sidebar.classList.contains('sidebar-open')) {
      if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
        sidebar.classList.remove('sidebar-open');
        menuToggle.classList.remove('open');
        document.body.style.overflow = '';
      }
    }
  });

    // fungsi dark mode
    const toggle = document.getElementById("darkModeToggle");

    function updateToggleText() {
      if (document.body.classList.contains("dark-mode")) {
        toggle.textContent = "🌙 Light Mode";
      } else {
        toggle.textContent = "☀️ Dark Mode";
      } 
    }

    toggle.addEventListener("click", () => {
      document.body.classList.toggle("dark-mode");

      // Update localStorage
      localStorage.setItem(
        "darkMode",
        document.body.classList.contains("dark-mode") ? "enabled" : "disabled"
      );

      // Update teks tombol
      updateToggleText();
    });

    window.addEventListener("DOMContentLoaded", () => {
      if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark-mode");
      }

      // Set teks tombol berdasarkan mode saat ini
      updateToggleText();
    });
  </script>
</body>
</html>
