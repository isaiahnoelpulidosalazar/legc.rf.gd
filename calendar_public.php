<?php
require_once 'db.php';

$username = $_GET['user'] ?? '';
$user = null;
$events = [];
$default_avatar = '';

if ($username) {
    $stmt = $pdo->prepare("SELECT id, display_name, avatar FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmtEvents = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY event_date ASC");
        $stmtEvents->execute([$user['id']]);
        $events = $stmtEvents->fetchAll();
        
        // Base64 Safe SVG Dynamic Avatar Generation
        $char = strtoupper(substr($user['display_name'], 0, 1));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%231877f2"/><text x="50" y="65" font-family="sans-serif" font-size="45" font-weight="bold" fill="white" text-anchor="middle">' . $char . '</text></svg>';
        $default_avatar = 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- ... [existing config stays unchanged] ... -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user ? $user['display_name'] : 'Guest'); ?>'s Calendar - LeGC</title>
    <script src="https://isaiahnoelpulidosalazar.github.io/js/ECStyleSheet.js"></script>
    <style>
        :root {
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --primary: #1877f2;
            --bg-body: #f1f5f9;
            --bg-card: rgba(255, 255, 255, 0.65);
            --text-main: #0f172a;
            --text-sub: #475569;
            --border-color: rgba(15, 23, 42, 0.08);
            --ec-bg: var(--bg-card);
            --ec-border: var(--border-color);
        }
        body {
            margin: 0;
            font-family: var(--font-family);
            background-color: var(--bg-body);
            color: var(--text-main);
            padding: 40px 20px;
        }

        /* LIGHT MODES */
        body.light {
            --bg-body: #f1f5f9;
            --bg-card: rgba(255, 255, 255, 0.65);
            --text-main: #0f172a;
            --text-sub: #475569;
            --border-color: rgba(15, 23, 42, 0.08);
            --ec-bg: var(--bg-card);
            --ec-border: var(--border-color);
        }
        body.light.blue { background: radial-gradient(at 0% 0%, #e0f2fe 0px, transparent 50%), radial-gradient(at 100% 100%, #e0f2fe 0px, transparent 50%), #f1f5f9 !important; }
        body.light.purple { background: radial-gradient(at 0% 0%, #f3e8ff 0px, transparent 50%), radial-gradient(at 100% 100%, #fae8ff 0px, transparent 50%), #f1f5f9 !important; }
        body.light.green { background: radial-gradient(at 0% 0%, #dcfce7 0px, transparent 50%), radial-gradient(at 100% 100%, #f0fdf4 0px, transparent 50%), #f1f5f9 !important; }
        body.light.multi-sunset { background: radial-gradient(at 0% 0%, #ffedd5 0px, transparent 50%), radial-gradient(at 100% 100%, #fce7f3 0px, transparent 50%), #f1f5f9 !important; }
        body.light.multi-ocean { background: radial-gradient(at 0% 0%, #ccfbf1 0px, transparent 50%), radial-gradient(at 100% 100%, #e0f2fe 0px, transparent 50%), #f1f5f9 !important; }

        /* DARK MODES WITH CORRECTED LIGHT TEXT COLORS */
        body.dark {
            --bg-body: #030712;
            --bg-card: rgba(17, 24, 39, 0.7);
            --text-main: #f9fafb;
            --text-sub: #9ca3af;
            --border-color: rgba(255, 255, 255, 0.08);
            --ec-bg: var(--bg-card);
            --ec-border: var(--border-color);
        }
        body.dark.blue { background: radial-gradient(at 0% 0%, #0c1524 0px, transparent 50%), radial-gradient(at 100% 100%, #07101e 0px, transparent 50%), #030712 !important; }
        body.dark.purple { background: radial-gradient(at 0% 0%, #1a0b2e 0px, transparent 50%), radial-gradient(at 100% 100%, #0d041e 0px, transparent 50%), #03010c !important; }
        body.dark.green { background: radial-gradient(at 0% 0%, #052e16 0px, transparent 30%), radial-gradient(at 100% 100%, #022c22 0px, transparent 35%), #020617 !important; }
        body.dark.multi-sunset { background: radial-gradient(at 0% 0%, #2e1015 0px, transparent 50%), radial-gradient(at 100% 100%, #1e1b4b 0px, transparent 50%), #030712 !important; }
        body.dark.multi-ocean { background: radial-gradient(at 0% 0%, #115e59 0px, transparent 40%), radial-gradient(at 100% 100%, #075985 0px, transparent 40%), #020617 !important; }

        /* DIRECT CLASS OVERRIDES TO ENFORCE CORRECT TEXT CONTRASTS */
        body, h1, h2, h3, h4, h5, h6, p, span, a, label, textarea, input, select {
            color: var(--text-main) !important;
        }
        .color-var\(--text-main\), [class*="color-var(--text-main)"] {
            color: var(--text-main) !important;
        }
        .color-var\(--text-sub\), .text-muted, [class*="color-var(--text-sub)"], label[class*="fontSize-13px"] {
            color: var(--text-sub) !important;
        }

        /* CORE FROSTED GLASS TRANSFORMATION */
        .eccard, #day-events-modal > div, #alert-modal > div, [class*="ECModal"] > div, [class*="ec-box"] {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            backdrop-filter: blur(12px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(180%) !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1) !important;
        }

        @keyframes modalBounce {
            0% { transform: scale(0.85); opacity: 0; }
            50% { transform: scale(1.03); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-modal-bounce {
            animation: modalBounce 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.2) forwards;
        }
        .width-200% { width: 200%; }
        .width-50% { width: 50%; }
    </style>
</head>
<body class="light blue">

    <div class="width-100% maxWidth-800px margin-0_auto">
        <?php if ($user): ?>
            <div class="eccard padding-20px marginBottom-20px display-flex alignItems-center gap-15px backgroundColor-var(--bg-card)">
                <img src="<?php echo $user['avatar'] ?: $default_avatar; ?>" class="width-60px height-60px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                <div class="display-flex flexDirection-column">
                   <h1 class="fontSize-22px fontWeight-bold color-var(--text-main) margin-0px"><?php echo htmlspecialchars($user['display_name']); ?>'s Calendar</h1>
                   <span class="color-var(--text-sub) fontSize-14px">Public calendar view shared on LeGC</span>
                </div>
            </div>

            <!-- Calendar Controllers -->
            <div class="display-flex justifyContent-space-between alignItems-center marginBottom-20px">
                <div id="cal-prev-container"></div>
                <h2 id="calendar-title" class="fontSize-20px fontWeight-bold color-var(--text-main) margin-0px"></h2>
                <div id="cal-next-container"></div>
            </div>

            <!-- Calendar Display Pane -->
            <div class="eccard padding-20px backgroundColor-var(--bg-card)">
                <div class="display-grid gridTemplateColumns-repeat(7,_1fr) gap-10px textAlign-center fontWeight-bold color-var(--text-sub) borderBottom-1px_solid_var(--border-color) paddingBottom-10px marginBottom-10px fontSize-13px">
                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                </div>
                <div id="calendar-grid" class="display-grid gridTemplateColumns-repeat(7,_1fr) gap-10px"></div>
            </div>
        <?php else: ?>
            <div class="eccard padding-30px textAlign-center backgroundColor-var(--bg-card)">
                <h1 class="fontSize-24px fontWeight-bold color-var(--text-main)">User Profile Not Found</h1>
                <p class="color-var(--text-sub) fontSize-14px">The requested public user calendar does not exist.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const loadedEvents = <?php echo json_encode($events); ?>;
        const hostDisplayName = <?php echo json_encode($user ? $user['display_name'] : ''); ?>;
        let calendarDate = new Date();

        let appAlertModal = null;
        let appDayEventsModal = null;
        let registeredComponents = [];

        window.addEventListener('DOMContentLoaded', () => {
            initPublicECElements();
            renderCalendar();
        });

        function registerPublicComponent(comp) {
            if (comp) {
                registeredComponents.push(comp);
                applyPublicThemeToComponent(comp);
            }
            return comp;
        }

        function applyPublicThemeToComponent(comp) {
            // Public calendars default to base blue light accents
            const isDark = document.body.classList.contains('dark');
            const targetTheme = new ECTheme({
                primary: "#1877f2",
                background: isDark ? "rgba(17, 24, 39, 0.7)" : "rgba(255, 255, 255, 0.65)",
                text: isDark ? "#f9fafb" : "#0f172a",
                textMuted: isDark ? "#9ca3af" : "#475569",
                border: isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(15, 23, 42, 0.08)"
            });
            comp.setTheme(targetTheme);
        }

        function initPublicECElements() {
            // 1. Notification Alert Modal
            appAlertModal = registerPublicComponent(new ECModal("Notification"));
            document.body.appendChild(appAlertModal.element);

            // 2. Sliding event modal
            appDayEventsModal = registerPublicComponent(new ECModal("Events"));
            document.body.appendChild(appDayEventsModal.element);

            const dayLogsLayout = document.createElement('div');
            dayLogsLayout.className = "overflow-hidden width-100% position-relative";
            dayLogsLayout.style.height = "280px";
            dayLogsLayout.innerHTML = `
                <div id="modal-slider" class="display-flex width-200% height-100%" style="transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: translateX(0);">
                    <div class="width-50% height-100% display-flex flexDirection-column boxSizing-border-box">
                        <div id="day-modal-list" class="flex-1 overflowY-auto display-flex flexDirection-column gap-10px"></div>
                    </div>
                    <div class="width-50% height-100% display-flex flexDirection-column boxSizing-border-box" style="padding-left: 15px;">
                        <div class="display-flex alignItems-center gap-10px marginBottom-15px">
                            <button id="slider-back-btn" class="backgroundColor-transparent border-none fontSize-13px cursor-pointer color-var(--primary) fontWeight-bold hover:opacity-0.8 transition-0.2s">◀ Back</button>
                        </div>
                        <div class="flex-1 overflowY-auto display-flex flexDirection-column gap-12px">
                            <div>
                                <div class="fontSize-10px fontWeight-bold color-var(--text-sub) textTransform-uppercase">Title</div>
                                <div id="detail-title" class="fontSize-14px fontWeight-bold color-var(--text-main) marginTop-3px"></div>
                            </div>
                            <div>
                                <div class="fontSize-10px fontWeight-bold color-var(--text-sub) textTransform-uppercase">Host</div>
                                <div id="detail-host" class="fontSize-12px color-var(--text-main) marginTop-3px"></div>
                            </div>
                            <div>
                                <div class="fontSize-10px fontWeight-bold color-var(--text-sub) textTransform-uppercase">Description</div>
                                <div id="detail-desc" class="fontSize-13px color-var(--text-sub) lineHeight-1.4" style="white-space: pre-wrap;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            appDayEventsModal.setContent(dayLogsLayout);
            appDayEventsModal._closeBtn.addEventListener('click', () => closeDayEventsModal());
            appDayEventsModal._footer.style.display = 'none';

            dayLogsLayout.querySelector('#slider-back-btn').addEventListener('click', () => slideBackToEventsList());

            // 3. Navigation Controls
            const prevContainer = document.getElementById('cal-prev-container');
            if (prevContainer) {
                const btn = registerPublicComponent(new ECButton("◀ Prev", { variant: "white" }));
                btn.onClick(prevMonth);
                prevContainer.appendChild(btn.element);
            }

            const nextContainer = document.getElementById('cal-next-container');
            if (nextContainer) {
                const btn = registerPublicComponent(new ECButton("Next ▶", { variant: "white" }));
                btn.onClick(nextMonth);
                nextContainer.appendChild(btn.element);
            }
        }

        function showAlert(title, message) {
            document.getElementById('alert-modal-title').innerText = title;
            document.getElementById('alert-modal-message').innerText = message;
            document.getElementById('alert-modal').style.display = 'flex';
            document.getElementById('alert-modal-ok').onclick = () => {
                document.getElementById('alert-modal').style.display = 'none';
            };
        }

        // PUBLIC SLIDING CONTROLLER
        function openDayEventsModal(dateStr, day) {
            const dateObj = new Date(dateStr + "T00:00:00");
            const formattedDate = dateObj.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' });
            appDayEventsModal.setTitle(`Events on ${formattedDate}`);
            
            const listContainer = document.getElementById('day-modal-list');
            listContainer.innerHTML = '';
            
            const dayEvents = loadedEvents.filter(e => e.event_date === dateStr);
            
            if (dayEvents.length === 0) {
                listContainer.innerHTML = `
                    <div class="display-flex flexDirection-column alignItems-center justifyContent-center flex-1 color-var(--text-sub) gap-8px padding-20px">
                        <span class="fontSize-13px">No scheduled events today</span>
                    </div>`;
            } else {
                dayEvents.forEach(e => {
                    const item = document.createElement('div');
                    item.className = 'padding-12px borderRadius-8px border-1px_solid_var(--border-color) hover:backgroundColor-var(--primary-light) cursor-pointer transition-0.2s ecbounce-2';
                    item.onclick = (event) => {
                        event.stopPropagation();
                        showEventDetailSlide(e.title, e.description || "No description provided.", hostDisplayName);
                    };
                    item.innerHTML = `
                        <div class="fontWeight-bold fontSize-14px color-var(--text-main) textOverflow-ellipsis overflow-hidden whiteSpace-nowrap">${escapeHtml(e.title)}</div>
                        <div class="fontSize-11px color-var(--text-sub) marginTop-3px">by ${escapeHtml(hostDisplayName)}</div>
                    `;
                    listContainer.appendChild(item);
                });
            }
            
            document.getElementById('modal-slider').style.transform = 'translateX(0)';
            appDayEventsModal.open();
            if (window.ECStyleSheet) window.ECStyleSheet.scan();
        }

        function closeDayEventsModal() {
            appDayEventsModal.close();
        }

        function showEventDetailSlide(title, description, host) {
            document.getElementById('detail-title').innerText = title;
            document.getElementById('detail-desc').innerText = description;
            document.getElementById('detail-host').innerText = host;
            document.getElementById('modal-slider').style.transform = 'translateX(-50%)';
        }

        function slideBackToEventsList() {
            document.getElementById('modal-slider').style.transform = 'translateX(0)';
        }

        function renderCalendar() {
            const grid = document.getElementById('calendar-grid');
            const title = document.getElementById('calendar-title');
            if (!grid) return;
            grid.innerHTML = '';

            const year = calendarDate.getFullYear();
            const month = calendarDate.getMonth();

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            title.innerText = `${monthNames[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const prevDaysInMonth = new Date(year, month, 0).getDate();

            for (let i = firstDay - 1; i >= 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'eccard minHeight-90px padding-10px opacity-0.4 backgroundColor-var(--bg-card)';
                cell.innerHTML = `<span class="fontSize-12px color-var(--text-sub)">${prevDaysInMonth - i}</span>`;
                grid.appendChild(cell);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isToday = (new Date().getDate() === day && new Date().getMonth() === month && new Date().getFullYear() === year);
                const highlight = isToday ? 'border-2px_solid_var(--primary)' : 'border-1px_solid_var(--border-color)';

                const cell = document.createElement('div');
                cell.className = `eccard minHeight-90px padding-8px display-flex flexDirection-column justifyContent-space-between backgroundColor-var(--bg-card) cursor-pointer ecbounce-2 ${highlight}`;
                cell.onclick = () => openDayEventsModal(dateStr, day);
                
                let dayEvents = loadedEvents.filter(e => e.event_date === dateStr);
                let eventsHtml = '';
                dayEvents.forEach(e => {
                    eventsHtml += `
                        <div class="fontSize-10px backgroundColor-var(--primary) color-white padding-2px_4px borderRadius-4px textOverflow-ellipsis whiteSpace-nowrap overflow-hidden" title="${escapeHtml(e.title)}">
                            ${escapeHtml(e.title)}
                        </div>`;
                });

                cell.innerHTML = `
                    <span class="fontSize-13px fontWeight-bold color-var(--text-main)">${day}</span>
                    <div class="flex-1 overflowY-auto display-flex flexDirection-column gap-3px marginTop-5px" style="max-height: 55px;">
                        ${eventsHtml}
                    </div>
                `;
                grid.appendChild(cell);
            }
            if (window.ECStyleSheet) window.ECStyleSheet.scan();
        }

        function prevMonth() { calendarDate.setMonth(calendarDate.getMonth() - 1); renderCalendar(); }
        function nextMonth() { calendarDate.setMonth(calendarDate.getMonth() + 1); renderCalendar(); }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>