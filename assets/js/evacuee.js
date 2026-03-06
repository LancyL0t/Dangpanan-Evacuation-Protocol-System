// =============================================
// DANGPANAN ENHANCED EVACUEE PORTAL
// Request Progress Tracker & Active Navigation System
// =============================================

let map = null;
let userMarker = null;
let shelterMarkers = [];
let routePolyline = null;
let activeNavigationMode = false;
let currentUserLocation = null;
let navigationUpdateInterval = null;
let activeRequestId = null;

document.addEventListener("DOMContentLoaded", function () {
  lucide.createIcons();

  // Initialize map
  initializeMap();

  // Load user's active requests
  loadUserRequests();

  // Start periodic updates
  startPeriodicUpdates();

  // Setup SOS button
  setupSOSButton();

  // Setup shelter request form
  setupRequestForm();

  // Setup view requests button
  setupViewRequestsButton();

  // Setup shelter search & filter
  setupSearchAndFilter();
});

// =============================================
// MAP INITIALIZATION
// =============================================
function initializeMap() {
  const mapContainer = document.getElementById("evacueeMap");
  if (!mapContainer) return;

  // Initialize Leaflet map
  map = L.map("evacueeMap").setView([10.676, 122.96], 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Get user's current location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        currentUserLocation = { lat, lng };

        // Center map on user
        map.setView([lat, lng], 14);

        // Create "You Are Here" marker
        const userIcon = L.icon({
          iconUrl:
            "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png",
          shadowUrl:
            "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41],
        });

        userMarker = L.marker([lat, lng], { icon: userIcon })
          .addTo(map)
          .bindPopup("<b>📍 YOU ARE HERE</b><br>Your current location")
          .openPopup();

        // Update location card
        updateLocationCard(lat, lng);
      },
      (error) => {
        console.warn("Location access denied:", error);
      },
    );
  }

  // Load shelters on map
  loadSheltersOnMap();
}

