// =============================================
// DANGPANAN — MAP PAGE
// Supports two modes via window.NAV_CONFIG:
//   mode: 'explore'    — shows all shelters
//   mode: 'navigation' — guides evacuee to approved shelter
// =============================================

let map = null;
let userMarker = null;
let shelterMarker = null;
let routePolyline = null;
let currentUserLocation = null;
let liveTrackingInterval = null;

// ── Haversine distance (km) ──
function calcDistance(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// ── ETA string ──
function calcETA(km) {
  const mins = Math.round((km / 30) * 60);
  if (mins < 1) return "Less than 1 min";
  if (mins < 60) return `${mins} min`;
  return `${Math.floor(mins / 60)}h ${mins % 60}m`;
}

// ── Hide loading overlay ──
function hideLoading() {
  const el = document.getElementById("mapLoading");
  if (el) el.style.display = "none";
}

// ── Update nav status pill ──
function setNavStatus(text, type) {
  const pill = document.getElementById("navStatusPill");
  const txt = document.getElementById("navStatusText");
  if (!pill || !txt) return;
  txt.textContent = text;
  pill.className =
    "nav-status-pill" +
    (type === "active"
      ? " status-active"
      : type === "arrived"
        ? " status-arrived"
        : "");
}

// ── Leaflet icon: user (pulsing blue dot) ──
function makeUserIcon() {
  return L.divIcon({
    className: "user-location-marker",
    html: '<div class="user-dot"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  });
}

// ── Leaflet icon: shelter destination (green pin) ──
function makeShelterIcon() {
  return L.icon({
    iconUrl:
      "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png",
    shadowUrl:
      "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  });
}

// ── Leaflet icon: explore mode shelter dots ──
function makeExploreIcon(color) {
  return L.divIcon({
    className: "custom-marker",
    html: `<div style="background:${color};width:20px;height:20px;border-radius:50%;border:3px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`,
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  });
}

// ── Init Leaflet map ──
function initMap(lat, lng, zoom) {
  map = L.map("fullMap").setView([lat, lng], zoom);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(map);
  L.control.scale().addTo(map);
  window.addEventListener("resize", () => map && map.invalidateSize());
}

// ── Draw / redraw route polyline ──
function drawRoute(userLat, userLng, shelterLat, shelterLng) {
  if (routePolyline) map.removeLayer(routePolyline);
  routePolyline = L.polyline(
    [
      [userLat, userLng],
      [shelterLat, shelterLng],
    ],
    { color: "#3b82f6", weight: 5, opacity: 0.85, dashArray: "10, 8" },
  ).addTo(map);
}

// ── Update HUD distance + ETA ──
function updateHUD(userLat, userLng, shelterLat, shelterLng) {
  const dist = calcDistance(userLat, userLng, shelterLat, shelterLng);
  const eta = calcETA(dist);
  const distEl = document.getElementById("hudDistance");
  const etaEl = document.getElementById("hudETA");
  if (distEl)
    distEl.textContent =
      dist < 1 ? `${Math.round(dist * 1000)} m` : `${dist.toFixed(2)} km`;
  if (etaEl) etaEl.textContent = eta;
}

// ── Update Google Maps link ──
function updateGMapsLink(userLat, userLng, shelterLat, shelterLng) {
  const btn = document.getElementById("btnGoogleMaps");
  if (btn)
    btn.href = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${shelterLat},${shelterLng}&travelmode=driving`;
}

// =============================================
// EXPLORE MODE
// =============================================
function runExploreMode() {
  initMap(10.676, 122.96, 12);

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        const { latitude: lat, longitude: lng } = coords;
        currentUserLocation = { lat, lng };
        L.marker([lat, lng], { icon: makeUserIcon() })
          .addTo(map)
          .bindPopup("<b>You Are Here</b>")
          .openPopup();
        map.setView([lat, lng], 14);
        hideLoading();
      },
      () => hideLoading(),
    );
  } else {
    hideLoading();
  }

  fetch("api/get_shelters.php")
    .then((r) => r.json())
    .then((data) => {
      if (!data.success || !data.shelters) return;
      data.shelters.forEach((shelter) => {
        if (!shelter.latitude || !shelter.longitude) return;
        const color =
          shelter.status === "full"
            ? "#ef4444"
            : shelter.status === "limited"
              ? "#fbbf24"
              : "#4ade80";

        const popup = `
                    <div style="min-width:230px;">
                        <h3 style="margin:0 0 8px;font-size:15px;font-weight:700;color:#1f2937;">${shelter.shelter_name}</h3>
                        <div style="background:#f3f4f6;padding:8px;border-radius:6px;margin-bottom:8px;">
                            <p style="margin:3px 0;font-size:13px;"><strong>📍</strong> ${shelter.location || "Not specified"}</p>
                            ${shelter.contact_number ? `<p style="margin:3px 0;font-size:13px;"><strong>📞</strong> ${shelter.contact_number}</p>` : ""}
                        </div>
                        <div style="background:#eff6ff;padding:8px;border-radius:6px;margin-bottom:8px;">
                            <p style="margin:3px 0;font-size:13px;"><strong>Capacity:</strong> ${shelter.current_capacity}/${shelter.max_capacity}</p>
                            <div style="background:#e5e7eb;height:8px;border-radius:4px;overflow:hidden;margin-top:4px;">
                                <div style="background:${color};height:100%;width:${shelter.capacity_percentage}%;"></div>
                            </div>
                        </div>
                        <p style="margin:0;font-size:12px;">
                            <span style="padding:2px 8px;border-radius:4px;background:${color};color:white;font-size:11px;font-weight:700;text-transform:uppercase;">${shelter.status}</span>
                        </p>
                    </div>`;

        L.marker(
          [parseFloat(shelter.latitude), parseFloat(shelter.longitude)],
          { icon: makeExploreIcon(color) },
        )
          .addTo(map)
          .bindPopup(popup);
      });
    })
    .catch((err) => console.error("Shelter load error:", err));
}

// =============================================
// NAVIGATION MODE
// =============================================
function runNavigationMode(requestId) {
  // Fetch approved request from server
  fetch(`index.php?route=get_request_details&request_id=${requestId}`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.success || !data.request) {
        showNavError(
          "Could not load your request. Please return to the portal and try again.",
        );
        return;
      }

      const req = data.request;

      if (!req.latitude || !req.longitude) {
        showNavErrorWithOptions(
          "Shelter location coordinates are not available.",
          req,
        );
        return;
      }

      const shelterLat = parseFloat(req.latitude);
      const shelterLng = parseFloat(req.longitude);

      // Fill HUD shelter name
      const nameEl = document.getElementById("hudShelterName");
      if (nameEl) nameEl.textContent = req.shelter_name;

      // Show chat button
      const chatBtn = document.getElementById("btnChatShelter");
      if (chatBtn) {
        chatBtn.href = `index.php?route=chat&request_id=${requestId}`;
        chatBtn.style.display = "flex";
      }

      // Init map centered on shelter while waiting for user GPS
      initMap(shelterLat, shelterLng, 15);

      // Place shelter marker right away
      shelterMarker = L.marker([shelterLat, shelterLng], {
        icon: makeShelterIcon(),
      })
        .addTo(map)
        .bindPopup(
          `<b>🎯 DESTINATION</b><br><strong>${req.shelter_name}</strong>`,
        )
        .openPopup();

      // Get user GPS
      if (!navigator.geolocation) {
        showNavError("Geolocation is not supported by your browser.");
        hideLoading();
        return;
      }

      setNavStatus("Detecting your location...", "normal");

      navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
          const userLat = coords.latitude;
          const userLng = coords.longitude;
          currentUserLocation = { lat: userLat, lng: userLng };
          onBothLocationsReady(userLat, userLng, shelterLat, shelterLng, req);
        },
        (err) => {
          console.warn("GPS error:", err.message);
          setNavStatus("GPS unavailable — shelter shown on map", "normal");
          hideLoading();
          map.setView([shelterLat, shelterLng], 15);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
      );
    })
    .catch((err) => {
      console.error("Request fetch error:", err);
      showNavError(
        "Network error loading your request. Please check your connection.",
      );
    });
}

// Called once we have user GPS + shelter coords
function onBothLocationsReady(userLat, userLng, shelterLat, shelterLng, req) {
  hideLoading();

  // User marker
  userMarker = L.marker([userLat, userLng], { icon: makeUserIcon() })
    .addTo(map)
    .bindPopup("<b>📍 YOU ARE HERE</b>");

  // Fit both markers in view
  map.fitBounds(L.latLngBounds([userLat, userLng], [shelterLat, shelterLng]), {
    padding: [60, 60],
  });

  // Draw route
  drawRoute(userLat, userLng, shelterLat, shelterLng);

  // Update HUD
  updateHUD(userLat, userLng, shelterLat, shelterLng);
  updateGMapsLink(userLat, userLng, shelterLat, shelterLng);

  setNavStatus("Navigating to shelter", "active");

  // Start live tracking
  liveTrackingInterval = setInterval(() => {
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        const lat = coords.latitude;
        const lng = coords.longitude;
        currentUserLocation = { lat, lng };

        if (userMarker) userMarker.setLatLng([lat, lng]);
        drawRoute(lat, lng, shelterLat, shelterLng);
        updateHUD(lat, lng, shelterLat, shelterLng);
        updateGMapsLink(lat, lng, shelterLat, shelterLng);

        // Arrived check — within 50 m
        if (calcDistance(lat, lng, shelterLat, shelterLng) < 0.05) {
          clearInterval(liveTrackingInterval);
          showArrivalBanner(req.shelter_name);
        }
      },
      (err) => console.warn("Live GPS failed:", err.message),
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
    );
  }, 5000);
}

// ── Arrival banner ──
function showArrivalBanner(shelterName) {
  setNavStatus("You have arrived! 🎉", "arrived");
  const banner = document.getElementById("arrivalBanner");
  if (!banner) return;
  const strong = banner.querySelector("strong");
  if (strong) strong.textContent = `You've Arrived at ${shelterName}!`;
  banner.style.display = "flex";
}

// ── Nav error display ──
function showNavError(msg) {
  hideLoading();
  setNavStatus("Error", "normal");
  const hud = document.getElementById("navHUD");
  if (hud) {
    hud.innerHTML = `
            <div class="hud-error">
                <i data-lucide="alert-triangle"></i>
                <p>${msg}</p>
                <a href="index.php?route=evacuee_portal" class="btn-back-portal">
                    <i data-lucide="arrow-left"></i> Return to Portal
                </a>
            </div>`;
    if (typeof lucide !== "undefined") lucide.createIcons();
  }
}

// =============================================
// ENTRY POINT
// =============================================
document.addEventListener("DOMContentLoaded", function () {
  if (typeof lucide !== "undefined") lucide.createIcons();

  // Read config from body data attributes (set by PHP view, no inline script needed)
  const body = document.body;
  const navMode = body.dataset.navMode === "1";
  const requestId = parseInt(body.dataset.requestId || "0", 10);

  if (navMode && requestId) {
    runNavigationMode(requestId);
  } else {
    runExploreMode();
  }
});
