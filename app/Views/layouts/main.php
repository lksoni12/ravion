<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAVION Pharma - <?= esc($title ?? 'Admin') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/company_logo.png') ?>">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0B2545">
    <link rel="apple-touch-icon" href="/assets/icons/icon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f4f6; color: #1f2937; -webkit-font-smoothing: antialiased; }
        .app-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #0B2545; color: #fff; padding: 0; display:flex; flex-direction:column; flex-shrink: 0; transition: width 0.2s ease-in-out; }
        .sidebar-top { position: sticky; top: 0; z-index: 10; background: #0B2545; }
        .brand { padding: 8px 20px; border-bottom: 1px solid #1e293b; display:flex; align-items:center; justify-content:center; background: #ffffff; color: #0B2545; }
        .brand img { height: 80px; width: auto; object-fit: contain; }
        .profile-box { margin: 10px 14px; padding: 10px; border:1px solid #1e293b; background:#0b1220; border-radius: 12px; display:flex; align-items:center; gap:10px; }
        .profile-avatar { width:36px; height:36px; border-radius:999px; background:#f59e0b; color:#111827; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:12px; }
        .profile-role { display:inline-block; margin-top:5px; padding:2px 8px; border-radius:999px; border:1px solid #475569; color:#fbbf24; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .nav-scroll { overflow-y:auto; flex: 1; padding: 12px 10px; }
        .nav-link { position: relative; display:flex; align-items:center; gap:8px; padding: 9px 12px; text-decoration: none; color: #cbd5e1; border-radius: 8px; margin-bottom: 4px; font-size: 13px; font-weight: 550; letter-spacing: .01em; transition: background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease, font-weight 0.25s ease; }
        .nav-link i { width: 20px; font-size: 14px; text-align: center; color:#94a3b8; transition: color 0.25s ease; }
        .nav-link:not(.active):hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .nav-link:not(.active):hover i { color:#fff; }
        .nav-link.active { background: #ffffff !important; color: #0B2545 !important; font-weight: 700 !important; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25); }
        .nav-link.active i { color: #0B2545 !important; }
        .nav-link.active::before { content: ""; position: absolute; left: 0; top: 20%; width: 4px; height: 60%; background: #3b82f6; border-radius: 0 4px 4px 0; transition: all 0.2s ease; }
        .nav-link.active:hover { background: #ffffff !important; color: #0B2545 !important; }
        .nav-link.active:hover i { color: #0B2545 !important; }
        .nav-group { margin-bottom:6px; border:1px solid #1e293b; border-radius:8px; overflow:hidden; background:#0b1220; }
        .nav-group summary { list-style:none; cursor:pointer; padding: 9px 12px; color:#cbd5e1; font-size:13px; font-weight: 550; display:flex; align-items:center; gap:8px; transition: background 0.2s ease; }
        .nav-group summary:hover { background: rgba(255, 255, 255, 0.04); color: #fff; }
        .nav-group summary::-webkit-details-marker { display:none; }
        .nav-group-content { padding: 4px; }
        .main-wrap { flex:1; display:flex; flex-direction:column; min-width: 0; }
        .topbar { background: #fff; padding: 24px 24px; border-bottom: 1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; position: sticky; top:0; z-index: 9; }
        .top-title { font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.01em; }
        .top-meta { display:flex; gap:10px; align-items:center; }
        .rolechip { background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:11px; padding:6px 10px; border-radius:999px; font-weight:800; }
        .top-avatar { width:36px; height:36px; border-radius:999px; background:#0f172a; color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; border:2px solid #fff; box-shadow:0 2px 6px rgba(15,23,42,.2); }
        .profile-menu { position:relative; }
        .profile-btn { border:1px solid #e2e8f0; background:#fff; border-radius:10px; padding:4px; cursor:pointer; }
        .profile-dropdown { position:absolute; right:0; top:44px; min-width:220px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 14px 30px rgba(2,6,23,.14); display:none; z-index:40; }
        .profile-dropdown.show { display:block; }
        .profile-dropdown a { display:block; padding:10px 12px; color:#334155; text-decoration:none; font-size:12px; font-weight:700; border-top:1px solid #f1f5f9; }
        .profile-dropdown a:hover { background:#f8fafc; }
        .logout-link { color:#dc2626 !important; }
        .content { padding: 24px; }
        .content-wrap { max-width: 1120px; width: 100%; margin: 0 auto; }
        .cards { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .muted { color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; border:1px solid #e5e7eb; }
        th, td { border-bottom: 1px solid #eef2f7; text-align: left; padding: 10px; font-size: 14px; vertical-align: top; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 11px; color:#475569; }
        form { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 16px; }
        input, select, textarea { width: 100%; margin: 6px 0 12px; padding: 9px; border-radius: 8px; border: 1px solid #d1d5db; box-sizing: border-box; background:#fff; }
        button { background: #16a34a; color: white; border: none; border-radius: 8px; padding: 9px 13px; cursor: pointer; font-weight:700; }
        h2 { margin-top: 0; font-size: 24px; }
        .flash { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .success { background: #e8f5e9; color: #1b5e20; }
        .error { background: #ffebee; color: #b71c1c; }
        .page-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .page-head h2 { margin:0; }
        .subtle { color:#64748b; font-size:13px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .panel { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; margin-bottom:16px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .panel h3 { margin:0 0 10px 0; font-size:16px; }
        .actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .btn-secondary { background:#0f172a; color:#fff; padding:8px 12px; border-radius:8px; text-decoration:none; font-size:12px; font-weight:700; display:inline-block; }
        .btn-add { background:#0f172a; color:#fff; border:none; padding:10px 14px; border-radius:10px; font-size:12px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
        .btn-link { color:#0f172a; font-weight:700; font-size:12px; text-decoration:none; }
        .tag { display:inline-block; border:1px solid #cbd5e1; background:#f8fafc; color:#334155; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
        .muted-cell { color:#94a3b8; font-style:italic; }
        .table-tools { display:flex; justify-content:flex-end; margin-bottom:10px; }
        .table-search { width:min(320px, 100%); margin:0; background:#f8fafc; }
        .popup-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 60; }
        .popup-overlay.show { display:flex; }
        .popup-card { width: min(760px, 100%); max-height: 90vh; overflow: auto; background: #fff; border-radius: 14px; border:1px solid #e5e7eb; box-shadow: 0 30px 70px rgba(2,6,23,.35); padding: 16px; }
        .popup-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; border-bottom:1px solid #e5e7eb; padding-bottom:8px; }
        .popup-head h3 { margin:0; font-size:16px; }
        .popup-close { background:#e2e8f0; color:#334155; border:none; border-radius:8px; padding:6px 10px; font-size:12px; font-weight:700; cursor:pointer; }
        @media (max-width: 1020px) { 
            .app-shell { display: flex; } 
            .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 260px; z-index: 100; transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); } 
            .sidebar.show { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,0.5); }
            .sidebar-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.6); z-index: 90; display: none; backdrop-filter: blur(2px); }
            .sidebar-overlay.show { display: block; }
            .mobile-menu-btn { display: flex !important; align-items: center; justify-content: center; }
            .nav-scroll { max-height:none; } 
        }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 24px; color: #0f172a; cursor: pointer; padding: 4px 8px; margin-right: 12px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns:1fr; } .top-title { font-size:20px; } .content { padding:16px; } }
        input[type="checkbox"], input[type="radio"] { width: auto !important; margin: 0 !important; padding: 0 !important; }
    </style>
    <script>
        function bindTableSearch(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) {
                return;
            }

            const tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            input.addEventListener('input', function () {
                const query = input.value.trim().toLowerCase();
                const rows = Array.from(tbody.querySelectorAll('tr'));

                rows.forEach(function (row) {
                    const isPlaceholder = !!row.querySelector('td[colspan]');
                    if (isPlaceholder) {
                        row.style.display = query === '' ? '' : 'none';
                        return;
                    }

                    const text = row.textContent.toLowerCase();
                    row.style.display = query === '' || text.includes(query) ? '' : 'none';
                });
            });
        }
    </script>
</head>
<body>
    <?php
        $currentPath = strtolower(trim((string) service('uri')->getPath(), '/'));
        if (str_starts_with($currentPath, 'index.php/')) {
            $currentPath = substr($currentPath, 10);
        }
        $currentPath = trim($currentPath, '/');

        $isActive = static function (array $patterns) use ($currentPath): bool {
            foreach ($patterns as $pattern) {
                $pat = strtolower(trim($pattern, '/'));
                if ($currentPath === $pat) {
                    return true;
                }
                if ($pat !== 'phadmin' && str_starts_with($currentPath, $pat . '/')) {
                    return true;
                }
            }
            return false;
        };
        $linkClass = static function (bool $active): string {
            return 'nav-link' . ($active ? ' active' : '');
        };
        $sessionRoleRaw = (string) (session()->get('user_role') ?? 'mr');
        $roleContext = strtolower((string) ($role ?? $sessionRoleRaw));
        if ($roleContext === 'admin') {
            $roleContext = 'owner';
        }
        if (! in_array($roleContext, ['owner', 'manager', 'mr'], true)) {
            $roleContext = 'mr';
        }

        $userAreaContext = (string) ($userArea ?? session()->get('user_area') ?? '');
        $profileName = (string) (session()->get('user_name') ?? session()->get('name') ?? strtoupper($roleContext));
        $avatar = (string) (session()->get('avatar') ?? '');
        if ($avatar === '' || $avatar === 'RP') {
            $parts = preg_split('/\s+/', trim($profileName)) ?: [];
            $avatar = strtoupper(substr((string) ($parts[0] ?? 'R'), 0, 1));
        }
    ?>

    <div class="app-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="mobileSidebar">
            <div class="sidebar-top">
                <div class="brand">
                    <img src="<?= base_url('uploads/logo/company_logo.png') ?>" alt="RavionPharma Logo">
                </div>
                <div class="profile-box">
                    <div class="profile-avatar"><?= esc($avatar) ?></div>
                    <div>
                        <div style="font-weight:900;font-size:13px;color:#fff;"><?= esc($profileName) ?></div>
                        <span class="profile-role"><?= esc($roleContext === 'owner' ? 'SUPER ADMIN' : strtoupper($roleContext)) ?><?php if ($userAreaContext !== ''): ?> · <?= esc($userAreaContext) ?><?php endif; ?></span>
                    </div>
                </div>
            </div>

            <div class="nav-scroll">
                <a class="<?= esc($linkClass($isActive(['phadmin/dashboard', 'phadmin']))) ?>" href="/phadmin/dashboard"><i class="fa-solid fa-gauge"></i>Dashboard</a>

                <?php if ($roleContext === 'owner'): ?>
                    <a class="<?= esc($linkClass($isActive(['phadmin/users']))) ?>" href="/phadmin/users"><i class="fa-solid fa-user-gear"></i>User Management</a>
                    <a class="<?= esc($linkClass($isActive(['phadmin/territories']))) ?>" href="/phadmin/territories"><i class="fa-solid fa-earth-americas"></i>Territory Management</a>
                <?php endif; ?>

                <details class="nav-group" <?= $isActive(['phadmin/products', 'phadmin/doctors', 'phadmin/chemists']) ? 'open' : '' ?>>
                    <summary><i class="fa-solid fa-folder-tree"></i>Master Data</summary>
                    <div class="nav-group-content">
                        <a class="<?= esc($linkClass($isActive(['phadmin/products']))) ?>" href="/phadmin/products"><i class="fa-solid fa-boxes-packing"></i>Product Portfolio</a>
                        <a class="<?= esc($linkClass($isActive(['phadmin/doctors']))) ?>" href="/phadmin/doctors"><i class="fa-solid fa-user-doctor"></i>Doctors Directory</a>
                        <a class="<?= esc($linkClass($isActive(['phadmin/chemists']))) ?>" href="/phadmin/chemists"><i class="fa-solid fa-flask"></i>Chemists Directory</a>
                    </div>
                </details>

                <?php if ($roleContext === 'owner'): ?>
                    <a class="<?= esc($linkClass($isActive(['phadmin/cfa-stockists', 'phadmin/cfas', 'phadmin/stockists']))) ?>" href="/phadmin/cfa-stockists"><i class="fa-solid fa-warehouse"></i>CFA & Stockist</a>
                <?php endif; ?>

                <a class="<?= esc($linkClass($isActive(['phadmin/expenses']))) ?>" href="/phadmin/expenses"><i class="fa-solid fa-receipt"></i>Expense</a>
                <a class="<?= esc($linkClass($isActive(['phadmin/plans']))) ?>" href="/phadmin/plans"><i class="fa-regular fa-map"></i>Tour Plan Track</a>
                <?php if (in_array($roleContext, ['owner', 'manager'], true)): ?>
                    <a class="<?= esc($linkClass($isActive(['phadmin/sponsorships']))) ?>" href="/phadmin/sponsorships"><i class="fa-solid fa-handshake-simple"></i>DRs CRM</a>
                <?php endif; ?>
                <a class="<?= esc($linkClass($isActive(['phadmin/e-detailing']))) ?>" href="/phadmin/e-detailing"><i class="fa-solid fa-tablet-screen-button"></i>E-Detailing</a>
                <a class="<?= esc($linkClass($isActive(['phadmin/sales-entries']))) ?>" href="/phadmin/sales-entries"><i class="fa-solid fa-money-bill-trend-up"></i>Sales Entry Board</a>

                <?php if (in_array($roleContext, ['owner', 'manager'], true)): ?>
                    <a class="<?= esc($linkClass($isActive(['phadmin/slides']))) ?>" href="/phadmin/slides"><i class="fa-solid fa-sliders"></i>Presentation Slides</a>
                <?php endif; ?>

                <a class="<?= esc($linkClass($isActive(['phadmin/reports']))) ?>" href="/phadmin/reports"><i class="fa-solid fa-chart-bar"></i>Reporting</a>
                <a class="<?= esc($linkClass($isActive(['phadmin/birthdays']))) ?>" href="/phadmin/birthdays"><i class="fa-solid fa-cake-candles"></i>Birthday Reminders</a>
            </div>
        </aside>

        <div class="main-wrap">
            <div class="topbar">
                <div style="display:flex; align-items:center;">
                    <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="top-title"><?= esc($title ?? 'Dashboard') ?></div>
                </div>
                <div class="top-meta">
                   
                    <!-- <span class="rolechip"><?= esc($companyName ?? 'RAVION Pharma') ?></span> -->
                    <span class="rolechip"><?= esc($roleContext === 'owner' ? 'Super Admin' : strtoupper($roleContext)) ?></span>
                    <div class="profile-menu">
                        <button type="button" class="profile-btn" id="profileMenuBtn" aria-label="Open profile menu">
                            <div class="top-avatar"><?= esc($avatar) ?></div>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div style="padding:10px 12px;font-size:12px;font-weight:800;color:#0B2545;"><?= esc($profileName) ?></div>
                            <a href="/phadmin/dashboard">Dashboard</a>
                            <a href="/phadmin/logout" class="logout-link">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <main class="content">
            <div class="content-wrap">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?= $this->renderSection('content') ?>
            </div>
            </main>
        </div>
    </div>
    <script>
        (function () {
            const profileBtn = document.getElementById('profileMenuBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const openButtons = document.querySelectorAll('[data-popup-open]');
            const closeButtons = document.querySelectorAll('[data-popup-close]');

            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function () {
                    profileDropdown.classList.remove('show');
                });
            }

            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileSidebar = document.getElementById('mobileSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (mobileMenuBtn && mobileSidebar && sidebarOverlay) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileSidebar.classList.add('show');
                    sidebarOverlay.classList.add('show');
                });
                sidebarOverlay.addEventListener('click', function() {
                    mobileSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }

            function closePopup(popup) {
                popup.classList.remove('show');
                popup.setAttribute('aria-hidden', 'true');
            }

            function openPopup(popup) {
                popup.classList.add('show');
                popup.setAttribute('aria-hidden', 'false');
            }

            openButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.getAttribute('data-popup-open'));
                    if (target) {
                        openPopup(target);
                    }
                });
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.getAttribute('data-popup-close'));
                    if (target) {
                        closePopup(target);
                    }
                });
            });

            document.querySelectorAll('.popup-overlay').forEach(function (popup) {
                popup.addEventListener('click', function (event) {
                    if (event.target === popup) {
                        closePopup(popup);
                    }
                });
            });
        })();

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw-e-detailing.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(err => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
</body>
</html>
