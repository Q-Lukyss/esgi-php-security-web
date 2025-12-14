<?php declare(strict_types=1);

/** @var string $title */
/** @var string $csrf_token */
/** @var string $redirect */

$error = isset($_GET["error"]) ? (string) $_GET["error"] : "";
$msg = match ($error) {
    "missing" => "Veuillez remplir tous les champs.",
    "invalid" => "Identifiants invalides.",
    "csrf" => "Session expirée (CSRF). Recharge la page et réessaie.",
    default => "",
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

    <div class="container-fluid py-5" style="min-height: 80vh;">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-md-8 col-lg-5">
            <div class="card border-0 shadow">
              <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                  <h2 class="font-weight-bold mb-2" style="color: #CD6800;">Connexion</h2>
                  <p class="mb-0 text-muted">Accède à ton compte pour gérer tes potions.</p>
                </div>

                <?php if ($msg !== ""): ?>
                  <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?>
                  </div>
                <?php endif; ?>

                <form id="loginForm">
                  <input type="hidden" id="csrf" name="csrf" value="<?= htmlspecialchars(
                      (string) $csrf_token,
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>">
                  <input type="hidden" id="redirect" name="redirect" value="<?= htmlspecialchars(
                      (string) $redirect,
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>">

                  <div id="loginError" class="alert alert-danger d-none" role="alert"></div>

                  <div class="form-group mb-3">
                    <label class="mb-1">Username ou email</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                  </div>

                  <div class="form-group mb-4">
                    <label class="mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                  </div>

                  <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>

              </div>
            </div>

            <div class="text-center mt-4">
              <a href="/cocktails" class="text-decoration-none">← Retour aux potions</a>
            </div>
          </div>
        </div>
      </div>
    </div>



    <?php
    include "components/footer.html";
    include "components/backtotop.html";
    ?>


    <!-- Back to Top -->
    <a href="#" class="btn btn-secondary px-2 back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/isotope/isotope.pkgd.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script src="js/cocktail-table.js"></script>

    <script>
    document.getElementById("loginForm").addEventListener("submit", async (e) => {
      e.preventDefault();

      const errorBox = document.getElementById("loginError");
      errorBox.classList.add("d-none");
      errorBox.textContent = "";

      const payload = {
        username: document.getElementById("username").value.trim(),
        password: document.getElementById("password").value,
        csrf: document.getElementById("csrf").value
      };

      try {
        const res = await fetch("/login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "include",
          body: JSON.stringify(payload)
        });

        if (res.ok) {
          const redirect = document.getElementById("redirect").value || "/cocktails";
          window.location.href = redirect;
          return;
        }

        // essaie de lire un message JSON
        let msg = "Erreur de connexion.";
        try {
          const data = await res.json();
          msg = Array.isArray(data) ? data.join(", ") : (data.error || msg);
        } catch {}

        errorBox.textContent = msg;
        errorBox.classList.remove("d-none");
      } catch (err) {
        errorBox.textContent = "Erreur réseau.";
        errorBox.classList.remove("d-none");
      }
    });
    </script>
</body>

</html>
