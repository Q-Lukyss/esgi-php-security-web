<?php
/** @var string $title */
/** @var array|null $cocktail */
/** @var array $ingredients */
/** @var array $upvotes */
/** @var array $ratings */

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function fmtDate(?string $dt): string
{
    if (!$dt) {
        return "—";
    }
    try {
        $d = new DateTime($dt);
        return $d->format("d/m/Y H:i");
    } catch (\Throwable $e) {
        return $dt;
    }
}

function stars($n): string
{
    $n = (int) $n;
    $n = max(0, min(5, $n));
    return str_repeat("★", $n) . str_repeat("☆", 5 - $n);
}

$likesCount = is_array($upvotes ?? null) ? count($upvotes) : 0;
$ratingsCount = is_array($ratings ?? null) ? count($ratings) : 0;

$avg = null;
if (!empty($ratings)) {
    $sum = 0;
    $cnt = 0;
    foreach ($ratings as $r) {
        if (isset($r["rating"])) {
            $sum += (int) $r["rating"];
            $cnt++;
        }
    }
    $avg = $cnt ? round($sum / $cnt, 1) : null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= e((string) $title) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">

    <link href="/css/style.css" rel="stylesheet">
</head>

<body>

<?php
include "components/topbar.php";
include "components/navbar.php";
?>

<?php if (empty($cocktail)): ?>
    <div class="container py-5">
        <div class="alert alert-warning">
            Potion introuvable.
        </div>
        <a href="./cocktails" class="btn btn-secondary"><i class="fa fa-arrow-left mr-2"></i>Retour</a>
    </div>
<?php else: ?>

    <!-- MAIN content -->
    <div class="container py-4">
        <div class="row">
            <!-- Left -->
            <div class="col-lg-8 mb-4">

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="mb-1"><?= e(
                            (string) $cocktail["name"],
                        ) ?></h2>
                        <p class="text-muted mb-0">
                            <i class="fa fa-user mr-2"></i><?= e(
                                (string) ($cocktail["username"] ?? "—"),
                            ) ?>
                            <span class="mx-2">•</span>
                            <i class="fa fa-clock mr-2"></i><?= e(
                                fmtDate($cocktail["created_at"] ?? null),
                            ) ?>
                        </p>
                    </div>

                    <div class="text-right">
                        <a href="/cocktails" class="btn btn-secondary mr-2">
                            <i class="fa fa-arrow-left mr-2"></i>Retour
                        </a>
                        <a href="<?= "/cocktails/edit/" .
                            urlencode(
                                (string) $cocktail["slug"],
                            ) ?>" class="btn btn-primary">
                            <i class="fa fa-pen mr-2"></i>Éditer
                        </a>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-light rounded p-4 mb-4">
                    <h5 class="mb-3"><i class="fa fa-scroll mr-2 text-primary"></i>Description</h5>
                    <?php if (!empty($cocktail["description"])): ?>
                        <p class="mb-0"><?= nl2br(
                            e((string) $cocktail["description"]),
                        ) ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Aucune description pour le moment.</p>
                    <?php endif; ?>
                </div>

                <!-- Ingredients -->
                <div class="bg-light rounded p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0"><i class="fa fa-flask mr-2 text-primary"></i>Ingrédients</h5>
                        <span class="badge badge-pill badge-primary px-3 py-2">
                            <?= count($ingredients ?? []) ?> ingrédient(s)
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th>Ingrédient</th>
                                <th style="width:140px;">Quantité</th>
                                <th style="width:140px;">Unité</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($ingredients)): ?>
                                <?php foreach ($ingredients as $i): ?>
                                    <tr>
                                        <td><?= e(
                                            (string) ($i["name"] ?? "—"),
                                        ) ?></td>
                                        <td><?= e(
                                            (string) ($i["quantity"] ?? "—"),
                                        ) ?></td>
                                        <td><?= e(
                                            (string) ($i["unit"] ?? "—"),
                                        ) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Aucun ingrédient.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right -->
            <div class="col-lg-4">

                <!-- Stats card -->
                <div class="bg-light rounded p-4 mb-4">
                    <h5 class="mb-3"><i class="fa fa-chart-bar mr-2 text-primary"></i>Stats</h5>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted"><i class="fa fa-heart mr-2"></i>Likes</span>
                        <strong><?= (int) $likesCount ?></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted"><i class="fa fa-comment mr-2"></i>Avis</span>
                        <strong><?= (int) $ratingsCount ?></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="fa fa-star mr-2"></i>Moyenne</span>
                        <strong><?= $avg !== null
                            ? e((string) $avg) . " / 5"
                            : "—" ?></strong>
                    </div>
                </div>

                <!-- Ratings -->
                <div class="bg-light rounded p-4">
                    <h5 class="mb-3"><i class="fa fa-feather-alt mr-2 text-primary"></i>Commentaires</h5>

                    <?php if (!empty($ratings)): ?>
                        <?php foreach ($ratings as $r): ?>
                            <div class="bg-white rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="text-warning" style="letter-spacing:1px;">
                                        <?= e(stars($r["rating"] ?? 0)) ?>
                                    </div>
                                    <small class="text-muted"><?= e(
                                        fmtDate($r["created_at"] ?? null),
                                    ) ?></small>
                                </div>

                                <?php if (!empty($r["comment"])): ?>
                                    <div><?= nl2br(
                                        e((string) $r["comment"]),
                                    ) ?></div>
                                <?php else: ?>
                                    <div class="text-muted">Pas de commentaire.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Aucun avis pour le moment.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

<?php endif; ?>

<?php
include "components/footer.html";
include "components/backtotop.html";
?>

<a href="#" class="btn btn-secondary px-2 back-to-top"><i class="fa fa-angle-double-up"></i></a>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="lib/isotope/isotope.pkgd.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>

<script src="mail/jqBootstrapValidation.min.js"></script>
<script src="mail/contact.js"></script>

<script src="js/main.js"></script>
</body>
</html>
