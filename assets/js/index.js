document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    const formData = new FormData();
    formData.append("email", email);
    formData.append("password", password);

    fetch("assets/php/index.php?action=login", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Simpan session secara server-side, redirect ke dashboard
          window.location.href = "assets/pages/dashboard.php";
        } else {
          alert(data.error);
        }
      })
      .catch((err) => {
        alert("Terjadi kesalahan saat login");
        console.error("Login error:", err);
      });
  });

  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", () => {
      const isHidden = passwordInput.getAttribute("type") === "password";
      passwordInput.setAttribute("type", isHidden ? "text" : "password");
      togglePassword.src = isHidden
        ? "./assets/img/icon/eye-on.png"
        : "./assets/img/icon/eye-off.png";
    });
  }
});
