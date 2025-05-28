<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../index.html");
  exit;
}

$conn = new mysqli("localhost", "root", "", "futsal_db");
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Auto update status lapangan jika pemesanan sudah lewat
$conn->query("
  UPDATE lapangan l
  JOIN pemesanan p ON p.lapangan_id = l.id
  SET l.status = 'Tersedia', p.status = 'Selesai'
  WHERE p.status = 'Dipakai' AND CONCAT(p.tanggal, ' ', p.waktu_selesai) < NOW()
");

// Ambil user info
$userId = $_SESSION['user_id'];
$sql = "SELECT name, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FutsalChamp - Pemesanan</title>
  <link rel="stylesheet" href="../css/core.css?v=<?= time(); ?>" />
  <link rel="shortcut icon" href="../img/icon/logo_futsal-champion.png" type="image/x-icon" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <h2 class="logo" id="userRole"><?= htmlspecialchars($user['role']) ?> Panel</h2>
      <nav>
        <ul>
          <li><a href="../pages/dashboard.php">Dashboard</a></li>
          <li><a href="../pages/daftar-lapangan.php">Daftar Lapangan</a></li>
          <?php if ($user['role'] === 'Admin'): ?>
            <li><a href="../pages/manajemen-pengguna.php">Manajemen Pengguna</a></li>
          <?php endif; ?>
          <li><a href="../pages/pemesanan.php" class="active">Pemesanan</a></li>
          <li><a href="../pages/developer.php">Tim Pengembang</a></li>
        </ul>
      </nav>
      <div class="sidebar-bottom">
        <a href="profile.html" class="profile-link">
          <img src="../img/icon/profile.png" alt="Profile" class="profile-icon" />
          <span style="margin-left:8px; color:white; font-weight:bold;"><?= htmlspecialchars($user['name']) ?></span>
        </a>
        <button id="darkModeToggle" class="dark-toggle">🌓 Dark Mode</button>
      </div>
    </aside>

    <main class="main-content">
      <!-- Menu Toggle -->
      <div class="dashboard-header">
        <button id="menuToggle" class="menu-toggle" aria-label="Menu">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </button>
        <h1>Pemesanan Lapangan Futsal</h1>
      </div>

      <!-- Form Pemesanan -->
      <div class="form-container">
        <label for="lapangan">Pilih Lapangan</label>
        <select id="lapangan"></select>

        <label for="tanggal">Pilih Tanggal</label>
        <input type="date" id="tanggal" required min="<?= date('Y-m-d') ?>" />

        <label for="waktu_mulai">Waktu Mulai</label>
        <input type="time" id="waktu_mulai" required />

        <label for="waktu_selesai">Waktu Selesai</label>
        <input type="time" id="waktu_selesai" required />

        <button onclick="buatPemesanan()">Pesan Sekarang</button>
      </div>

      <h2>Daftar Lapangan Tersedia</h2>
      <table class="lapangan-table" id="tabelLapangan">
        <thead>
          <tr>
            <th>No</th>
            <th>Foto</th>
            <th>Nama Lapangan</th>
            <th>Lokasi</th>
            <th>Harga per Jam</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <footer>
        <small>© 2025 FutsalChamp. All rights reserved.</small>
      </footer>
    </main>

    <!-- Modal Gambar -->
    <div id="imageModal" class="modal" style="display:none;">
      <span class="close" onclick="closeModal()">&times;</span>
      <img class="modal-content" id="modalImage">
    </div>
  </div>

<script>
  // Toggle Sidebar
  const menuToggle = document.getElementById("menuToggle");
  const sidebar = document.getElementById("sidebar");

  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("sidebar-open");
    menuToggle.classList.toggle("open");
    document.body.style.overflow = sidebar.classList.contains("sidebar-open") ? "hidden" : "";
  });

  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 768 && sidebar.classList.contains("sidebar-open")) {
      if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
        sidebar.classList.remove("sidebar-open");
        menuToggle.classList.remove("open");
        document.body.style.overflow = '';
      }
    }
  });

  function closeModal() {
    document.getElementById("imageModal").style.display = "none";
  }

  const API_URL = "../php/api-dftr-lapangan.php";

  async function getLapangan() {
    const res = await fetch(API_URL);
    return await res.json();
  }

  async function renderTabelLapangan() {
    const tbody = document.querySelector("#tabelLapangan tbody");
    tbody.innerHTML = "";

    const lapanganList = await getLapangan();

    lapanganList.forEach((lapangan, index) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${index + 1}</td>
        <td><img src="../img/uploads/${lapangan.gambar}" width="50" style="cursor:pointer;" onclick="showImage('../img/uploads/${lapangan.gambar}')"/></td>
        <td>${lapangan.nama}</td>
        <td>${lapangan.lokasi}</td>
        <td>Rp ${parseInt(lapangan.harga).toLocaleString()}</td>
        <td class="status-${lapangan.status.toLowerCase()}">${lapangan.status}</td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function renderPilihanLapangan() {
    const lapanganSelect = document.getElementById("lapangan");
    lapanganSelect.innerHTML = "";

    const lapanganList = await getLapangan();

    lapanganList.forEach((lapangan) => {
      if (lapangan.status === "Tersedia") {
        const option = document.createElement("option");
        option.value = lapangan.id;
        option.textContent = lapangan.nama;
        lapanganSelect.appendChild(option);
      }
    });
  }

  function showImage(src) {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    modal.style.display = "flex";
    modalImg.src = src;
  }

  async function buatPemesanan() {
    const lapanganId = document.getElementById("lapangan").value;
    const tanggal = document.getElementById("tanggal").value;
    const waktuMulai = document.getElementById("waktu_mulai").value;
    const waktuSelesai = document.getElementById("waktu_selesai").value;

    if (!lapanganId || !tanggal || !waktuMulai || !waktuSelesai) {
      alert("Semua field harus diisi!");
      return;
    }

    const today = new Date().toISOString().split("T")[0];
    if (tanggal < today) {
      alert("Tanggal tidak boleh sebelum hari ini!");
      return;
    }

    if (waktuMulai >= waktuSelesai) {
      alert("Waktu selesai harus lebih dari waktu mulai!");
      return;
    }

    const payload = {
      lapangan_id: lapanganId,
      tanggal,
      waktu_mulai: waktuMulai,
      waktu_selesai: waktuSelesai
    };

    const res = await fetch("../php/api-pemesanan.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const result = await res.json();
    if (result.success) {
      alert(`Pemesanan berhasil!\nTotal bayar: Rp ${result.total_harga.toLocaleString()}`);
      document.getElementById("tanggal").value = "";
      document.getElementById("waktu_mulai").value = "";
      document.getElementById("waktu_selesai").value = "";
      renderTabelLapangan();
      renderPilihanLapangan();
    } else {
      alert("Gagal pesan: " + result.error);
    }
  }

  window.addEventListener("DOMContentLoaded", () => {
    renderTabelLapangan();
    renderPilihanLapangan();

    const tanggalInput = document.getElementById("tanggal");
    tanggalInput.addEventListener("change", function () {
      const today = new Date().toISOString().split("T")[0];
      if (this.value < today) {
        alert("Tanggal tidak boleh sebelum hari ini!");
        this.value = today;
      }
    });

    const toggle = document.getElementById("darkModeToggle");

    function updateToggleText() {
      toggle.textContent = document.body.classList.contains("dark-mode")
        ? "🌙 Light Mode"
        : "☀️ Dark Mode";
    }

    toggle.addEventListener("click", () => {
      document.body.classList.toggle("dark-mode");
      localStorage.setItem("darkMode", document.body.classList.contains("dark-mode") ? "enabled" : "disabled");
      updateToggleText();
    });

    if (localStorage.getItem("darkMode") === "enabled") {
      document.body.classList.add("dark-mode");
    }
    updateToggleText();
  });
</script>
</body>
</html>
