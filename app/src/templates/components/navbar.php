<?php
    $current_page = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);;
?>

<!-- Navbar Start -->
    <div class="container-fluid position-relative nav-bar p-0">
        <div class="container-lg position-relative p-0 px-lg-3" style="z-index: 9;">
            <nav class="navbar navbar-expand-lg bg-white navbar-light shadow p-lg-0">
                <a href="/" class="navbar-brand d-block d-lg-none">
                    <h1 class="m-0 display-4 text-primary"><span class="text-secondary">Potion</span>MAGIQUE</h1>
                </a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav ml-auto py-0">
                        <a href="/druides" class="nav-item nav-link <?= ($current_page == '/druides' ? 'active' : '') ?>">Nos Druides</a>
                        <!-- <a href="/about" class="nav-item nav-link <?= ($current_page == '/about' ? 'active' : '') ?>">A Propos</a> -->
                    </div>
                    <div class="d-flex align-items-center">
                        <img src="/img/obelix.png" alt="obelix" style="height: 48px; width: auto;" />
                        <a href="/" class="navbar-brand mx-2 d-none d-lg-block">
                            <h1 class="m-0 display-4 text-primary"><span class="text-secondary">Potion</span>MAGIQUE</h1>
                        </a>
                        <img src="/img/panoramix.png" alt="panoramix" style="height: 48px; width: auto;" />
                    </div>
                    
                    <div class="navbar-nav mr-auto py-0">
                        <a href="/cocktails" class="nav-item nav-link <?= ($current_page == '/cocktails' ? 'active' : '') ?>">Nos Potions</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->