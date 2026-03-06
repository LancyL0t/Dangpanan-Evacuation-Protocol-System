/**
 * Navigation Active State Handler
 * Highlights the correct nav link based on current page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get current route from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentRoute = urlParams.get('route') || 'home';
    
    // Map routes to nav links
    const routeMap = {
        'evacuee_portal': 'evacuee',
        'host_portal': 'host',
        'maps': 'maps',
        'alerts': 'alerts',
        'admin_dashboard': 'admin'
    };
    
    // Get the matching route key
    const activeKey = routeMap[currentRoute];
    
    // Find and activate the correct nav link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const linkText = link.querySelector('span')?.textContent.toLowerCase();
        
        // Remove any existing active class
        link.classList.remove('active');
        
        // Add active class to matching link
        if (linkText === activeKey || 
            (activeKey === 'host' && linkText === 'host') ||
            (activeKey === 'evacuee' && linkText === 'evacuee') ||
            (activeKey === 'maps' && linkText === 'maps') ||
            (activeKey === 'alerts' && linkText === 'alerts') ||
            (activeKey === 'admin' && linkText.includes('admin'))) {
            link.classList.add('active');
        }
    });
    
    // Mobile drawer functionality
    const drawerTrigger = document.getElementById('drawerTrigger');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const drawerClose = document.getElementById('drawerClose');
    
    function openDrawer() {
        drawerOverlay.classList.add('active');
        mobileDrawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDrawer() {
        drawerOverlay.classList.remove('active');
        mobileDrawer.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (drawerTrigger) {
        drawerTrigger.addEventListener('click', openDrawer);
    }
    
    if (drawerClose) {
        drawerClose.addEventListener('click', closeDrawer);
    }
    
    if (drawerOverlay) {
        drawerOverlay.addEventListener('click', closeDrawer);
    }
    
    // Close drawer on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileDrawer.classList.contains('active')) {
            closeDrawer();
        }
    });
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});


/**
 * Dangpanan Navigation & Sidebar Logic
 * Extracted from portal.js
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Initialize Lucide icons (needed for nav icons)
    if (typeof lucide !== "undefined") {
        lucide.createIcons();
    }

    // --- DRAWER MENU LOGIC ---
    const drawerTrigger = document.getElementById("drawerTrigger");
    const drawerClose = document.getElementById("drawerClose");
    const drawerOverlay = document.getElementById("drawerOverlay");
    const mobileDrawer = document.getElementById("mobileDrawer");

    function openDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.add("active");
            drawerOverlay.classList.add("active");
            document.body.style.overflow = "hidden";
        }
    }

    function closeDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.remove("active");
            drawerOverlay.classList.remove("active");
            document.body.style.overflow = "";
        }
    }

    if (drawerTrigger) drawerTrigger.addEventListener("click", openDrawer);
    if (drawerClose) drawerClose.addEventListener("click", closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener("click", closeDrawer);

    // --- ACTIVE STATE FOR NAV LINKS ---
    // Highlights the current page in the sidebar/nav based on the 'route' URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const currentRoute = urlParams.get("route");
    const allNavLinks = document.querySelectorAll(".nav-link, .drawer-nav a");

    allNavLinks.forEach((link) => {
        if (currentRoute && link.getAttribute("href").includes(currentRoute)) {
            if (link.classList.contains("nav-link")) {
                link.classList.add("active");
            } else {
                // Inline styles for mobile drawer active state
                link.style.color = "var(--primary-red)";
                link.style.background = "var(--bg-tertiary)";
                link.style.fontWeight = "700";
                const icon = link.querySelector("i, svg");
                if (icon) icon.style.color = "var(--primary-red)";
            }
        }
    });
});