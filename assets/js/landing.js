/**
 * Dangpanan/assets/css/landing.js
 * Handles authentication checks, modal interactions, and User Profile Dropdown.
 */

/* --- AUTHENTICATION & MODAL LOGIC --- */

/**
 * Checks if a user is logged in before redirecting to a portal.
 * If not logged in, displays the authentication modal.
 */
function checkAuth(targetRoute, isLoggedIn) {
  if (isLoggedIn) {
    window.location.href = "index.php?route=" + targetRoute;
  } else {
    const modal = document.getElementById("authModal");
    if (modal) {
      modal.style.display = "flex";
    }
  }
}

/**
 * Closes the authentication modal.
 */
function closeAuthModal() {
  const modal = document.getElementById("authModal");
  if (modal) {
    modal.style.display = "none";
  }
}

// Global listener to close the authentication modal if clicking outside it
window.addEventListener("click", function (event) {
  const modal = document.getElementById("authModal");
  if (event.target == modal) {
    closeAuthModal();
  }
});

/* --- USER PROFILE DROPDOWN LOGIC --- */

/**
 * Toggles the visibility of the User Profile Dropdown menu.
 */
function toggleUserDropdown() {
  const dropdown = document.getElementById("userDropdown");
  if (dropdown) {
    dropdown.classList.toggle("active");
  }
}

/**
 * NEW: Confirms with the user before logging out.
 * Prevents accidental logouts on mobile devices.
 */
function confirmLogout(event) {
  // Prevent the immediate redirection from the link
  event.preventDefault();

  const confirmed = confirm("Are you sure you want to sign out?");
  if (confirmed) {
    // If user clicks OK, proceed to logout route
    window.location.href = "index.php?route=logout";
  }
}

/**
 * UX Helper: Closes the dropdown if the user clicks anywhere outside
 * the user profile pill or the dropdown itself.
 */
document.addEventListener("click", function (event) {
  const wrapper = document.querySelector(".user-profile-wrapper");
  const dropdown = document.getElementById("userDropdown");

  if (wrapper && !wrapper.contains(event.target)) {
    if (dropdown) {
      dropdown.classList.remove("active");
    }
  }
});

// Initialize Lucide icons on page load
if (typeof lucide !== "undefined") {
  lucide.createIcons();
}
