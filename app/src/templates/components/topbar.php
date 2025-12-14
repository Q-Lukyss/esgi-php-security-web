<?php declare(strict_types=1);

/** @var \App\Classes\SessionUser|null $user */

$is_connected = isset($user) && $user !== null;

$display = $is_connected
    ? htmlspecialchars($user->getUsername(), ENT_QUOTES, "UTF-8")
    : null;

$loginUrl = "/connexion";
$accountUrl = "/me";
$logoutUrl = "/logout";
?>

<div class="container-fluid bg-primary py-3 d-none d-md-block">
  <div class="container">
    <div class="row">
      <div class="col-md-6 text-center text-lg-left mb-2 mb-lg-0">
        <div class="d-inline-flex align-items-center">
          <?php if ($is_connected): ?>
            <span class="text-white pr-3">Bon retour</span>
            <span class="text-white">|</span>
            <span class="text-white px-3"><?= $display ?></span>
          <?php else: ?>
            <span class="text-white pr-3">Bienvenue</span>
            <span class="text-white">|</span>
            <a class="text-white px-3" href="<?= $loginUrl ?>">Se connecter</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-md-6 text-center text-lg-right">
        <div class="d-inline-flex align-items-center">
          <?php if ($is_connected): ?>
            <a class="text-white px-3" href="<?= $accountUrl ?>">Mon Compte</a>
            <span class="text-white">|</span>
            <a class="text-white px-3" href="<?= $logoutUrl ?>">Déconnexion</a>
          <?php else: ?>
            <a class="btn btn-secondary btn-sm px-4" href="<?= $loginUrl ?>">Connexion</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
