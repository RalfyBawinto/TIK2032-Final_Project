<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../index.html");
  exit;
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
<h2 class="logo" id="userRole"><?= htmlspecialchars($user['role']) ?> Panel</h2>
  <nav>
    <ul>
      <li>
        <a href="../pages/dashboard.php">Dashboard</a>
      </li>
      <li>
        <a href="../pages/daftar-lapangan.php" class="active">Daftar Lapangan</a>
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


      <main class="main-content">
        <div class="dashboard-header">
          <button id="menuToggle" class="menu-toggle" aria-label="Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
          </button>
          <h1>Daftar Lapangan Futsal</h1>
        </div>

        <h3>Form CRUD</h3>
        <!-- Form Tambah/Edit -->
        <?php if ($user['role'] === 'Admin'): ?>
          <div class="form-container">
            <input type="hidden" id="editIndex" />
            <input type="text" id="nama" placeholder="Nama Lapangan" required />
            <input type="text" id="lokasi" placeholder="Lokasi" required />
            <input type="number" id="harga" placeholder="Harga per Jam" required />
            <input type="file" id="gambar" accept="image/*" />
            <select id="status">
              <option value="Tersedia">Tersedia</option>
              <option value="Dibooking">Dibooking</option>
              <option value="Dipakai">Dipakai</option>
            </select>
            <button onclick="tambahLapangan()">Simpan</button>
            <button class="batal-btn" onclick="resetForm()">Batal</button>
          </div>
        <?php endif; ?>

        <h3>Daftar Lapangan</h3>
        <!-- Tabel Lapangan -->
        <table class="lapangan-table" id="tabelLapangan">
          <thead>
            <tr>
              <th>No</th>
              <th>Foto</th>
              <th>Nama Lapangan</th>
              <th>Lokasi</th>
              <th>Harga per Jam</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <!-- Diisi dengan JS -->
          </tbody>
        </table>
        <footer>
          <small>© 2025 FutsalChamp. All rights reserved.</small>
        </footer>
      </main>
      <!-- Show Gambar -->
      <div id="imageModal" class="modal" style="display:none;">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
      </div>
    </div>

    <script>
      const userRole = "<?php echo $user['role']; ?>";
      // === FETCH ROLE & VALIDASI SESSION ===
      window.addEventListener("DOMContentLoaded", () => {
        fetch("../php/index.php?action=check-session")
          .then(res => res.json())
          .then(data => {
            if (data.error) {
              alert("Session habis atau belum login.");
              window.location.href = "../../index.html";
            } else {
              document.getElementById("userRole").textContent = data.role + " Panel";
            }
          })
          .catch(err => {
            console.error("Gagal ambil data session:", err);
            window.location.href = "../../index.html";
          });
      });

      // Sidebar responsive toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

menuToggle.addEventListener('click', function () {
  sidebar.classList.toggle('sidebar-open');
  menuToggle.classList.toggle('open');
  document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
});

// Close sidebar on outside click (mobile)
document.addEventListener('click', function (e) {
  if (window.innerWidth <= 768 && sidebar.classList.contains('sidebar-open')) {
    if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
      sidebar.classList.remove('sidebar-open');
      menuToggle.classList.remove('open');
      document.body.style.overflow = '';
    }
  }
});


  // === REPLACE DATA DUMMY DENGAN API ===
  const API_URL = "../php/api-dftr-lapangan.php";

  async function getLapangan() {
    const res = await fetch(API_URL);
    const data = await res.json();
    return data;
  }

    async function tambahLapangan() {
      if (userRole !== "Admin") {
        alert("Hanya admin yang dapat menambah atau mengubah data.");
        return;
      }
      const nama = document.getElementById("nama").value;
      const lokasi = document.getElementById("lokasi").value;
      const harga = document.getElementById("harga").value;
      const status = document.getElementById("status").value;
      const gambar = document.getElementById("gambar").files[0];
      const editIndex = document.getElementById("editIndex").value;

      const formData = new FormData();
      formData.append("nama", nama);
      formData.append("lokasi", lokasi);
      formData.append("harga", harga);
      formData.append("status", status);
      if (gambar) formData.append("gambar", gambar);

      let url = API_URL;
      let method = "POST";

    if (editIndex !== "") {
      formData.append("id", editIndex);
    }

    const res = await fetch(url, { method, body: formData });
    const result = await res.json();
    if (result.success) {
      resetForm();
      renderTabel();
    } else {
      alert("Gagal simpan data: " + result.error);
    }
  }

async function renderTabel() {
  const tbody = document.querySelector("#tabelLapangan tbody");
  tbody.innerHTML = "";
  const dataLapangan = await getLapangan();

  dataLapangan.forEach((item, index) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${index + 1}</td>
      <td>
        <img src="../img/uploads/${item.gambar}" width="50" style="cursor:pointer;" onclick="showImage('../img/uploads/${item.gambar}')" />
      </td>
      <td>${item.nama}</td>
      <td>${item.lokasi}</td>
      <td>Rp ${parseInt(item.harga).toLocaleString()}</td>
      <td class="status-${item.status.toLowerCase()}">${item.status}</td>
      <td>
        ${userRole === 'Admin' ? `
          <button class="btn-edit" onclick="editLapangan(${item.id})">Edit</button>
          <button class="btn-delete" onclick="hapusLapangan(${item.id})">Hapus</button>
        ` : `<i>Tidak tersedia</i>`}
      </td>
    `;
    tbody.appendChild(tr);
  });
}

async function editLapangan(id) {
  const data = await getLapangan();
  const item = data.find(l => l.id == id);
  document.getElementById("nama").value = item.nama;
  document.getElementById("lokasi").value = item.lokasi;
  document.getElementById("harga").value = item.harga;
  document.getElementById("status").value = item.status;
  document.getElementById("editIndex").value = item.id;
}

  async function hapusLapangan(id) {
    if (userRole !== "Admin") {
      alert("Hanya admin yang dapat menghapus data.");
      return;
    }
    if (!confirm("Yakin ingin menghapus lapangan ini?")) return;

    const formData = new URLSearchParams();
    formData.append("id", id);

    const res = await fetch(API_URL, {
      method: "DELETE",
      body: formData,
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      }
    });

    const result = await res.json();
    if (result.success) {
      renderTabel();
    } else {
      alert("Gagal hapus: " + result.error);
    }
  }

        function showImage(src) {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        modal.style.display = "flex";
        modalImg.src = src;
      }

      function closeModal() {
        document.getElementById("imageModal").style.display = "none";
      }

      function resetForm() {
        document.getElementById("nama").value = "";
        document.getElementById("lokasi").value = "";
        document.getElementById("harga").value = "";
        document.getElementById("status").value = "Tersedia";
        document.getElementById("editIndex").value = "";
        document.getElementById("gambar").value = "";
      }

      window.addEventListener("DOMContentLoaded", () => {
        renderTabel();
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
