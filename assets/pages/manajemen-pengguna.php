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

$userId = $_SESSION['user_id'];
$sql = "SELECT name, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$conn->close();

if ($user['role'] !== 'Admin') {
  header("Location: dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FutsalChamp - Manajemen Pengguna</title>
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
            <li><a href="../pages/manajemen-pengguna.php" class="active">Manajemen Pengguna</a></li>
          <?php endif; ?>
          <li><a href="../pages/pemesanan.php">Pemesanan</a></li>
          <li><a href="../pages/developer.php">Tim Pengembang</a></li>
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
        <h1>Manajemen Pengguna</h1>
      </div>

      <!-- Form Tambah/Edit Pengguna -->
      <div class="form-container">
        <input type="hidden" id="editIndex" />
        <input type="text" id="username" placeholder="Username" required />
        <input type="email" id="email" placeholder="Email" required />
        <input type="password" id="password" placeholder="Password" required />
        <select id="role">
          <option value="Admin">Admin</option>
          <option value="User">User</option>
        </select>
        <button onclick="tambahPengguna()">Simpan</button>
        <button class="batal-btn" onclick="resetForm()">Batal</button>
      </div>

      <h3>Daftar Pengguna</h3>
      <!-- Tabel Pengguna -->
      <table class="user-table" id="tabelPengguna">
        <thead>
          <tr>
            <th>No</th>
            <th>Username</th>
            <th>Role</th>
            <th>Email</th>
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
  </div>

  <script>
    const userRole = "<?= $user['role']; ?>";

    // Validasi session & load data pengguna
    window.addEventListener("DOMContentLoaded", () => {
      fetch("../php/index.php?action=check-session")
        .then((res) => res.json())
        .then((data) => {
          if (data.error) {
            alert("Session habis atau belum login.");
            window.location.href = "../../index.html";
          } else {
            document.getElementById("userRole").textContent = data.role + " Panel";
            loadPengguna();
          }
        })
        .catch((err) => {
          console.error("Gagal ambil data session:", err);
          window.location.href = "../../index.html";
        });

      // Load dark mode state
      if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark-mode");
      }
      updateToggleText();
    });

    // Sidebar toggle
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    menuToggle.addEventListener("click", function () {
      sidebar.classList.toggle("sidebar-open");
      menuToggle.classList.toggle("open");
      document.body.style.overflow = sidebar.classList.contains("sidebar-open") ? "hidden" : "";
    });

    document.addEventListener("click", function (e) {
      if (window.innerWidth <= 768 && sidebar.classList.contains("sidebar-open")) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
          sidebar.classList.remove("sidebar-open");
          menuToggle.classList.remove("open");
          document.body.style.overflow = '';
        }
      }
    });

    // Pengelolaan data pengguna
    let dataPengguna = [];

    function loadPengguna() {
      fetch("../php/index.php?action=get_users")
        .then((res) => res.json())
        .then((data) => {
          dataPengguna = data;
          renderTabel();
        })
        .catch((err) => {
          console.error("Gagal muat data pengguna:", err);
        });
    }

    function renderTabel() {
      const tbody = document.querySelector("#tabelPengguna tbody");
      tbody.innerHTML = "";
      dataPengguna.forEach((item, index) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${index + 1}</td>
          <td>${item.username}</td>
          <td>${item.role}</td>
          <td>${item.email}</td>
          <td>
            <button class="btn-edit" onclick="editPengguna(${index})">Edit</button>
            <button class="btn-delete" onclick="hapusPengguna(${index})">Hapus</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function tambahPengguna() {
      const username = document.getElementById("username").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      const role = document.getElementById("role").value;
      const editIndex = document.getElementById("editIndex").value;

      if (!username || !email || (!password && editIndex === "") || !role) {
        alert("Semua field harus diisi!");
        return;
      }

      const user = { username, email, role };
      const endpoint = editIndex === "" ? "add_user" : "update_user";

      if (editIndex !== "") {
        user.id = dataPengguna[editIndex].id;
      } else {
        user.password = password;
      }

      fetch(`../php/index.php?action=${endpoint}`, {
        method: "POST",
        body: JSON.stringify(user),
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then((res) => res.json())
        .then(() => {
          loadPengguna();
          resetForm();
        })
        .catch((err) => console.error("Gagal simpan pengguna:", err));
    }

    function editPengguna(index) {
      const item = dataPengguna[index];
      document.getElementById("username").value = item.username;
      document.getElementById("email").value = item.email;
      document.getElementById("role").value = item.role;
      document.getElementById("editIndex").value = index;
    }

    function hapusPengguna(index) {
      if (!confirm("Yakin ingin menghapus pengguna ini?")) return;
      const userId = dataPengguna[index].id;

      fetch("../php/index.php?action=delete_user", {
        method: "POST",
        body: JSON.stringify({ id: userId }),
        headers: { "Content-Type": "application/json" },
      })
        .then((res) => res.json())
        .then(() => loadPengguna())
        .catch((err) => console.error("Gagal hapus pengguna:", err));
    }

    function resetForm() {
      document.getElementById("username").value = "";
      document.getElementById("email").value = "";
      document.getElementById("password").value = "";
      document.getElementById("role").value = "User";
      document.getElementById("editIndex").value = "";
    }

    // Dark mode toggle
    const toggle = document.getElementById("darkModeToggle");
    toggle.addEventListener("click", () => {
      document.body.classList.toggle("dark-mode");
      localStorage.setItem("darkMode", document.body.classList.contains("dark-mode") ? "enabled" : "disabled");
      updateToggleText();
    });

    function updateToggleText() {
      toggle.textContent = document.body.classList.contains("dark-mode")
        ? "🌙 Light Mode"
        : "☀️ Dark Mode";
    }
  </script>
</body>
</html>
