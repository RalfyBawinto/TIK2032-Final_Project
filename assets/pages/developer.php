<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.html");
    exit();
}

// Ambil data user dari DB
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar Developer | FutsalChamp</title>
    <link rel="stylesheet" href="../css/core.css?v=<?= time(); ?>" />
    <link rel="shortcut icon" href="../img/icon/logo_futsal-champion.png" type="image/x-icon" />
</head>
<body>
    <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <h2 class="logo"><?= htmlspecialchars($user['role']) ?> Panel</h2>
        <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="daftar-lapangan.php">Daftar Lapangan</a></li>
            <?php if ($user['role'] === 'Admin'): ?>
            <li><a href="manajemen-pengguna.php">Manajemen Pengguna</a></li>
            <?php endif; ?>
            <li><a href="pemesanan.php">Pemesanan</a></li>
            <li><a href="developer.php" class="active">Tim Pengembang</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-header">
            <button id="menuToggle" class="menu-toggle" aria-label="Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
                </button>
            <h1>Developer Aplikasi</h1>
        </div>
        <section class="developer-section">
            <h3>Daftar Pengembang Aplikasi FutsalChamp</h3>
            <div class="developer-cards">
                <div class="dev-card">
                    <img src="../img/dev/jeremy.jpeg" alt="Dev 1" class="dev-photo" />
                    <h4>Jeremy Chris Allen Pongdatu</h4>
                    <p>UI / UX Designer</p>
                    <i>jpongdatu3245@gmail.com</i>
                </div>
                <div class="dev-card">
                    <img src="../img/dev/abdon.jpeg" alt="Dev 2" class="dev-photo" />
                    <h4>Abdon E. Sayori</h4>
                    <p>Front-End Developer</p>
                    <i>abdonesayori@gmail.com</i>
                </div>
                <div class="dev-card">
                    <img src="../img/dev/risky.jpeg" alt="Dev 3" class="dev-photo" />
                    <h4>Risky Alfelsinus Imbat</h4>
                    <p>Front-End Developer</p>
                    <i>riskiimbat0@gmail.com</i>
                </div>
                <div class="dev-card">
                    <img src="../img/dev/ralfy.jpeg" alt="Dev 3" class="dev-photo" />
                    <h4>Ralfy Briggith Bawinto</h4>
                    <p>Back-End Developer</p>
                    <i>ralfybriggithbawinto@gmail.com</i>
                </div>
            </div>
        </section>
        <footer>
            <small>© 2025 FutsalChamp. All rights reserved.</small>
        </footer>
    </main>
</div>
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
            menuToggle.classList.toggle('open');
            if (sidebar.classList.contains('sidebar-open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
            });

        // Dark mode toggle
        const toggle = document.getElementById("darkModeToggle");

        function updateToggleText() {
            toggle.textContent = document.body.classList.contains("dark-mode")
            ? "🌙 Light Mode"
            : "☀️ Dark Mode";
        }

        toggle.addEventListener("click", () => {
            document.body.classList.toggle("dark-mode");
            localStorage.setItem(
            "darkMode",
            document.body.classList.contains("dark-mode") ? "enabled" : "disabled"
            );
            updateToggleText();
        });

        window.addEventListener("DOMContentLoaded", () => {
            if (localStorage.getItem("darkMode") === "enabled") {
            document.body.classList.add("dark-mode");
            }
            updateToggleText();
        });
    </script>
</body>
</html>
