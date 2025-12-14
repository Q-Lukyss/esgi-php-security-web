<?php
/** @var string $title */
/** @var array $allIngredients */

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
} ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e((string) $title) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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

<div class="container py-4">

    <?php if (!empty($_GET["error"])): ?>
        <div class="alert alert-danger">Erreur: <?= e(
            (string) $_GET["error"],
        ) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">Créer une Potion</h2>
        <div class="text-right">
            <a href="/cocktails" class="btn btn-secondary mr-2">
                <i class="fa fa-arrow-left mr-2"></i>Retour
            </a>
            <button type="submit" form="createForm" class="btn btn-primary">
                <i class="fa fa-check mr-2"></i>Créer
            </button>
        </div>
    </div>

    <form id="createForm" method="post" action="/cocktails/new">
        <div class="bg-light rounded p-4 mb-4">
            <h5 class="mb-3"><i class="fa fa-scroll mr-2 text-primary"></i>Informations</h5>

            <div class="form-group">
                <label>Nom</label>
                <input class="form-control" name="name" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="5"></textarea>
            </div>

            <div class="form-group mb-0">
                <label>Visibilité</label>
                <select class="form-control" name="visibility">
                    <option value="public">Public</option>
                    <option value="private">Privé</option>
                </select>
            </div>
        </div>

        <div class="bg-light rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0"><i class="fa fa-flask mr-2 text-primary"></i>Ingrédients</h5>
                <button type="button" class="btn btn-secondary btn-sm" id="addIngredientBtn">
                    <i class="fa fa-plus mr-2"></i>Ajouter
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Ingrédient</th>
                            <th style="width:120px;">Qté</th>
                            <th style="width:120px;">Unité</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="ingredientsBody">
                        <tr id="emptyRow">
                            <td colspan="4" class="text-center text-muted py-4">Aucun ingrédient.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-3">Ex: ml, cl, g, pincée…</small>
        </div>
    </form>
</div>

<template id="ingredientRowTpl">
    <tr>
        <td>
            <select class="form-control" required>
                <option value="">—</option>
                <?php foreach ($allIngredients as $opt): ?>
                    <option value="<?= e((string) $opt["id"]) ?>"><?= e(
    (string) $opt["name"],
) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input class="form-control" required></td>
        <td><input class="form-control"></td>
        <td class="text-right">
            <button type="button" class="btn btn-outline-danger btn-sm removeRowBtn">
                <i class="fa fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<script>
(function () {
    var body = document.getElementById("ingredientsBody");
    var btn  = document.getElementById("addIngredientBtn");
    var tpl  = document.getElementById("ingredientRowTpl");

    function bindRemove(root) {
        var buttons = root.querySelectorAll(".removeRowBtn");
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener("click", function () {
                var tr = this.closest("tr");
                if (tr) tr.remove();

                var count = body.querySelectorAll("select").length;
                if (count === 0 && !document.getElementById("emptyRow")) {
                    var empty = document.createElement("tr");
                    empty.id = "emptyRow";
                    empty.innerHTML = '<td colspan="4" class="text-center text-muted py-4">Aucun ingrédient.</td>';
                    body.appendChild(empty);
                }
            });
        }
    }

    function nextIndex() {
        return body.querySelectorAll("select").length;
    }

    bindRemove(document);

    btn.addEventListener("click", function () {
        var emptyRow = document.getElementById("emptyRow");
        if (emptyRow) emptyRow.remove();

        var idx = nextIndex();
        var frag = tpl.content.cloneNode(true);
        var tr = frag.querySelector("tr");
        var select = tr.querySelector("select");
        var inputs = tr.querySelectorAll("input");

        select.name = "ingredients[" + idx + "][ingredient_id]";
        inputs[0].name = "ingredients[" + idx + "][quantity]";
        inputs[0].placeholder = "ex: 4";
        inputs[1].name = "ingredients[" + idx + "][unit]";
        inputs[1].placeholder = "ml";

        body.appendChild(frag);
        bindRemove(body);
    });
})();
</script>

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
