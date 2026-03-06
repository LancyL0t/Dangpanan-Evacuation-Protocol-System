    // ── Filter buttons ──
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('.alert-card').forEach(card => {
                card.style.display = (filter === 'all' || card.dataset.type === filter) ? '' : 'none';
            });
        });
    });

    // ── Expand/collapse alert body ──
    function toggleAlert(i) {
        const body = document.getElementById('alert-body-' + i);
        if (!body) return;
        body.classList.toggle('open');
        const btn = body.closest('.alert-card').querySelector('.btn-request-action');
        if (btn) btn.textContent = body.classList.contains('open') ? 'CLOSE' : 'READ MORE';
        lucide.createIcons();
    }

    // ── Auto-refresh every 30s ──
    let countdown = 30;
    const cdEl = document.getElementById('refreshCountdown');
    const refreshInterval = setInterval(() => {
        countdown--;
        if (cdEl) cdEl.textContent = countdown;
        if (countdown <= 0) {
            countdown = 30;
            refreshAlerts();
        }
    }, 1000);

    function refreshAlerts() {
        fetch('index.php?route=get-active-alerts')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                // Update counts
                document.getElementById('count-critical').textContent = data.counts.critical;
                document.getElementById('count-warning').textContent  = data.counts.warning;
                document.getElementById('count-info').textContent     = data.counts.info;
                document.getElementById('lastUpdated').textContent    = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
            })
            .catch(() => {});
    }

    // ── Safety tips rotator ──
    const tips = [
        {label:'🌊 Flood Safety', text:'Never walk or drive through floodwaters. Even 6 inches of fast-moving water can knock you down.'},
        {label:'🌀 Typhoon Prep', text:'Secure loose objects outside your home. Move to higher ground if you are in a flood-prone area.'},
        {label:'💧 Water Safety', text:'Store at least 3 gallons of water per person for 3 days. Use sealed containers.'},
        {label:'📦 Emergency Bag', text:'Pack a go-bag with: flashlight, batteries, first aid, medications, important documents, and 3 days of food.'},
        {label:'📱 Stay Informed', text:'Keep your phone charged. Follow official PAGASA advisories and Bacolod City CDRRMO announcements.'},
        {label:'🏠 If Evacuating', text:'Lock your home, bring your go-bag, and proceed to the nearest open evacuation center. Do NOT wait too long.'},
        {label:'⚡ Power Outage', text:'Unplug appliances before leaving. Do not use candles near flammable materials. Use battery-powered lights.'},
    ];
    let tipIndex = 0;
    function nextTip() {
        tipIndex = (tipIndex + 1) % tips.length;
        document.querySelector('.tip-label').textContent = tips[tipIndex].label;
        document.getElementById('safetyTipText').textContent = tips[tipIndex].text;
    }
    // Auto-rotate tips every 8s
    setInterval(nextTip, 8000);

    lucide.createIcons();
