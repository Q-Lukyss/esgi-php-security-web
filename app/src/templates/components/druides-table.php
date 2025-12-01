<?php
/** @var array $druides */

// petite utilitaire pour échapper proprement
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// function pickAssoc(array $row): array {
//   $get = function($k) use ($row) {
//     if (array_key_exists($k, $row)) return $row[$k];
//     $map = [
//       'id' => 0, 'role' => 1, 'display_name' => 2,
//       'bio' => 3, 'avatar_url' => 4,
//     ];
//     return isset($map[$k], $row[$map[$k]]) ? $row[$map[$k]] : null;
//   };

//   return [
//     'id' => $get('id'),
//     'role' => $get('role'),
//     'display_name' => $get('display_name'),
//     'bio' => $get('bio'),
//     'avatar_url' => $get('avatar_url'),
//   ];
// }
?>

<div class="container my-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 id="druideLength" class="mb-0"><?= count($druides ?? []) ?> Druides(s)</h5>
    <input id="druideSearch" type="search" class="form-control" placeholder="Chercher un Druide...">
  </div>

  <div class="table-responsive">
    <table id="druideTable" class="table table-striped table-hover align-middle">
      <thead class="thead-light">
        <tr>
          <th>Druide</th>
          <th>Type de compte</th>
          <th>Bio</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($druides)): ?>
          <?php foreach ($druides as $row): ?>
            <?php $c = $row; ?>
            <tr>
              <td class="font-weight-600">
                <?= e((string)$c['avatar_url']) ?>
                <a href="<?= 'druides/' . urlencode((string)$c['id']) ?>">
                  <?= e((string)$c['display_name']) ?>
                </a>
              </td>
              <td>
                <?= e((string)$c['role']) ?>
              </td>
              <td>
                <?= e((string)$c['bio']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Aucun Druide ne souhaite se revéler à vous.</td></tr>
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
