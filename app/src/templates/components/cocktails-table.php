<?php
/** @var array $cocktails */

// petite utilitaire pour échapper proprement
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// function pickAssoc(array $row): array {
//   // on ne garde que les clés utiles, en priorisant les clés associatives
//   $get = function($k) use ($row) {
//     if (array_key_exists($k, $row)) return $row[$k];
//     // fallback très prudent sur les index numériques présents dans ton dump (optionnel)
//     $map = [
//       'a.username' => 0, 'c.slug' => 1, 'c.name' => 2,
//       'c.description' => 3, 'c.created_at' => 4,
//     ];
//     return isset($map[$k], $row[$map[$k]]) ? $row[$map[$k]] : null;
//   };

//   return [
//     'username' => $get('a.username'),
//     'slug' => $get('c.slug'),
//     'name' => $get('c.name'),
//     'description' => $get('c.description'),
//     'created_at' => $get('c.created_at'),
//   ];
// }
?>

<div class="container my-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 id="cocktailsLength" class="mb-0"><?= count($cocktails ?? []) ?> Potion(s)</h5>
    <input id="cocktailSearch" type="search" class="form-control" placeholder="Rechercher une Potion...">
  </div>

  <div class="table-responsive">
    <table id="cocktailsTable" class="table table-striped table-hover align-middle">
      <thead class="thead-light">
        <tr>
          <th>Nom</th>
          <th class="d-none d-lg-table-cell">Auteur</th>
          <th class="d-none d-lg-table-cell">Créé</th>
          <th>Description</th>
          <th style="width:110px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($cocktails)): ?>
          <?php foreach ($cocktails as $row): ?>
            <?php $c = $row; ?>
            <tr>
              <td class="font-weight-600">
                <a href="<?= 'cocktails/' . urlencode((string)$c['slug']) ?>">
                  <?= e((string)$c['name']) ?>
                </a>
              </td>
              <td class="d-none d-lg-table-cell"><?= (string)$c['username'] ?></td>
              <td class="d-none d-lg-table-cell">
                <?= e((string)$c['created_at']) ?>
              </td>
              <td>
                <div class="text-truncate-2" title="<?= e((string)$c['description']) ?>">
                  <?= e((string)$c['description']) ?>
                </div>
              </td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a class="btn btn-outline-secondary" href="<?= 'cocktails/' . urlencode((string)$c['slug']) ?>">
                    Voir
                  </a>
                  <a class="btn btn-outline-primary" href="<?= 'cocktails/edit/' . (string)$c['slug'] ?>">
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
