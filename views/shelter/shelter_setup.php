<?php 
require_once 'config/auth_guard.php'; 
protect_page(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Shelter Setup</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/shelter_setup.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="light-portal shelter-setup-theme">
    <?php require 'views/partials/nav_portal.php'; ?>

    <main class="portal-container setup-container">
        <!-- HEADER -->
        <header class="portal-header setup-header">
            <div class="header-left">
                <h1 class="page-title">SHELTER SETUP</h1>
                <p class="user-id">Get your shelter ready to accept evacuees</p>
            </div>
            <div class="header-progress">
                <div class="progress-indicator">
                    <span class="progress-step active">1</span>
                    <span class="progress-step">2</span>
                    <span class="progress-step">3</span>
                </div>
                <p class="progress-text">Step <span class="current-step">1</span> of 3</p>
            </div>
        </header>

        <!-- SETUP WIZARD -->
        <div class="setup-wizard-container">
            
            <!-- STEP 1: BASIC INFORMATION -->
            <section class="setup-step active" id="step-1">
                <div class="step-card">
                    <div class="step-header">
                        <h2 class="step-title">
                            <i data-lucide="building-2" class="step-icon"></i>
                            Basic Shelter Information
                        </h2>
                        <p class="step-description">Tell us about your shelter location and basic details</p>
                    </div>

                    <form class="setup-form" id="form-step-1">
                        <!-- Shelter Name -->
                        <div class="form-group">
                            <label for="shelter-name" class="form-label">
                                <i data-lucide="tag"></i> SHELTER NAME
                            </label>
                            <input 
                                type="text" 
                                id="shelter-name" 
                                class="form-input" 
                                placeholder="e.g. Bacolod Central Shelter" 
                                required
                            >
                            <p class="form-hint">This will be displayed to evacuees searching for shelter</p>
                        </div>

                        <!-- Location Section -->
                        <div class="form-group">
                            <label class="form-label">
                                <i data-lucide="map-pin"></i> LOCATION
                            </label>
                            <div class="form-row">
                                <div class="form-col">
                                    <label class="sub-label">City/Municipality</label>
                                    <input type="text" class="form-input" placeholder="Bacolod City" required>
                                </div>
                                <div class="form-col">
                                    <label class="sub-label">Province</label>
                                    <input type="text" class="form-input" placeholder="Negros Occidental" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col">
                                    <label class="sub-label">Barangay</label>
                                    <input type="text" class="form-input" placeholder="Mansilingan" required>
                                </div>
                                <div class="form-col">
                                    <label class="sub-label">Street Address</label>
                                    <input type="text" class="form-input" placeholder="123 Main Street" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i data-lucide="map"></i> PIN EXACT LOCATION
                            </label>
                            
                            <div id="setup-map" style="height: 350px; width: 100%; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 1rem; z-index: 1;"></div>
                            
                            <button type="button" onclick="useCurrentLocation()" style="
                                background: #eff6ff; border: 2px solid #3b82f6; color: #3b82f6; 
                                padding: 0.8rem; border-radius: 8px; font-weight: 700; cursor: pointer; 
                                display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; margin-bottom: 1rem;
                                transition: all 0.3s ease;">
                                <i data-lucide="crosshair"></i> GET MY CURRENT LOCATION
                            </button>

                            <div class="form-row">
                                <div class="form-col">
                                    <label class="sub-label">Latitude</label>
                                    <input type="text" id="latitude" name="latitude" class="form-input" readonly style="background: #f1f5f9; cursor: not-allowed;">
                                </div>
                                <div class="form-col">
                                    <label class="sub-label">Longitude</label>
                                    <input type="text" id="longitude" name="longitude" class="form-input" readonly style="background: #f1f5f9; cursor: not-allowed;">
                                </div>
                            </div>
                            <p class="form-hint">Click on the map or use the button to set coordinates automatically.</p>
                        </div>
                        <!-- Contact Information -->
                        <div class="form-group">
                            <label for="contact-phone" class="form-label">
                                <i data-lucide="phone"></i> CONTACT PHONE
                            </label>
                            <input 
                                type="tel" 
                                id="contact-phone" 
                                class="form-input" 
                                placeholder="+63 9XX XXX XXXX" 
                                required
                            >
                            <p class="form-hint">This will be visible to evacuees</p>
                        </div>

                        <!-- Contact Email -->
                        <div class="form-group">
                            <label for="contact-email" class="form-label">
                                <i data-lucide="mail"></i> EMAIL ADDRESS
                            </label>
                            <input 
                                type="email" 
                                id="contact-email" 
                                class="form-input" 
                                placeholder="shelter@example.com" 
                                required
                            >
                        </div>

                        <!-- Shelter Type -->
                        <div class="form-group">
                            <label class="form-label">
                                <i data-lucide="home"></i> SHELTER TYPE
                            </label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="shelter-type" value="school" checked>
                                    <span class="radio-label">School Building</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="shelter-type" value="gymnasium">
                                    <span class="radio-label">Gymnasium/Sports Complex</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="shelter-type" value="community">
                                    <span class="radio-label">Community Center</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="shelter-type" value="private">
                                    <span class="radio-label">Private Facility</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="shelter-type" value="other">
                                    <span class="radio-label">Other</span>
                                </label>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="form-actions">
                            <button type="button" class="btn-back" onclick="previousStep()" disabled>
                                <i data-lucide="arrow-left"></i> BACK
                            </button>
                            <button type="button" class="btn-next" onclick="validateAndNext(1)">
                                NEXT <i data-lucide="arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- STEP 2: CAPACITY & FACILITIES -->
            <section class="setup-step" id="step-2">
                <div class="step-card">
                    <div class="step-header">
                        <h2 class="step-title">
                            <i data-lucide="users" class="step-icon"></i>
                            Capacity & Facilities
                        </h2>
                        <p class="step-description">Define shelter capacity and available facilities</p>
                    </div>

                    <form class="setup-form" id="form-step-2">
                        <!-- Total Capacity -->
                        <div class="form-group">
                            <label for="max-capacity" class="form-label">
                                <i data-lucide="maximize-2"></i> MAXIMUM CAPACITY
                            </label>
                            <div class="capacity-input-wrapper">
                                <input 
                                    type="number" 
                                    id="max-capacity" 
                                    class="form-input capacity-input" 
                                    placeholder="0" 
                                    min="10"
                                    value="50"
                                    required
                                >
                                <span class="capacity-unit">Persons</span>
                            </div>
                            <p class="form-hint">Maximum number of evacuees your shelter can accommodate</p>
                        </div>

                        <!-- Special Needs Capacity -->
                        <div class="form-group">
                            <label for="special-needs-capacity" class="form-label">
                                <i data-lucide="accessibility"></i> SPECIAL NEEDS BEDS
                            </label>
                            <div class="capacity-input-wrapper">
                                <input 
                                    type="number" 
                                    id="special-needs-capacity" 
                                    class="form-input capacity-input" 
                                    placeholder="0" 
                                    min="0"
                                    value="5"
                                >
                                <span class="capacity-unit">Beds</span>
                            </div>
                            <p class="form-hint">For elderly, disabled, or persons with medical needs</p>
                        </div>

                        <!-- Available Amenities -->
                        <div class="form-group">
                            <label class="form-label">
                                <i data-lucide="check-square"></i> AVAILABLE AMENITIES
                            </label>
                            <div class="amenities-grid">
                                <label class="amenity-item">
                                    <input type="checkbox" checked>
                                    <span class="amenity-icon">🍔</span>
                                    <span class="amenity-text">Food & Drinking Water</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox" checked>
                                    <span class="amenity-icon">🏥</span>
                                    <span class="amenity-text">Medical Services</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox">
                                    <span class="amenity-icon">📡</span>
                                    <span class="amenity-text">WiFi Internet</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox">
                                    <span class="amenity-icon">🧺</span>
                                    <span class="amenity-text">Laundry Facilities</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox">
                                    <span class="amenity-icon">🚿</span>
                                    <span class="amenity-text">Shower/Bathing</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox" checked>
                                    <span class="amenity-icon">🛏️</span>
                                    <span class="amenity-text">Sleeping Mats/Beds</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox">
                                    <span class="amenity-icon">📞</span>
                                    <span class="amenity-text">Telephone Service</span>
                                </label>
                                <label class="amenity-item">
                                    <input type="checkbox">
                                    <span class="amenity-icon">🎓</span>
                                    <span class="amenity-text">Child Care Services</span>
                                </label>
                            </div>
                        </div>

                        <!-- Special Features -->
                        <div class="form-group">
                            <label for="special-features" class="form-label">
                                <i data-lucide="star"></i> SPECIAL FEATURES
                            </label>
                            <textarea 
                                id="special-features" 
                                class="form-input form-textarea" 
                                placeholder="e.g. Generator backup, playground for kids, counseling services..."
                                rows="4"
                            ></textarea>
                            <p class="form-hint">Any additional features or services that make your shelter unique</p>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="form-actions">
                            <button type="button" class="btn-back" onclick="previousStep()">
                                <i data-lucide="arrow-left"></i> BACK
                            </button>
                            <button type="button" class="btn-next" onclick="validateAndNext(2)">
                                NEXT <i data-lucide="arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- STEP 3: INITIAL SUPPLIES & REVIEW -->
           <section class="setup-step" id="step-3">
    <div class="step-card">
        <div class="step-header">
            <h2 class="step-title"><i data-lucide="package" class="step-icon"></i> Initial Supplies</h2>
            <p class="step-description">Set up initial stock levels</p>
        </div>

        <form class="setup-form" id="form-step-3">
            <div class="stock-setup-section">
                
                <div class="stock-item-setup">
                    <span class="stock-item-label">💧 Water Supply</span>
                    <div class="stock-input-wrapper">
                        <input type="number" name="water_qty" class="form-input" value="0" min="0" placeholder="Qty">
                        <select name="water_unit" class="form-input"><option>Gallons</option><option>Liters</option></select>
                    </div>
                </div>

                <div class="stock-item-setup">
                    <span class="stock-item-label">🍔 Food Packs</span>
                    <div class="stock-input-wrapper">
                        <input type="number" name="food_qty" class="form-input" value="0" min="0" placeholder="Qty">
                        <select name="food_unit" class="form-input"><option>Packs</option><option>Boxes</option></select>
                    </div>
                </div>

                <div class="stock-item-setup">
                    <span class="stock-item-label">🏥 Medical Kits</span>
                    <div class="stock-input-wrapper">
                        <input type="number" name="meds_qty" class="form-input" value="0" min="0" placeholder="Qty">
                        <select name="meds_unit" class="form-input"><option>Kits</option><option>Items</option></select>
                    </div>
                </div>
            </div>

            <div class="review-section">
                <h3 class="section-title">Review Information</h3>
                <div class="review-card">
                    <div class="review-item"><span class="review-label">Shelter Name</span><span class="review-value" id="review-name">-</span></div>
                    <div class="review-item"><span class="review-label">Capacity</span><span class="review-value" id="review-capacity">-</span></div>
                </div>
                <label class="checkbox-agreement">
                    <input type="checkbox" id="agree-terms" required> <span>I verify this information is accurate</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-back" onclick="previousStep()">BACK</button>
                <button type="button" class="btn-complete" onclick="completeShelterSetup()">COMPLETE SETUP</button>
            </div>
        </form>
    </div>
</section>
        </div>

        <!-- HELP SECTION -->
        <div class="help-section">
            <div class="help-card">
                <i data-lucide="help-circle" class="help-icon"></i>
                <div class="help-content">
                    <h4 class="help-title">Need Help?</h4>
                    <p class="help-text">For assistance with shelter setup, contact our support team</p>
                    <a href="#" class="help-link">Contact Support <i data-lucide="arrow-right"></i></a>
                </div>
            </div>
        </div>

    </main>

<?php require 'views/partials/footer.php'; ?>
    <script src="assets/js/shelter_setup.js"></script>
 
</body>
</html>