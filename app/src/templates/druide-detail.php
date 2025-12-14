<?php
/** @var string $title */
/** @var array|null $druide */

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

$displayName = $druide["display_name"] ?? "Druide inconnu";
$role = $druide["role"] ?? "—";
$bio = $druide["bio"] ?? "";
$avatar = $druide["avatar_url"] ?? "";

// fallback avatar (initiales)
$initials = "";
$parts = preg_split("/\s+/", trim((string) $displayName));
foreach ($parts as $p) {
    if ($p !== "") {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1, "UTF-8"), "UTF-8");
    }
}
$initials = mb_substr($initials, 0, 2, "UTF-8");
if ($initials === "") {
    $initials = "🧙";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title><?= e((string) $title) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <link href="/img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <link href="/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="/lib/lightbox/css/lightbox.min.css" rel="stylesheet">

    <link href="/css/style.css" rel="stylesheet">
</head>

<body>

<?php
include "components/topbar.php";
include "components/navbar.php";
?>

<!-- MAIN content -->
<div class="container py-4">
    <?php if (empty($druide)): ?>
        <div class="alert alert-warning">
            Druide introuvable.
        </div>
        <a href="/druides" class="btn btn-secondary">
            <i class="fa fa-arrow-left mr-2"></i>Retour
        </a>
    <?php else: ?>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="mb-0">Profil de : <?= e(
                    (string) $displayName,
                ) ?></h2>
            </div>

            <div class="text-right">
                <a href="/druides" class="btn btn-secondary">
                    <i class="fa fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Left: card -->
            <div class="col-lg-4 mb-4">
                <div class="bg-light rounded p-4 text-center">
                    <?php if (!empty($avatar)): ?>
                        <img
                            src="<?= e((string) $avatar) ?>"
                            alt="Avatar"
                            class="rounded-circle mb-3"
                            style="width:120px;height:120px;object-fit:cover;border:6px solid rgba(148,193,232,.35);"
                        >
                    <?php else: ?>
                        <div
                            class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                            style="width:120px;height:120px;border:6px solid rgba(148,193,232,.35);background:#fff;font-size:34px;font-weight:700;color:#CD6800;"
                        >
                            <?= e((string) $initials) ?>
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-1"><?= e((string) $displayName) ?></h4>
                    <span class="badge badge-pill badge-primary px-3 py-2">
                        <i class="fa fa-hat-wizard mr-2"></i><?= e(
                            (string) $role,
                        ) ?>
                    </span>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fa fa-id-badge mr-2"></i>ID</span>
                        <strong><?= (int) ($druide["id"] ?? 0) ?></strong>
                    </div>

                    <div class="mt-3">
                        <a href="/cocktails" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-flask mr-2"></i>Voir les potions
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right: bio -->
            <div class="col-lg-8 mb-4">
                <div class="bg-light rounded p-4 h-100">
                    <h5 class="mb-3">
                        <i class="fa fa-feather-alt mr-2 text-primary"></i>Bio
                    </h5>

                    <?php if (!empty(trim((string) $bio))): ?>
                        <p class="mb-0" style="white-space:pre-line;"><?= e(
                            (string) $bio,
                        ) ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Ce druide n’a pas encore écrit sa bio.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
<!-- END -->

<?php
include "components/footer.html";
include "components/backtotop.html";
?>

<a href="#" class="btn btn-secondary px-2 back-to-top"><i class="fa fa-angle-double-up"></i></a>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>

<script src="/lib/easing/easing.min.js"></script>
<script src="/lib/waypoints/waypoints.min.js"></script>
<script src="/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="/lib/isotope/isotope.pkgd.min.js"></script>
<script src="/lib/lightbox/js/lightbox.min.js"></script>

<script src="/mail/jqBootstrapValidation.min.js"></script>
<script src="/mail/contact.js"></script>

<script src="/js/main.js"></script>
<script src="/js/druide-table.js"></script>
</body>

</html>
