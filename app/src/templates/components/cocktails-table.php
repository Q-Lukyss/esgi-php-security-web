<?php
/** @var array $cocktails */

// petite utilitaire pour échapper proprement
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function pickAssoc(array $row): array {
  // on ne garde que les clés utiles, en priorisant les clés associatives
  $get = function($k) use ($row) {
    if (array_key_exists($k, $row)) return $row[$k];
    // fallback très prudent sur les index numériques présents dans ton dump (optionnel)
    $map = [
      'id' => 0, 'author_id' => 1, 'slug' => 2, 'name' => 3,
      'description' => 4, 'instructions' => 5, 'created_at' => 6, 'updated_at' => 7,
    ];
    return isset($map[$k], $row[$map[$k]]) ? $row[$map[$k]] : null;
  };

  return [
    'id' => $get('id'),
    'author_id' => $get('author_id'),
    'slug' => $get('slug'),
    'name' => $get('name'),
    'description' => $get('description'),
    'instructions' => $get('instructions'),
    'created_at' => $get('created_at'),
    'updated_at' => $get('updated_at'),
  ];
}
?>

<div class="container my-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">Cocktails (<?= count($cocktails ?? []) ?>)</h5>
    <input id="cocktailSearch" type="search" class="form-control" placeholder="Rechercher (nom, slug, description…)">
  </div>

  <div class="table-responsive">
    <table id="cocktailsTable" class="table table-striped table-hover align-middle">
      <thead class="thead-light">
        <tr>
          <th style="width:70px;">#</th>
          <th>Nom</th>
          <th class="d-none d-md-table-cell">Slug</th>
          <th class="d-none d-lg-table-cell">Auteur</th>
          <th class="d-none d-lg-table-cell">Créé</th>
          <th class="d-none d-xl-table-cell">MAJ</th>
          <th>Description</th>
          <th style="width:110px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($cocktails)): ?>
          <?php foreach ($cocktails as $row): ?>
            <?php $c = pickAssoc($row); ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td class="font-weight-600">
                <a href="<?= 'cocktail.php?slug=' . urlencode((string)$c['slug']) ?>">
                  <?= e((string)$c['name']) ?>
                </a>
              </td>
              <td class="d-none d-md-table-cell text-monospace"><?= e((string)$c['slug']) ?></td>
              <td class="d-none d-lg-table-cell">#<?= (int)$c['author_id'] ?></td>
              <td class="d-none d-lg-table-cell">
                <?= e((string)$c['created_at']) ?>
              </td>
              <td class="d-none d-xl-table-cell">
                <?= e((string)$c['updated_at']) ?>
              </td>
              <td>
                <div class="text-truncate-2" title="<?= e((string)$c['description']) ?>">
                  <?= e((string)$c['description']) ?>
                </div>
              </td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a class="btn btn-outline-secondary" href="<?= 'cocktail.php?slug=' . urlencode((string)$c['slug']) ?>">
                    Voir
                  </a>
                  <a class="btn btn-outline-primary" href="<?= 'cocktail_edit.php?id=' . (int)$c['id'] ?>">
                    Éditer
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Aucun cocktail.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
/* clamp 2 lignes pour la description */
.text-truncate-2{
  display:-webkit-box;
  -webkit-box-orient:vertical;
  -webkit-line-clamp:2;
  overflow:hidden;
}
</style>

<script>
// Recherche ultra simple (client-side)
(function () {
  var input = document.getElementById('cocktailSearch');
  var table = document.getElementById('cocktailsTable');
  if (!input || !table) return;

  input.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
      var r = rows[i];
      // on concatène quelques colonnes (nom, slug, description)
      var nom = (r.cells[1]?.innerText || '').toLowerCase();
      var slug = (r.cells[2]?.innerText || '').toLowerCase();
      var desc = (r.cells[6]?.innerText || '').toLowerCase();
      var show = !q || nom.includes(q) || slug.includes(q) || desc.includes(q);
      r.style.display = show ? '' : 'none';
    }
  });
})();
</script>
