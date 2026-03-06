<?php
/**
 * views/shelter/alerts.php
 * VIEW ONLY — no logic, no inline CSS, no inline JS.
 * Data ($alerts, $counts, $totalActive) provided by AlertController::showAlertsPage()
 * human_time_diff() helper moved to AlertController or AlertModel
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Emergency Alerts</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/alerts.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/nav.js" defer></script>
    <link rel="stylesheet" href="assets/css/alerts.css">
</head>
<body class="light-portal">
    <?php require __DIR__ . '/../partials/nav_portal.php'; ?>

    <main class="portal-container">
        <div class="portal-layout">
            <div class="main-content">

                <!-- Header -->
                <section class="portal-header">
                    <div class="header-info">
                        <h1 class="page-title">Emergency Alerts</h1>
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:0.5rem;margin-top:0.25rem;">
                            <p class="user-id" style="margin:0;">LIVE FEED • <span>BACOLOD CITY</span></p>
                            <span class="refresh-badge">
                                <span class="refresh-dot"></span>
                                Auto-refresh: <span id="refreshCountdown">30</span>s
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Metric Cards (live from DB) -->
                <section class="metrics-grid">
                    <div class="metric-card" style="border-top:4px solid var(--primary-red);">
                        <span class="metric-label">Critical Alerts</span>
                        <h3 class="metric-value" style="color:var(--primary-red);" id="count-critical"><?= $counts['critical'] ?></h3>
                        <p class="metric-detail">Immediate Action Required</p>
                    </div>
                    <div class="metric-card" style="border-top:4px solid var(--warning-amber);">
                        <span class="metric-label">Active Warnings</span>
                        <h3 class="metric-value" style="color:var(--warning-amber);" id="count-warning"><?= $counts['warning'] ?></h3>
                        <p class="metric-detail">Monitor Conditions</p>
                    </div>
                    <div class="metric-card" style="border-top:4px solid #3b82f6;">
                        <span class="metric-label">General Info</span>
                        <h3 class="metric-value" style="color:#3b82f6;" id="count-info"><?= $counts['info'] ?></h3>
                        <p class="metric-detail">Community Updates</p>
                    </div>
                </section>

                <!-- Filters -->
                <div class="search-bar" style="margin-bottom:2rem;">
                    <div class="alert-filters">
                        <button class="filter-btn active" data-filter="all">ALL (<?= $totalActive ?>)</button>
                        <button class="filter-btn" data-filter="critical">CRITICAL</button>
                        <button class="filter-btn" data-filter="warning">WARNINGS</button>
                        <button class="filter-btn" data-filter="info">UPDATES</button>
                    </div>
                </div>

                <!-- Alerts Feed -->
                <div class="alerts-feed" id="alertsFeed" style="display:grid;gap:1rem;">
                    <?php if(empty($alerts)): ?>
                    <div class="empty-alerts">
                        <i data-lucide="bell-off" style="color:#cbd5e1;"></i>
                        <p>No active alerts at this time. Stay safe!</p>
                    </div>
                    <?php else: ?>
                    <?php foreach($alerts as $i => $alert):
                        $isNew = (time() - strtotime($alert['created_at'])) < 3600;
                        $iconMap = ['critical'=>'alert-triangle','warning'=>'droplets','info'=>'info'];
                        $colorMap = ['critical'=>['#fee2e2','#ef4444'],'warning'=>['#fef3c7','#f59e0b'],'info'=>['#dbeafe','#3b82f6']];
                        $btnColorMap = ['critical'=>'#ef4444','warning'=>'#f59e0b','info'=>'#3b82f6'];
                        $icon   = $iconMap[$alert['type']] ?? 'bell';
                        $colors = $colorMap[$alert['type']] ?? ['#f1f5f9','#64748b'];
                        $btnColor = $btnColorMap[$alert['type']] ?? '#64748b';
                        $timeAgo = AlertModel::humanTimeDiff($alert['created_at']);
                    ?>
                    <div class="shelter-row-card alert-card <?= $alert['type'] ?>" data-type="<?= $alert['type'] ?>">
                        <div style="flex:1;min-width:0;">
                            <div class="shelter-main">
                                <div class="shelter-icon-box" style="background:<?= $colors[0] ?>;color:<?= $colors[1] ?>;">
                                    <i data-lucide="<?= $icon ?>"></i>
                                </div>
                                <div class="shelter-details" style="flex:1;min-width:0;">
                                    <h4 class="shelter-name">
                                        <?= htmlspecialchars($alert['title']) ?>
                                        <?php if($isNew): ?><span class="alert-new-badge">NEW</span><?php endif; ?>
                                    </h4>
                                    <p class="shelter-sub"><?= htmlspecialchars(mb_strimwidth($alert['body'],0,120,'…')) ?></p>
                                    <small style="color:#64748b;font-weight:600;"><?= $timeAgo ?> • Source: <?= htmlspecialchars($alert['source'] ?: 'DANGPANAN') ?></small>
                                </div>
                                <div class="shelter-meta">
                                    <button class="btn-request-action" style="background:<?= $btnColor ?>;"
                                        onclick="toggleAlert(<?= $i ?>)">READ MORE</button>
                                </div>
                            </div>

                            <!-- Expandable body -->
                            <div class="alert-body" id="alert-body-<?= $i ?>">
                                <p class="alert-body-text"><?= nl2br(htmlspecialchars($alert['body'])) ?></p>
                                <?php if(!empty($alert['affected_area'])): ?>
                                <div class="alert-area-chips">
                                    <strong style="font-size:0.72rem;color:#64748b;margin-right:4px;">AFFECTED:</strong>
                                    <?php foreach(explode(',',$alert['affected_area']) as $area): ?>
                                    <?php if(trim($area)): ?>
                                    <span class="area-chip"><?= htmlspecialchars(trim($area)) ?></span>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <?php if($alert['type']==='critical'||$alert['type']==='warning'): ?>
                                <div style="margin-top:0.75rem;">
                                    <a href="index.php?route=maps" class="btn-request-action" style="background:#0f172a;display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                                        <i data-lucide="map" style="width:14px;"></i> View Map
                                    </a>
                                </div>
                                <?php else: ?>
                                <div style="margin-top:0.75rem;">
                                    <a href="index.php?route=evacuee_portal" class="btn-request-action" style="background:#0f172a;display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                                        <i data-lucide="home" style="width:14px;"></i> Find Shelter
                                    </a>
                                </div>
                                <?php endif; ?>
                                <button class="btn-toggle" onclick="toggleAlert(<?= $i ?>)" style="margin-top:0.5rem;">
                                    <i data-lucide="chevron-up" style="width:14px;"></i> Show less
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div><!-- end main-content -->

            <!-- Sidebar -->
            <aside class="sidebar">

                <!-- System Status -->
                <div class="sidebar-panel">
                    <h3 class="panel-title">System Status</h3>
                    <div style="padding:0.875rem;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;">
                        <p style="color:#166534;font-size:0.82rem;font-weight:600;display:flex;align-items:center;gap:8px;">
                            <span style="width:8px;height:8px;background:#22c55e;border-radius:50%;"></span>
                            All systems operational
                        </p>
                    </div>
                    <div style="margin-top:0.75rem;font-size:0.78rem;color:#64748b;">
                        Last updated: <span id="lastUpdated"><?= date('h:i A') ?></span>
                    </div>
                </div>

                <!-- Emergency Hotlines -->
                <div class="sidebar-panel">
                    <h3 class="panel-title">Emergency Hotlines</h3>
                    <div class="hotline-row">
                        <span class="hotline-name">🚑 NDRRMC</span>
                        <a href="tel:911" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>911</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">🔴 Philippine Red Cross</span>
                        <a href="tel:143" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>143</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">🚒 BFP (Fire)</span>
                        <a href="tel:160" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>160</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">👮 PNP (Police)</span>
                        <a href="tel:117" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>117</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">🌩️ PAGASA</span>
                        <a href="tel:+63-2-8284-0800" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>(02) 8284-0800</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">🏥 Bacolod CDRRMO</span>
                        <a href="tel:+63344330695" class="hotline-number"><i data-lucide="phone" style="width:13px;"></i>(034) 433-0695</a>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">📻 DZMM TeleRadyo</span>
                        <span style="font-size:0.82rem;font-weight:800;color:#64748b;">630 AM</span>
                    </div>
                    <div class="hotline-row">
                        <span class="hotline-name">📻 Bombo Radyo</span>
                        <span style="font-size:0.82rem;font-weight:800;color:#64748b;">1035 AM</span>
                    </div>
                </div>

                <!-- Active Shelters quick link -->
                <div class="sidebar-panel">
                    <h3 class="panel-title">Find Shelter</h3>
                    <!-- $sData provided by AlertController::showAlertsPage() -->
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <div style="width:44px;height:44px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="home" style="width:20px;color:#16a34a;"></i>
                        </div>
                        <div>
                            <p style="font-size:1.5rem;font-weight:800;color:#0f172a;"><?= $sData['c'] ?></p>
                            <p style="font-size:0.75rem;color:#64748b;font-weight:600;">Active Shelters</p>
                        </div>
                    </div>
                    <p style="font-size:0.8rem;color:#64748b;margin-bottom:0.75rem;">
                        <?= max(0,(int)$sData['avail']) ?> spots available across all centers.
                    </p>
                    <a href="index.php?route=evacuee_portal" class="btn-request-action" style="background:var(--primary-red);display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;padding:0.75rem;border-radius:8px;font-weight:700;font-size:0.85rem;color:white;">
                        <i data-lucide="map-pin" style="width:15px;"></i> Find Nearest Shelter
                    </a>
                </div>

                <!-- Safety Tips (rotate based on alert type) -->
                <div class="sidebar-panel">
                    <h3 class="panel-title">Safety Tips</h3>
                    <div class="tip-card" id="safetyTip">
                        <div class="tip-label">⚠️ General Safety</div>
                        <p id="safetyTipText">Keep an emergency go-bag ready with water, food, documents, and medicines for at least 3 days.</p>
                    </div>
                    <button class="btn-toggle" onclick="nextTip()" style="margin-top:0.75rem;width:100%;justify-content:center;">
                        <i data-lucide="refresh-cw" style="width:14px;"></i> Next Tip
                    </button>
                </div>

            </aside>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <script src="assets/js/alerts.js" defer></script>
</body>
</html>
