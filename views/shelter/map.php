<?php 
require_once 'config/auth_guard.php'; 
protect_page(); 

// Detect navigation mode — passed from evacuee portal via URL
$navMode    = isset($_GET['nav']) && $_GET['nav'] === '1';
$requestId  = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>DANGPANAN | <?php echo $navMode ? 'Navigation' : 'Live Protocol Map'; ?></title>

    <!-- Pass PHP config to JS via data attributes on body (no inline script needed) -->
    <!-- map.js reads document.body.dataset.navMode and dataset.requestId -->

    <link rel="stylesheet" href="assets/css/map.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/nav.js" defer></script>
    <script src="assets/js/map.js" defer></script>
</head>
<body class="light-portal"
      data-nav-mode="<?php echo $navMode ? '1' : '0'; ?>"
      data-request-id="<?php echo $requestId; ?>">

    <?php require __DIR__ . '/../partials/nav_portal.php'; ?>

    <main class="portal-container">

        <?php if ($navMode): ?>
        <!-- NAVIGATION MODE HEADER -->
        <section class="portal-header nav-mode-header">
            <div class="nav-header-left">
                <a href="index.php?route=evacuee_portal" class="btn-back-portal">
                    <i data-lucide="arrow-left"></i>
                    Back to Portal
                </a>
                <div>
                    <h1 class="page-title">Navigation</h1>
                    <p class="nav-subtitle">Guiding you to your approved shelter</p>
                </div>
            </div>
            <div class="nav-status-pill" id="navStatusPill">
                <span class="nav-status-dot"></span>
                <span id="navStatusText">Detecting location...</span>
            </div>
        </section>

        <!-- NAVIGATION HUD -->
        <div class="nav-hud" id="navHUD">
            <div class="hud-destination" id="hudDestination">
                <div class="hud-dest-icon">
                    <i data-lucide="home"></i>
                </div>
                <div class="hud-dest-info">
                    <span class="hud-dest-label">Destination</span>
                    <strong class="hud-dest-name" id="hudShelterName">Loading...</strong>
                </div>
            </div>

            <div class="hud-metrics-row">
                <div class="hud-metric">
                    <i data-lucide="route"></i>
                    <div>
                        <span class="hud-metric-label">Distance</span>
                        <span class="hud-metric-val" id="hudDistance">—</span>
                    </div>
                </div>
                <div class="hud-metric">
                    <i data-lucide="clock"></i>
                    <div>
                        <span class="hud-metric-label">ETA</span>
                        <span class="hud-metric-val" id="hudETA">—</span>
                    </div>
                </div>
            </div>

            <div class="hud-actions">
                <a id="btnGoogleMaps" href="#" target="_blank" class="btn-hud-gmaps">
                    <i data-lucide="map"></i>
                    Open in Google Maps
                </a>
                <a id="btnCallShelter" href="#" class="btn-hud-call" style="display:none;">
                    <i data-lucide="phone"></i>
                    Call Shelter
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- EXPLORE MODE HEADER -->
        <section class="portal-header">
            <h1 class="page-title">Live Protocol Map</h1>
            <p class="map-subtitle">All available shelters in your area</p>
        </section>
        <?php endif; ?>

        <!-- MAP CONTAINER -->
        <div class="map-section <?php echo $navMode ? 'nav-map-section' : ''; ?>">
            <div id="fullMap" style="height:100%; width:100%;"></div>

            <?php if (!$navMode): ?>
            <div class="map-legend-overlay">
                <div class="legend-item"><span class="legend-dot green"></span> Available</div>
                <div class="legend-item"><span class="legend-dot yellow"></span> Limited</div>
                <div class="legend-item"><span class="legend-dot red"></span> Full</div>
            </div>
            <?php endif; ?>

            <div class="map-loading" id="mapLoading">
                <div class="map-loading-inner">
                    <i data-lucide="loader-2" class="spin-icon"></i>
                    <span><?php echo $navMode ? 'Getting your location...' : 'Loading map...'; ?></span>
                </div>
            </div>
        </div>

        <div id="arrivalBanner" class="arrival-banner" style="display:none;">
            <div class="arrival-banner-inner">
                <span class="arrival-check">✅</span>
                <div>
                    <strong>You've Arrived!</strong>
                    <p>Welcome to your shelter. Please check in with the host.</p>
                </div>
                <button onclick="document.getElementById('arrivalBanner').style.display='none'">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
