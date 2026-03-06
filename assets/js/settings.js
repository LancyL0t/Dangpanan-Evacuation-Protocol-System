        lucide.createIcons();

        // ── Tab switching ─────────────────────────────────────────
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });

        // ── Password visibility toggle ────────────────────────────
        function togglePw(id, btn) {
            const input = document.getElementById(id);
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.innerHTML = isText
                ? '<i data-lucide="eye"></i>'
                : '<i data-lucide="eye-off"></i>';
            lucide.createIcons();
        }

        // ── Password strength checker ─────────────────────────────
        function checkStrength(value) {
            const fill  = document.getElementById('strengthFill');
            const label = document.getElementById('strengthLabel');
            let score = 0;
            if (value.length >= 8)             score++;
            if (/[A-Z]/.test(value))           score++;
            if (/[0-9]/.test(value))           score++;
            if (/[^A-Za-z0-9]/.test(value))   score++;

            const levels = [
                { w: '0%',   color: '#e2e8f0', text: 'Enter a new password',  css: '' },
                { w: '25%',  color: '#ef4444', text: 'Weak',                  css: '#ef4444' },
                { w: '50%',  color: '#f59e0b', text: 'Fair',                  css: '#b45309' },
                { w: '75%',  color: '#3b82f6', text: 'Good',                  css: '#1d4ed8' },
                { w: '100%', color: '#22c55e', text: 'Strong ✓',              css: '#15803d' },
            ];
            const lvl = value.length === 0 ? levels[0] : levels[score];
            fill.style.width      = lvl.w;
            fill.style.background = lvl.color;
            label.textContent     = lvl.text;
            label.style.color     = lvl.css || '#64748b';
            checkMatch();
        }

        // ── Password match indicator ──────────────────────────────
        function checkMatch() {
            const np  = document.getElementById('new_password').value;
            const cp  = document.getElementById('confirm_password').value;
            const lbl = document.getElementById('matchLabel');
            if (!cp) { lbl.textContent = '\u00a0'; lbl.style.color = ''; return; }
            if (np === cp) {
                lbl.textContent = '✓ Passwords match';
                lbl.style.color = '#16a34a';
            } else {
                lbl.textContent = '✗ Passwords do not match';
                lbl.style.color = '#dc2626';
            }
        }

        function resetPasswordForm() {
            document.getElementById('strengthFill').style.width = '0';
            document.getElementById('strengthLabel').textContent = 'Enter a new password';
            document.getElementById('matchLabel').textContent = '\u00a0';
        }

        // ── Client-side form validation before submit ─────────────
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const fn = this.querySelector('#first_name');
            const em = this.querySelector('#email');
            let ok = true;

            if (!fn.value.trim()) {
                fn.classList.add('invalid'); ok = false;
            } else { fn.classList.remove('invalid'); }

            if (!em.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value)) {
                em.classList.add('invalid'); ok = false;
            } else { em.classList.remove('invalid'); }

            if (!ok) { e.preventDefault(); }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const cp = this.querySelector('#current_password');
            const np = this.querySelector('#new_password');
            const cn = this.querySelector('#confirm_password');
            let ok = true;

            [cp, np, cn].forEach(f => {
                if (!f.value.trim()) { f.classList.add('invalid'); ok = false; }
                else { f.classList.remove('invalid'); }
            });

            if (np.value && cn.value && np.value !== cn.value) {
                cn.classList.add('invalid'); ok = false;
            }
            if (np.value && np.value.length < 8) {
                np.classList.add('invalid'); ok = false;
            }

            if (!ok) { e.preventDefault(); }
        });