// =============================================
// SHELTER MAP MARKERS
// =============================================
function loadSheltersOnMap() {
  fetch("api/get_shelters.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.shelters) {
        data.shelters.forEach((shelter) => {
          if (shelter.latitude && shelter.longitude) {
            let color = "#4ade80"; // Green
            if (shelter.status === "full") color = "#ef4444"; // Red
            if (shelter.status === "limited") color = "#fbbf24"; // Yellow

            const shelterIcon = L.divIcon({
              className: "custom-shelter-marker",
              html: `<div style="background-color: ${color}; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
              iconSize: [15, 15],
              iconAnchor: [7.5, 7.5],
            });

            const marker = L.marker(
              [parseFloat(shelter.latitude), parseFloat(shelter.longitude)],
              { icon: shelterIcon },
            ).addTo(map);

            marker.bindPopup(`
                            <strong>${shelter.shelter_name}</strong><br>
                            Capacity: ${shelter.current_capacity}/${shelter.max_capacity}<br>
                            <button onclick="handleRequest('${shelter.shelter_id}', '${shelter.shelter_name.replace(/'/g, "\\'")}')" 
                            style="margin-top:5px; background:#0d9488; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">
                            Request Here
                            </button>
                        `);

            shelterMarkers.push({
              marker: marker,
              shelter: shelter,
            });
          }
        });
      }
    })
    .catch((error) => console.error("Error loading shelters:", error));
}

// =============================================
// REQUEST PROGRESS TRACKER
// =============================================
function loadUserRequests() {
  console.log("🔄 Loading requests from database...");

  fetch("index.php?route=get_user_requests")
    .then((response) => response.json())
    .then((data) => {
      console.log("📥 API Response:", data);

      if (data.success && data.requests && data.requests.length > 0) {
        console.log(
          "✅ Found",
          data.requests.length,
          "active request(s) in database",
        );
        console.log("📊 Request details:", data.requests);

        // Store requests globally
        window.userActiveRequests = data.requests;

        // Update the view requests button state
        updateViewRequestsButton(data.requests);

        // Update active request count in metric card
        const countEl = document.getElementById("activeRequestCount");
        if (countEl) {
          countEl.textContent = data.requests.length;
        }
      } else {
        console.log("ℹ️ No active requests found in database");
        window.userActiveRequests = [];
        updateViewRequestsButton([]);
      }
    })
    .catch((error) => {
      console.error("❌ Error loading requests from database:", error);
    });
}

function displayRequestTracker(requests) {
  // Get or create tracker container
  let trackerContainer = document.getElementById("requestProgressTracker");

  if (!trackerContainer) {
    trackerContainer = document.createElement("div");
    trackerContainer.id = "requestProgressTracker";
    trackerContainer.className = "request-progress-tracker";

    // Insert after portal header
    const portalHeader = document.querySelector(".portal-header");
    if (portalHeader) {
      portalHeader.after(trackerContainer);
    }
  }

  // Build tracker HTML
  let html = '<div class="tracker-wrapper">';

  requests.forEach((request) => {
    const stage = getRequestStage(request.status);
    const canNavigate = request.status === "approved";

    html += `
            <div class="request-tracker-card ${request.status}">
                <div class="tracker-header">
                    <div class="tracker-title">
                        <i data-lucide="navigation-2"></i>
                        <h3>Request for ${request.shelter_name}</h3>
                    </div>
                    <span class="request-badge ${request.status}">${request.status.toUpperCase()}</span>
                </div>
                
                <div class="progress-stages">
                    <div class="stage ${stage >= 1 ? "completed" : ""}">
                        <div class="stage-icon">
                            <i data-lucide="send"></i>
                        </div>
                        <div class="stage-label">Submitted</div>
                    </div>
                    
                    <div class="stage-line ${stage >= 2 ? "completed" : ""}"></div>
                    
                    <div class="stage ${stage >= 2 ? "completed" : stage === 1 ? "active" : ""}">
                        <div class="stage-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <div class="stage-label">Under Review</div>
                    </div>
                    
                    <div class="stage-line ${stage >= 3 ? "completed" : ""}"></div>
                    
                    <div class="stage ${stage >= 3 ? "completed" : ""}">
                        <div class="stage-icon">
                            <i data-lucide="check-circle"></i>
                        </div>
                        <div class="stage-label">Approved</div>
                    </div>
                </div>
                
                <div class="tracker-details">
                    <div class="detail-item">
                        <i data-lucide="map-pin"></i>
                        <span>${request.location}</span>
                    </div>
                    <div class="detail-item">
                        <i data-lucide="users"></i>
                        <span>${request.group_size} ${request.group_size > 1 ? "people" : "person"}</span>
                    </div>
                    <div class="detail-item">
                        <i data-lucide="calendar"></i>
                        <span>${formatDate(request.created_at)}</span>
                    </div>
                </div>
                
                <div class="tracker-actions">
                    ${
                      canNavigate
                        ? `
                        <button class="btn-guide-me" onclick="activateNavigation(${request.id})">
                            <i data-lucide="navigation"></i>
                            GUIDE ME
                        </button>
                    `
                        : `
                        <button class="btn-track-request" disabled>
                            <i data-lucide="hourglass"></i>
                            TRACK REQUEST
                        </button>
                    `
                    }
                    
                    ${
                      request.contact_number
                        ? `
                        <a href="tel:${request.contact_number}" class="btn-call-shelter">
                            <i data-lucide="phone"></i>
                            CALL SHELTER
                        </a>
                    `
                        : ""
                    }
                </div>
            </div>
        `;
  });

  html += "</div>";

  trackerContainer.innerHTML = html;

  // Re-initialize Lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

function hideRequestTracker() {
  const trackerContainer = document.getElementById("requestProgressTracker");
  if (trackerContainer) {
    trackerContainer.style.display = "none";
  }
}

function getRequestStage(status) {
  switch (status) {
    case "pending":
      return 1; // Submitted + Under Review active
    case "approved":
      return 3; // All stages completed
    case "declined":
      return 0; // Request declined
    default:
      return 0;
  }
}

// =============================================
// ACTIVE NAVIGATION MODE
// =============================================
function activateNavigation(requestId) {
  activeRequestId = requestId;

  // Check if GPS location is available
  if (!currentUserLocation) {
    alert(
      "⚠️ GPS location not available yet.\n\nPlease wait a moment for your location to be detected, then try again.",
    );

    // Try to get GPS if not already available
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          currentUserLocation = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          };

          // Update user marker if exists
          if (userMarker && map) {
            userMarker.setLatLng([
              currentUserLocation.lat,
              currentUserLocation.lng,
            ]);
          }

          // Now retry navigation
          alert('✅ GPS location acquired! Click "START NAVIGATION" again.');
        },
        (error) => {
          console.error("GPS error:", error);
          alert(
            "❌ Unable to access GPS.\n\nPlease enable location services in your browser settings.",
          );
        },
      );
    }
    return;
  }

  // Fetch request details with shelter coordinates from database
  fetch(`index.php?route=get_request_details&request_id=${requestId}`)
    .then((response) => response.json())
    .then((data) => {
      console.log("Navigation data from database:", data); // Debug log

      if (data.success && data.request) {
        // Verify we have coordinates
        if (!data.request.latitude || !data.request.longitude) {
          alert(
            "❌ Shelter coordinates not available.\n\nThis shelter needs to update its location information.",
          );
          return;
        }

        enterNavigationMode(data.request);
      } else {
        alert(
          "Unable to load navigation data. Please try again.\n\nError: " +
            (data.message || "Unknown error"),
        );
      }
    })
    .catch((error) => {
      console.error("Navigation error:", error);
      alert(
        "❌ Navigation failed. Please check your connection.\n\nError: " +
          error.message,
      );
    });
}

function enterNavigationMode(request) {
  console.log("🧭 Entering navigation mode with data:", request);

  if (!currentUserLocation) {
    alert(
      "⚠️ GPS location not available.\n\nPlease enable location services and refresh the page.",
    );
    return;
  }

  console.log("📍 Current user location:", currentUserLocation);

  activeNavigationMode = true;

  // Hide normal UI elements
  document.querySelector(".shelter-feed")?.classList.add("hidden-during-nav");
  document.querySelector(".sidebar")?.classList.add("hidden-during-nav");

  // Center map between user and shelter
  const shelterLat = parseFloat(request.latitude);
  const shelterLng = parseFloat(request.longitude);

  console.log("🎯 Destination coordinates:", {
    lat: shelterLat,
    lng: shelterLng,
  });

  if (!shelterLat || !shelterLng || isNaN(shelterLat) || isNaN(shelterLng)) {
    alert(
      "❌ Invalid shelter coordinates.\n\nThis shelter needs to update its location.",
    );
    return;
  }

  const bounds = L.latLngBounds(
    [currentUserLocation.lat, currentUserLocation.lng],
    [shelterLat, shelterLng],
  );
  map.fitBounds(bounds, { padding: [50, 50] });

  // Draw route polyline
  if (routePolyline) {
    map.removeLayer(routePolyline);
  }

  console.log("🛣️ Drawing route from user to shelter...");

  routePolyline = L.polyline(
    [
      [currentUserLocation.lat, currentUserLocation.lng],
      [shelterLat, shelterLng],
    ],
    {
      color: "#3b82f6",
      weight: 4,
      opacity: 0.7,
      dashArray: "10, 10",
    },
  ).addTo(map);

  console.log("✅ Route polyline added to map");

  // Add shelter destination marker
  const destinationIcon = L.icon({
    iconUrl:
      "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png",
    shadowUrl:
      "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  });

  L.marker([shelterLat, shelterLng], { icon: destinationIcon })
    .addTo(map)
    .bindPopup(`<b>🎯 DESTINATION</b><br>${request.shelter_name}`)
    .openPopup();

  console.log("✅ Destination marker added");

  // Show navigation HUD
  displayNavigationHUD(request, shelterLat, shelterLng);

  // Start live GPS tracking
  startLiveTracking(shelterLat, shelterLng);

  console.log("🎉 Navigation mode activated successfully!");
}

function displayNavigationHUD(request, shelterLat, shelterLng) {
  // Create or update HUD
  let hud = document.getElementById("navigationHUD");

  if (!hud) {
    hud = document.createElement("div");
    hud.id = "navigationHUD";
    hud.className = "navigation-hud";
    document.querySelector(".portal-container").appendChild(hud);
  }

  const distance = calculateDistance(
    currentUserLocation.lat,
    currentUserLocation.lng,
    shelterLat,
    shelterLng,
  );

  const eta = calculateETA(distance);

  hud.innerHTML = `
        <div class="hud-header">
            <h3>
                <i data-lucide="navigation"></i>
                ACTIVE NAVIGATION
            </h3>
            <button class="btn-exit-nav" onclick="exitNavigationMode()">
                <i data-lucide="x"></i>
                EXIT
            </button>
        </div>
        
        <div class="hud-body">
            <div class="hud-destination">
                <div class="dest-icon">
                    <i data-lucide="home"></i>
                </div>
                <div class="dest-info">
                    <span class="dest-label">Destination</span>
                    <h4 class="dest-name">${request.shelter_name}</h4>
                </div>
            </div>
            
            <div class="hud-metrics">
                <div class="metric">
                    <div class="metric-icon">
                        <i data-lucide="route"></i>
                    </div>
                    <div class="metric-data">
                        <span class="metric-label">Distance</span>
                        <span class="metric-value" id="hudDistance">${distance.toFixed(2)} km</span>
                    </div>
                </div>
                
                <div class="metric">
                    <div class="metric-icon">
                        <i data-lucide="clock"></i>
                    </div>
                    <div class="metric-data">
                        <span class="metric-label">ETA</span>
                        <span class="metric-value" id="hudETA">${eta}</span>
                    </div>
                </div>
            </div>
            
            <div class="hud-actions">
                <a href="https://www.google.com/maps/dir/?api=1&origin=${currentUserLocation.lat},${currentUserLocation.lng}&destination=${shelterLat},${shelterLng}&travelmode=driving" 
                   target="_blank" 
                   class="btn-google-maps">
                    <i data-lucide="map"></i>
                    OPEN IN GOOGLE MAPS
                </a>
                
                ${
                  request.contact_number
                    ? `
                    <a href="tel:${request.contact_number}" class="btn-call-shelter-nav">
                        <i data-lucide="phone"></i>
                        CALL SHELTER
                    </a>
                `
                    : ""
                }
            </div>
        </div>
    `;

  // Re-initialize icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

function startLiveTracking(destLat, destLng) {
  // Update position every 5 seconds
  navigationUpdateInterval = setInterval(() => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          currentUserLocation = { lat, lng };

          // Update user marker
          if (userMarker) {
            userMarker.setLatLng([lat, lng]);
          }

          // Update route
          if (routePolyline) {
            routePolyline.setLatLngs([
              [lat, lng],
              [destLat, destLng],
            ]);
          }

          // Update HUD metrics
          const distance = calculateDistance(lat, lng, destLat, destLng);
          const eta = calculateETA(distance);

          const distEl = document.getElementById("hudDistance");
          const etaEl = document.getElementById("hudETA");

          if (distEl) distEl.textContent = `${distance.toFixed(2)} km`;
          if (etaEl) etaEl.textContent = eta;

          // Check if arrived (within 50 meters)
          if (distance < 0.05) {
            showArrivalNotification();
          }
        },
        (error) => {
          console.warn("GPS update failed:", error);
        },
      );
    }
  }, 5000);
}

function exitNavigationMode() {
  activeNavigationMode = false;

  // Clear interval
  if (navigationUpdateInterval) {
    clearInterval(navigationUpdateInterval);
    navigationUpdateInterval = null;
  }

  // Remove route
  if (routePolyline) {
    map.removeLayer(routePolyline);
    routePolyline = null;
  }

  // Remove HUD
  const hud = document.getElementById("navigationHUD");
  if (hud) {
    hud.remove();
  }

  // Restore normal UI
  document
    .querySelector(".shelter-feed")
    ?.classList.remove("hidden-during-nav");
  document.querySelector(".sidebar")?.classList.remove("hidden-during-nav");

  // Re-center map
  if (currentUserLocation) {
    map.setView([currentUserLocation.lat, currentUserLocation.lng], 14);
  }
}

function showArrivalNotification() {
  if (navigationUpdateInterval) {
    clearInterval(navigationUpdateInterval);
  }

  const notification = document.createElement("div");
  notification.className = "arrival-notification";
  notification.innerHTML = `
        <div class="arrival-content">
            <div class="arrival-icon">
                <i data-lucide="check-circle"></i>
            </div>
            <h3>🎉 You've Arrived!</h3>
            <p>Welcome to your safe shelter. Please check in with the host.</p>
            <button onclick="this.parentElement.parentElement.remove(); exitNavigationMode();" class="btn-arrival-ok">
                OK, GOT IT
            </button>
        </div>
    `;

  document.body.appendChild(notification);

  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

// =============================================
// UTILITY FUNCTIONS
// =============================================
function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // Earth's radius in km
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

function calculateETA(distanceKm) {
  const avgSpeedKmh = 30; // Average speed in city
  const hours = distanceKm / avgSpeedKmh;
  const minutes = Math.round(hours * 60);

  if (minutes < 1) return "Less than 1 min";
  if (minutes === 1) return "1 minute";
  if (minutes < 60) return `${minutes} minutes`;

  const hrs = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hrs}h ${mins}m`;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return "Just now";
  if (diffMins < 60) return `${diffMins} min ago`;
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)} hours ago`;

  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function updateLocationCard(lat, lng) {
  const locCard = document.querySelector(".location-card .metric-value");
  if (locCard) {
    locCard.textContent = "GPS Active";
  }
}

// =============================================
// PERIODIC UPDATES
// =============================================
function startPeriodicUpdates() {
  // Reload requests every 15 seconds
  setInterval(() => {
    if (!activeNavigationMode) {
      loadUserRequests();
    }
  }, 15000);

  // Check for approval notifications
  setInterval(() => {
    fetch("index.php?route=check_updates")
      .then((response) => response.json())
      .then((data) => {
        if (data.new_update) {
          showNotification(
            `✅ APPROVED: Your request for ${data.facility} has been approved!`,
          );
          loadUserRequests();
        }
      })
      .catch(() => {
        /* Silent fail */
      });
  }, 10000);
}

// =============================================
// SOS BUTTON
// =============================================
function setupSOSButton() {
  const sosBtn = document.getElementById("sosTrigger");
  if (sosBtn) {
    sosBtn.addEventListener("click", function () {
      const originalContent = sosBtn.innerHTML;
      sosBtn.innerHTML = "<span>SENDING...</span>";
      sosBtn.style.background = "var(--primary-red)";
      sosBtn.style.color = "white";

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            console.log("SOS Signal Dispatched at:", position.coords);
            alert(
              "🚨 EMERGENCY SIGNAL SENT!\nHelp is being routed to your coordinates.",
            );
            sosBtn.innerHTML = originalContent;
          },
          (error) => {
            alert("Unable to get location. Signal sent without coordinates.");
            sosBtn.innerHTML = originalContent;
          },
        );
      } else {
        alert("Geolocation is not supported by this browser.");
        sosBtn.innerHTML = originalContent;
      }
    });
  }
}

// =============================================
// REQUEST FORM SUBMISSION
// =============================================
function setupRequestForm() {
  const requestForm = document.getElementById("shelterRequestForm");
  if (requestForm) {
    requestForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const payload = {
        shelter_id: document.getElementById("modalShelterId").value,
        group_size: document.getElementById("group_size").value,
        notes: document.getElementById("request_note").value,
      };

      if (!payload.shelter_id) {
        alert(
          "System Error: Shelter ID missing. Please refresh and try again.",
        );
        return;
      }

      fetch("index.php?route=submit_request", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert(
              `✅ SUCCESS: Request for ${payload.group_size} people sent.\nWaiting for host approval.`,
            );
            closeRequestModal();
            setTimeout(() => location.reload(), 1000);
          } else {
            alert(
              "❌ REQUEST FAILED:\n\n" +
                (data.message || "Unknown error occurred"),
            );
          }
        })
        .catch((err) => {
          console.error("Transmission Error:", err);
          alert("❌ CONNECTION ERROR:\nCould not reach the server.");
        });
    });
  }
}

// =============================================
// GLOBAL MODAL FUNCTIONS
// =============================================
window.handleRequest = function (shelterId, shelterName) {
  // Check if user is an active host
  const hostWarning = document.querySelector(".host-status-warning");
  if (hostWarning && hostWarning.style.display !== "none") {
    alert(
      "⚠️ You cannot request shelter while you are an active host.\n\nPlease go to Host Portal and relinquish your host status first.",
    );
    return;
  }

  // Check if user is currently checked in (button is disabled, but guard for map popup clicks)
  const checkoutBtn = document.querySelector(".btn-checkout");
  if (checkoutBtn) {
    alert(
      "⚠️ You are currently checked in to a shelter.\n\nPlease check out before requesting another shelter.",
    );
    return;
  }

  // Check if user already has an active request
  if (window.userActiveRequests && window.userActiveRequests.length > 0) {
    showAlreadyRequestedWarning();
    return;
  }

  document.getElementById("modalShelterId").value = shelterId;
  document.getElementById("modalShelterName").value = shelterName;
  document.getElementById("displayShelterName").textContent = shelterName;

  const modal = document.getElementById("requestModal");
  if (modal) {
    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("active"), 10);
    document.body.style.overflow = "hidden";

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  }
};

function showAlreadyRequestedWarning() {
  const modal = document.createElement("div");
  modal.id = "alreadyRequestedModal";
  modal.className = "modal-overlay active";

  modal.innerHTML = `
        <div class="protocol-modal modal-warning">
            <div class="modal-header modal-header-warning">
                <h3 class="modal-title modal-title-warning">
                    <i data-lucide="alert-triangle" class="icon-warning"></i> 
                    Active Request Exists
                </h3>
                <button type="button" class="btn-close-icon" onclick="closeAlreadyRequestedModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="alert-box alert-box-warning">
                    <p class="alert-box-title">
                        ⚠️ You Already Have an Active Shelter Request
                    </p>
                    <p class="alert-box-desc">
                        You can only have one active shelter request at a time. Please cancel your current request if you want to request a different shelter.
                    </p>
                </div>

                <div class="choice-box">
                    <h4 class="choice-box-title">
                        <i data-lucide="info" class="icon-sm"></i>
                        What would you like to do?
                    </h4>
                    <div class="choice-grid">
                        <button onclick="closeAlreadyRequestedModal(); document.getElementById('viewRequestsBtn').click();" 
                                class="btn-choice btn-choice-amber">
                            <i data-lucide="eye" class="icon-sm"></i>
                            VIEW MY CURRENT REQUEST
                        </button>
                        <p class="choice-or">or</p>
                        <button onclick="closeAlreadyRequestedModal();" 
                                class="btn-choice btn-choice-neutral">
                            STAY HERE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
  document.body.style.overflow = "hidden";

  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

window.closeAlreadyRequestedModal = function () {
  const modal = document.getElementById("alreadyRequestedModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
};

window.closeRequestModal = function () {
  const modal = document.getElementById("requestModal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      modal.style.display = "none";
      document.body.style.overflow = "";
    }, 300);
  }
};

function showNotification(message) {
  const banner = document.createElement("div");
  banner.className = "toast-notification active";
  banner.innerHTML = `<strong>System Alert:</strong> ${message}`;
  document.body.appendChild(banner);

  setTimeout(() => {
    banner.classList.remove("active");
    setTimeout(() => banner.remove(), 500);
  }, 5000);
}

// =============================================
// VIEW REQUESTS BUTTON FUNCTIONALITY
// =============================================
function setupViewRequestsButton() {
  const viewBtn = document.getElementById("viewRequestsBtn");
  if (viewBtn) {
    viewBtn.addEventListener("click", function () {
      if (window.userActiveRequests && window.userActiveRequests.length > 0) {
        showRequestProgressModal();
      }
    });
  }
}

function updateViewRequestsButton(requests) {
  const viewBtn = document.getElementById("viewRequestsBtn");
  if (!viewBtn) return;

  if (requests.length === 0) {
    viewBtn.style.display = "none";
    return;
  }

  viewBtn.style.display = "flex";

  // Check if any request is approved
  const hasApproved = requests.some((r) => r.status === "approved");

  if (hasApproved) {
    viewBtn.innerHTML = `
            <i data-lucide="navigation"></i>
            <span>GUIDE ME</span>
        `;
    viewBtn.classList.remove("btn-view-requests-pending");
    viewBtn.classList.add("btn-view-requests-approved");
  } else {
    viewBtn.innerHTML = `
            <i data-lucide="clipboard-list"></i>
            <span>VIEW MY REQUESTS</span>
        `;
    viewBtn.classList.remove("btn-view-requests-approved");
    viewBtn.classList.add("btn-view-requests-pending");
  }

  // Re-initialize icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

function showRequestProgressModal() {
  const requests = window.userActiveRequests || [];
  if (requests.length === 0) {
    console.log("No active requests found in database");
    return;
  }

  // Show only the first (and should be only) request
  const request = requests[0];

  console.log("📊 Request data from database:", {
    id: request.id,
    shelter_name: request.shelter_name,
    location: request.location,
    latitude: request.latitude,
    longitude: request.longitude,
    group_size: request.group_size,
    status: request.status,
    created_at: request.created_at,
    contact_number: request.contact_number,
  });

  // Create modal
  const modal = document.createElement("div");
  modal.id = "requestProgressModal";
  modal.className = "modal-overlay active";

  const stage = getRequestStage(request.status);
  const canNavigate = request.status === "approved";

  modal.innerHTML = `
        <div class="request-progress-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i data-lucide="clipboard-list"></i>
                    My Shelter Request
                </h3>
                <button type="button" class="btn-close-icon" onclick="closeRequestProgressModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="request-progress-card ${request.status}">
                    <div class="request-header">
                        <h4>${request.shelter_name}</h4>
                        <span class="status-badge ${request.status}">${request.status.toUpperCase()}</span>
                    </div>
                    
                    <div class="db-indicator">
                        <p class="db-indicator-text">
                            <i data-lucide="database" class="icon-xs"></i>
                            <span>Data loaded from database (Request ID: ${request.id})</span>
                        </p>
                    </div>
                    
                    <div class="progress-timeline">
                        <div class="timeline-step ${stage >= 1 ? "completed" : ""}">
                            <div class="step-icon">
                                <i data-lucide="send"></i>
                            </div>
                            <div class="step-label">SUBMITTED</div>
                        </div>
                        
                        <div class="timeline-connector ${stage >= 2 ? "completed" : ""}"></div>
                        
                        <div class="timeline-step ${stage >= 2 ? "completed" : stage === 1 ? "active" : ""}">
                            <div class="step-icon">
                                <i data-lucide="eye"></i>
                            </div>
                            <div class="step-label">REVIEWING</div>
                        </div>
                        
                        <div class="timeline-connector ${stage >= 3 ? "completed" : ""}"></div>
                        
                        <div class="timeline-step ${stage >= 3 ? "completed" : ""}">
                            <div class="step-icon">
                                <i data-lucide="check-circle"></i>
                            </div>
                            <div class="step-label">APPROVED</div>
                        </div>
                    </div>
                    
                    ${
                      request.status === "approved" && request.approval_code
                        ? `
                    <div class="approval-qr-box">
                        <div class="approval-qr-title">
                            <i data-lucide="qr-code" class="icon-xs"></i>
                            Your Check-In QR Code
                        </div>
                        <div class="approval-qr-img-wrap">
                            <img id="modalQrImage" 
                                 src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(request.approval_code)}"
                                 alt="QR Code"
                                 class="approval-qr-img"
                            >
                        </div>
                        <div class="approval-code-val">${request.approval_code}</div>
                        <div class="approval-qr-hint">
                            Present this QR code or code at the shelter for check-in
                        </div>
                    </div>
                    `
                        : ""
                    }
                    
                    <div class="request-details">
                        <div class="detail-row">
                            <i data-lucide="map-pin"></i>
                            <span>${request.location || "Location not available"}</span>
                        </div>
                        <div class="detail-row">
                            <i data-lucide="users"></i>
                            <span>${request.group_size} ${request.group_size > 1 ? "people" : "person"}</span>
                        </div>
                        <div class="detail-row">
                            <i data-lucide="calendar"></i>
                            <span>${formatDate(request.created_at)}</span>
                        </div>
                        ${
                          request.notes
                            ? `
                        <div class="detail-row">
                            <i data-lucide="file-text"></i>
                            <span>${request.notes}</span>
                        </div>
                        `
                            : ""
                        }
                    </div>
                    
                    <div class="request-actions">
                        ${
                          canNavigate
                            ? `
                            <button class="btn-navigate-now" onclick="startNavigationFromModal(${request.id})">
                                <i data-lucide="navigation"></i>
                                START NAVIGATION
                            </button>
                        `
                            : ""
                        }
                        
                        <button class="btn-cancel-request" onclick="confirmCancelRequest(${request.id}, '${request.shelter_name.replace(/'/g, "\\'")}')">
                            <i data-lucide="x-circle"></i>
                            CANCEL THIS REQUEST
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
  document.body.style.overflow = "hidden";

  // Initialize Lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

window.closeRequestProgressModal = function () {
  const modal = document.getElementById("requestProgressModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
};

window.startNavigationFromModal = function (requestId) {
  closeRequestProgressModal();
  // Redirect to the dedicated Map Page with the request ID as a URL param.
  // The Map Page will handle all navigation: GPS detection, shelter marker,
  // route drawing, and live tracking.
  window.location.href = `index.php?route=maps&nav=1&request_id=${requestId}`;
};

// =============================================
// CANCEL REQUEST FUNCTIONALITY
// =============================================
window.confirmCancelRequest = function (requestId, shelterName) {
  const confirmModal = document.createElement("div");
  confirmModal.id = "confirmCancelModal";
  confirmModal.className = "modal-overlay active";

  confirmModal.innerHTML = `
        <div class="protocol-modal modal-danger">
            <div class="modal-header modal-header-danger">
                <h3 class="modal-title modal-title-danger">
                    <i data-lucide="alert-circle" class="icon-danger"></i> 
                    Cancel Request?
                </h3>
                <button type="button" class="btn-close-icon" onclick="closeCancelConfirmModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="alert-box alert-box-danger">
                    <p class="alert-box-text">
                        Are you sure you want to cancel your shelter request?
                    </p>
                </div>
                
                <div class="info-box">
                    <p class="info-box-row"><strong>Shelter:</strong> ${shelterName}</p>
                    <p class="info-box-note">
                        This action cannot be undone. You'll need to submit a new request if you change your mind.
                    </p>
                </div>
                
                <div class="modal-actions modal-actions-gap">
                    <button type="button" class="btn-cancel btn-flex" onclick="closeCancelConfirmModal()">
                        NO, KEEP IT
                    </button>
                    <button type="button" class="btn-confirm btn-flex btn-confirm-danger" onclick="executeCancelRequest(${requestId})">
                        <i data-lucide="trash-2"></i>
                        YES, CANCEL IT
                    </button>
                </div>
            </div>
        </div>
    `;

  document.body.appendChild(confirmModal);

  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
};

window.closeCancelConfirmModal = function () {
  const modal = document.getElementById("confirmCancelModal");
  if (modal) {
    modal.remove();
  }
};

window.executeCancelRequest = async function (requestId) {
  const confirmBtn = event.target.closest("button");
  const originalHTML = confirmBtn.innerHTML;

  confirmBtn.disabled = true;
  confirmBtn.innerHTML =
    '<i data-lucide="loader-2" class="spin"></i> CANCELLING...';
  if (typeof lucide !== "undefined") lucide.createIcons();

  try {
    const response = await fetch("index.php?route=cancel_request", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: requestId }),
    });

    const data = await response.json();

    if (data.success) {
      confirmBtn.innerHTML = '<i data-lucide="check"></i> CANCELLED!';
      if (typeof lucide !== "undefined") lucide.createIcons();

      setTimeout(() => {
        closeCancelConfirmModal();
        closeRequestProgressModal();

        // Reload requests
        loadUserRequests();

        showNotification(
          "✅ Request cancelled successfully. You can now request a different shelter.",
        );
      }, 800);
    } else {
      throw new Error(data.message || "Failed to cancel request");
    }
  } catch (error) {
    alert("❌ Error: " + error.message);
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = originalHTML;
    if (typeof lucide !== "undefined") lucide.createIcons();
  }
};

// =============================================
// EVACUEE CHECK-OUT FUNCTIONALITY
// =============================================
window.showCheckOutModal = function () {
  const modal = document.getElementById("checkOutModal");
  if (modal) {
    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("active"), 10);
    if (typeof lucide !== "undefined") lucide.createIcons();
  }
};

window.closeCheckOutModal = function () {
  const modal = document.getElementById("checkOutModal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      modal.style.display = "none";
    }, 300);
  }
};

window.executeCheckOut = async function (btn) {
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> CHECKING OUT...';
  if (typeof lucide !== "undefined") lucide.createIcons();

  try {
    const response = await fetch("index.php?route=evacuee_checkout", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    });

    const data = await response.json();

    if (data.success) {
      btn.innerHTML = '<i data-lucide="check"></i> CHECKED OUT!';
      if (typeof lucide !== "undefined") lucide.createIcons();
      setTimeout(() => {
        window.location.reload();
      }, 900);
    } else {
      throw new Error(data.message || "Check-out failed");
    }
  } catch (error) {
    alert("❌ Check-Out Error:\n\n" + error.message);
    btn.disabled = false;
    btn.innerHTML = originalHTML;
    if (typeof lucide !== "undefined") lucide.createIcons();
  }
};

// =============================================
// RESTORE HOST STATUS FUNCTIONS
// =============================================
window.confirmRestoreStatus = function () {
  const modal = document.getElementById("restoreHostModal");
  if (modal) {
    modal.style.display = "flex";
    setTimeout(() => {
      modal.classList.add("active");
    }, 10);

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  }
};

window.closeRestoreModal = function () {
  const modal = document.getElementById("restoreHostModal");
  if (modal) {
    modal.style.display = "none";
  }
};

window.executeRestore = async function (btn) {
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> RESTORING...';
  if (typeof lucide !== "undefined") lucide.createIcons();

  try {
    const response = await fetch("index.php?route=restore-host-status", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });

    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      throw new TypeError("Server didn't return JSON!");
    }

    const data = await response.json();

    if (data.success) {
      btn.innerHTML = '<i data-lucide="check"></i> SUCCESS!';
      if (typeof lucide !== "undefined") lucide.createIcons();

      setTimeout(() => {
        window.location.href = data.redirect || "index.php?route=host_portal";
      }, 800);
    } else {
      throw new Error(data.message || "Restoration failed");
    }
  } catch (error) {
    alert("❌ Restoration Error:\n\n" + error.message);
    btn.disabled = false;
    btn.innerHTML = originalHTML;
    if (typeof lucide !== "undefined") lucide.createIcons();
  }
};

// =============================================
// SHELTER SEARCH & FILTER
// =============================================

// Holds the current active filter state
const filterState = {
  status: "all", // "all" | "open" | "full"
  sort: "default", // "default" | "cap-asc" | "cap-desc" | "name-asc"
};

function setupSearchAndFilter() {
  const searchInput = document.getElementById("shelterSearch");
  const filterBtn = document.querySelector(".filter-btn");
  const shelterFeed = document.querySelector(".shelter-feed");

  if (!searchInput || !filterBtn || !shelterFeed) return;

  // ── Build filter dropdown ──────────────────────────────────
  const dropdown = document.createElement("div");
  dropdown.id = "filterDropdown";
  dropdown.className = "filter-dropdown";
  dropdown.innerHTML = `
    <div class="filter-dropdown-inner">
      <div class="filter-section">
        <p class="filter-section-label">Availability</p>
        <div class="filter-chip-group">
          <button class="filter-chip active" data-status="all">All Shelters</button>
          <button class="filter-chip"        data-status="open">Open Only</button>
          <button class="filter-chip"        data-status="full">Full</button>
        </div>
      </div>
      <div class="filter-section">
        <p class="filter-section-label">Sort By</p>
        <div class="filter-chip-group">
          <button class="filter-chip active" data-sort="default">Default</button>
          <button class="filter-chip"        data-sort="cap-asc">Capacity ↑</button>
          <button class="filter-chip"        data-sort="cap-desc">Capacity ↓</button>
          <button class="filter-chip"        data-sort="name-asc">Name A–Z</button>
        </div>
      </div>
      <div class="filter-footer">
        <button class="filter-reset-btn" id="filterResetBtn">Reset Filters</button>
        <span class="filter-results-count" id="filterResultsCount"></span>
      </div>
    </div>
  `;

  // Insert dropdown right after the search-bar section
  const searchBar = document.querySelector(".search-bar");
  searchBar.parentNode.insertBefore(dropdown, searchBar.nextSibling);

  // ── Stamp data attributes on each card for easy reading ───
  stampShelterCardData();

  // ── Filter button: toggle dropdown ────────────────────────
  filterBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.toggle("open");
    filterBtn.classList.toggle("filter-btn--active", isOpen);
    if (isOpen) {
      // Position dropdown below the search bar
      const rect = searchBar.getBoundingClientRect();
      dropdown.style.top = rect.bottom + window.scrollY + 8 + "px";
      dropdown.style.left = rect.left + "px";
      dropdown.style.width = rect.width + "px";
    }
  });

  // Close dropdown on outside click
  document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) && e.target !== filterBtn) {
      dropdown.classList.remove("open");
      filterBtn.classList.remove("filter-btn--active");
    }
  });

  // ── Status chip clicks ────────────────────────────────────
  dropdown.querySelectorAll("[data-status]").forEach((chip) => {
    chip.addEventListener("click", () => {
      dropdown
        .querySelectorAll("[data-status]")
        .forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      filterState.status = chip.dataset.status;
      applySearchAndFilter(searchInput.value);
      updateFilterBadge();
    });
  });

  // ── Sort chip clicks ──────────────────────────────────────
  dropdown.querySelectorAll("[data-sort]").forEach((chip) => {
    chip.addEventListener("click", () => {
      dropdown
        .querySelectorAll("[data-sort]")
        .forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      filterState.sort = chip.dataset.sort;
      applySearchAndFilter(searchInput.value);
    });
  });

  // ── Reset button ──────────────────────────────────────────
  document.getElementById("filterResetBtn").addEventListener("click", () => {
    filterState.status = "all";
    filterState.sort = "default";
    searchInput.value = "";
    dropdown
      .querySelectorAll("[data-status]")
      .forEach((c) => c.classList.toggle("active", c.dataset.status === "all"));
    dropdown
      .querySelectorAll("[data-sort]")
      .forEach((c) =>
        c.classList.toggle("active", c.dataset.sort === "default"),
      );
    applySearchAndFilter("");
    updateFilterBadge();
  });

  // ── Live search ───────────────────────────────────────────
  searchInput.addEventListener("input", () => {
    applySearchAndFilter(searchInput.value);
  });

  // ── Keyboard: close on Escape ─────────────────────────────
  searchInput.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      searchInput.value = "";
      applySearchAndFilter("");
    }
  });

  // Initial render count
  updateResultsCount(document.querySelectorAll(".shelter-row-card").length);
}

/**
 * Read name, location, status, and capacity off each shelter card
 * and store them as data-* attributes for fast matching.
 */
function stampShelterCardData() {
  document.querySelectorAll(".shelter-row-card").forEach((card) => {
    const name = card.querySelector(".shelter-name")?.textContent.trim() ?? "";
    const sub = card.querySelector(".shelter-sub")?.textContent.trim() ?? "";
    const badge = card.querySelector(".status-badge");
    const status = badge
      ? badge.classList.contains("open")
        ? "open"
        : "full"
      : "open";
    const capText = card.querySelector(".capacity-text")?.textContent ?? "0/0";
    const [cur, max] = capText
      .replace(/[^0-9/]/g, "")
      .split("/")
      .map(Number);

    card.dataset.name = name.toLowerCase();
    card.dataset.location = sub.toLowerCase();
    card.dataset.status = status;
    card.dataset.cur = cur || 0;
    card.dataset.max = max || 0;
  });

  // Initialise pagination after cards are stamped
  initPagination();
}

/**
 * Apply both the text search and the active filter state,
 * then re-sort, paginate, and show/hide cards accordingly.
 */
function applySearchAndFilter(rawQuery) {
  const query = rawQuery.trim().toLowerCase();
  const feed = document.querySelector(".shelter-feed");
  if (!feed) return;

  const cards = Array.from(feed.querySelectorAll(".shelter-row-card"));

  // ── Step 1: filter by text + status ──────────────────────
  const matched = cards.filter((card) => {
    const nameHit = card.dataset.name.includes(query);
    const locHit = card.dataset.location.includes(query);
    const textMatch = query === "" || nameHit || locHit;

    const statusMatch =
      filterState.status === "all" ||
      card.dataset.status === filterState.status;

    return textMatch && statusMatch;
  });

  // ── Step 2: sort matched cards ────────────────────────────
  if (filterState.sort !== "default") {
    matched.sort((a, b) => {
      switch (filterState.sort) {
        case "cap-asc":
          return Number(a.dataset.cur) - Number(b.dataset.cur);
        case "cap-desc":
          return Number(b.dataset.cur) - Number(a.dataset.cur);
        case "name-asc":
          return a.dataset.name.localeCompare(b.dataset.name);
        default:
          return 0;
      }
    });
    matched.forEach((card) => feed.appendChild(card));
  }

  // ── Step 3: hide ALL cards, then paginate the matched set ─
  cards.forEach((card) => (card.style.display = "none"));

  // Reset to page 1 whenever search/filter changes
  pagination.currentPage = 1;
  pagination.filteredCards = matched;
  renderPage();

  // ── Step 4: highlight matched text in name ────────────────
  matched.forEach((card) => {
    const nameEl = card.querySelector(".shelter-name");
    if (!nameEl) return;
    if (query) {
      const display = nameEl.textContent;
      const regex = new RegExp(`(${escapeRegex(query)})`, "gi");
      nameEl.innerHTML = display.replace(
        regex,
        '<mark class="search-highlight">$1</mark>',
      );
    } else {
      nameEl.innerHTML = nameEl.textContent;
    }
  });

  // ── Step 5: show/hide empty state ────────────────────────
  removeEmptyState();
  if (matched.length === 0) {
    showEmptyState(query, filterState.status);
  }

  updateResultsCount(matched.length);
}

// =============================================
// SHELTER PAGINATION MODULE
// =============================================
const SHELTERS_PER_PAGE = 10;

const pagination = {
  currentPage: 1,
  filteredCards: [],   // set by applySearchAndFilter
  allCards: [],        // all .shelter-row-card nodes (set on init)
};

/**
 * Show the correct slice of cards for the current page
 * and re-render the pagination controls.
 */
function renderPage() {
  const { currentPage, filteredCards } = pagination;
  const totalPages = Math.ceil(filteredCards.length / SHELTERS_PER_PAGE);
  const start = (currentPage - 1) * SHELTERS_PER_PAGE;
  const end = start + SHELTERS_PER_PAGE;

  // Hide all, then show only the current page slice
  pagination.allCards.forEach((c) => (c.style.display = "none"));
  filteredCards.slice(start, end).forEach((c) => (c.style.display = ""));

  renderPaginationControls(totalPages);
}

/**
 * Build/update the pagination control bar.
 * Only shown when total shelters > 10.
 */
function renderPaginationControls(totalPages) {
  const container = document.getElementById("shelterPagination");
  if (!container) return;

  // Hide pagination if not needed
  if (totalPages <= 1) {
    container.style.display = "none";
    container.innerHTML = "";
    return;
  }

  container.style.display = "flex";
  const { currentPage } = pagination;

  let html = "";

  // ← Prev button
  html += `<button class="pagination-btn prev-next" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>
    ← Prev
  </button>`;

  // Page number buttons with ellipsis
  const pageNums = getPaginationRange(currentPage, totalPages);
  pageNums.forEach((p) => {
    if (p === "...") {
      html += `<span class="pagination-dots">…</span>`;
    } else {
      html += `<button class="pagination-btn ${p === currentPage ? "active" : ""}" onclick="goToPage(${p})">${p}</button>`;
    }
  });

  // Next → button
  html += `<button class="pagination-btn prev-next" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""}>
    Next →
  </button>`;

  // Info text
  const start = (currentPage - 1) * SHELTERS_PER_PAGE + 1;
  const end = Math.min(currentPage * SHELTERS_PER_PAGE, pagination.filteredCards.length);
  html += `<span class="pagination-info">${start}–${end} of ${pagination.filteredCards.length}</span>`;

  container.innerHTML = html;
}

/**
 * Navigate to a specific page.
 */
function goToPage(page) {
  const totalPages = Math.ceil(pagination.filteredCards.length / SHELTERS_PER_PAGE);
  if (page < 1 || page > totalPages) return;
  pagination.currentPage = page;
  renderPage();

  // Scroll shelter feed into view smoothly
  const feed = document.querySelector(".shelter-feed");
  if (feed) feed.scrollIntoView({ behavior: "smooth", block: "start" });
}

/**
 * Generate an array of page numbers (with "..." for gaps).
 * Always shows first/last page, current page ± 1 neighbour.
 */
function getPaginationRange(current, total) {
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const range = new Set([1, total, current]);
  if (current > 1) range.add(current - 1);
  if (current < total) range.add(current + 1);

  const sorted = Array.from(range).sort((a, b) => a - b);
  const result = [];
  sorted.forEach((p, i) => {
    if (i > 0 && p - sorted[i - 1] > 1) result.push("...");
    result.push(p);
  });
  return result;
}

/**
 * Initialise pagination on page load.
 * Called after stampShelterCardData() so all cards are ready.
 */
function initPagination() {
  const feed = document.querySelector(".shelter-feed");
  if (!feed) return;

  const allCards = Array.from(feed.querySelectorAll(".shelter-row-card"));
  pagination.allCards = allCards;
  pagination.filteredCards = allCards;   // start with full list
  pagination.currentPage = 1;

  // Only activate if there are more than 10 shelters
  if (allCards.length > SHELTERS_PER_PAGE) {
    renderPage();
  }
}

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function showEmptyState(query, status) {
  const feed = document.querySelector(".shelter-feed");
  if (!feed || document.getElementById("shelterEmptyState")) return;

  const el = document.createElement("div");
  el.id = "shelterEmptyState";
  el.className = "shelter-empty-state";

  const statusLabel =
    { all: "any shelters", open: "open shelters", full: "full shelters" }[
      status
    ] ?? "shelters";
  const queryPart = query ? ` matching "<strong>${query}</strong>"` : "";

  el.innerHTML = `
    <div class="empty-state-icon">
      <i data-lucide="search-x"></i>
    </div>
    <h4 class="empty-state-title">No ${statusLabel} found${queryPart}</h4>
    <p class="empty-state-sub">Try a different search term or reset the filters.</p>
    <button class="empty-state-reset" onclick="document.getElementById('filterResetBtn').click()">
      Reset Filters
    </button>
  `;

  feed.appendChild(el);
  if (typeof lucide !== "undefined") lucide.createIcons();
}

function removeEmptyState() {
  const el = document.getElementById("shelterEmptyState");
  if (el) el.remove();
}

function updateResultsCount(count) {
  const el = document.getElementById("filterResultsCount");
  if (el) {
    el.textContent = count === 1 ? "1 shelter" : `${count} shelters`;
  }
}

/** Show a small badge dot on the filter button when non-default filters are active */
function updateFilterBadge() {
  const filterBtn = document.querySelector(".filter-btn");
  if (!filterBtn) return;
  const isActive =
    filterState.status !== "all" || filterState.sort !== "default";
  filterBtn.classList.toggle("filter-btn--has-active", isActive);
}
