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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user ? $user['display_name'] : 'Guest'); ?>'s Calendar - LeGC</title>
    <script src="https://isaiahnoelpulidosalazar.github.io/js/ECStyleSheet.js"></script>
    <style>
        :root {
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --primary: #1877f2;
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-main: #050505;
            --text-sub: #65676b;
            --border-color: #ced0d4;
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
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-main: #050505;
            --text-sub: #65676b;
            --border-color: #ced0d4;
            --ec-bg: var(--bg-card);
            --ec-border: var(--border-color);
        }
        body.light.blue {
            --primary: #1877f2;
            --primary-hover: #166fe5;
            --primary-light: #e7f3ff;
        }
        body.light.purple {
            --primary: #8a2be2;
            --primary-hover: #7b1fa2;
            --primary-light: #f3e5f5;
        }
        body.light.green {
            --primary: #2ecc71;
            --primary-hover: #27ae60;
            --primary-light: #e8f8f5;
        }
        body.light.multi-sunset {
            --primary: #ff4757;
            --primary-hover: #ff6b81;
            --primary-light: #ffe0e6;
            background: linear-gradient(135deg, #f0f2f5 0%, #ffe3e8 100%) !important;
        }
        body.light.multi-ocean {
            --primary: #00bcd4;
            --primary-hover: #00acc1;
            --primary-light: #e0f7fa;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0faff 100%) !important;
        }

        /* DARK MODES */
        body.dark {
            --bg-body: #18191a;
            --bg-card: #242526;
            --text-main: #e4e6eb;
            --text-sub: #b0b3b8;
            --border-color: #3e4042;
            --ec-bg: var(--bg-card);
            --ec-border: var(--border-color);
        }
        body.dark.blue {
            --primary: #2d88ff;
            --primary-hover: #3578e5;
            --primary-light: #263951;
        }
        body.dark.purple {
            --primary: #a040ff;
            --primary-hover: #8f2be2;
            --primary-light: #3e2751;
        }
        body.dark.green {
            --primary: #2ecc71;
            --primary-hover: #2ecc71;
            --primary-light: #1b3d2b;
        }
        body.dark.multi-sunset {
            --primary: #ff4757;
            --primary-hover: #ff6b81;
            --primary-light: #4c1d24;
            background: linear-gradient(135deg, #18191a 0%, #301b1e 100%) !important;
        }
        body.dark.multi-ocean {
            --primary: #00bcd4;
            --primary-hover: #00acc1;
            --primary-light: #1b3d42;
            background: linear-gradient(135deg, #18191a 0%, #162c30 100%) !important;
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
                <button onclick="prevMonth()" class="backgroundColor-var(--bg-card) border-1px_solid_var(--border-color) color-var(--text-main) padding-8px_16px borderRadius-8px fontWeight-bold cursor-pointer ecbounce-3">◀ Prev</button>
                <h2 id="calendar-title" class="fontSize-20px fontWeight-bold color-var(--text-main) margin-0px"></h2>
                <button onclick="nextMonth()" class="backgroundColor-var(--bg-card) border-1px_solid_var(--border-color) color-var(--text-main) padding-8px_16px borderRadius-8px fontWeight-bold cursor-pointer ecbounce-3">Next ▶</button>
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

    <div id="alert-modal" style="display:none;" class="position-fixed top-0px left-0px width-100% height-100% backgroundColor-rgba(0,0,0,0.5) display-flex justifyContent-center alignItems-center zIndex-2000">
        <div class="eccard padding-25px width-100% maxWidth-400px display-flex flexDirection-column gap-15px backgroundColor-var(--bg-card) animate-modal-bounce">
            <div id="alert-modal-title" class="fontSize-18px fontWeight-bold color-var(--text-main)">Notification</div>
            <div id="alert-modal-message" class="color-var(--text-sub) fontSize-14px lineHeight-1.5" style="white-space: pre-wrap;"></div>
            <div class="display-flex justifyContent-flex-end">
                <button id="alert-modal-ok" class="backgroundColor-var(--primary) color-white border-none padding-8px_20px borderRadius-6px fontWeight-bold cursor-pointer ecbounce-3">OK</button>
            </div>
        </div>
    </div>

    <!-- PUBLIC VIEW SLIDING EVENT DETAIL MODAL -->
    <div id="day-events-modal" style="display:none;" class="position-fixed top-0px left-0px width-100% height-100% backgroundColor-rgba(0,0,0,0.5) display-flex justifyContent-center alignItems-center zIndex-1000">
        <div class="eccard width-100% maxWidth-400px backgroundColor-var(--bg-card) animate-modal-bounce overflow-hidden" style="height: 350px; position: relative;">
            <div id="modal-slider" class="display-flex width-200% height-100%" style="transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: translateX(0);">
                
                <!-- Pane 1: Event List -->
                <div class="width-50% height-100% padding-20px display-flex flexDirection-column boxSizing-border-box">
                    <div class="display-flex justifyContent-space-between alignItems-center marginBottom-15px">
                        <span id="day-modal-title" class="fontSize-18px fontWeight-bold color-var(--text-main)">Events</span>
                        <button onclick="closeDayEventsModal()" class="backgroundColor-transparent border-none fontSize-18px cursor-pointer color-var(--text-sub) hover:color-var(--text-main) ecbounce-3">✕</button>
                    </div>
                    <div id="day-modal-list" class="flex-1 overflowY-auto display-flex flexDirection-column gap-10px"></div>
                </div>
                
                <!-- Pane 2: Event Details -->
                <div class="width-50% height-100% padding-20px display-flex flexDirection-column boxSizing-border-box backgroundColor-var(--bg-body)">
                    <div class="display-flex alignItems-center gap-10px marginBottom-15px">
                        <button onclick="slideBackToEventsList()" class="backgroundColor-transparent border-none fontSize-14px cursor-pointer color-var(--primary) fontWeight-bold hover:opacity-0.8 ecbounce-3">◀ Back</button>
                        <span class="fontSize-15px fontWeight-bold color-var(--text-main)">Event Details</span>
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
                            <div id="detail-desc" class="fontSize-13px color-var(--text-sub) lineHeight-1.4 whitespace-pre-wrap marginTop-3px"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const loadedEvents = <?php echo json_encode($events); ?>;
        const hostDisplayName = <?php echo json_encode($user ? $user['display_name'] : ''); ?>;
        let calendarDate = new Date();

        window.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
        });

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
            document.getElementById('day-modal-title').innerText = `Events on ${formattedDate}`;
            
            const listContainer = document.getElementById('day-modal-list');
            listContainer.innerHTML = '';
            
            const dayEvents = loadedEvents.filter(e => e.event_date === dateStr);
            
            if (dayEvents.length === 0) {
                listContainer.innerHTML = `
                    <div class="display-flex flexDirection-column alignItems-center justifyContent-center flex-1 color-var(--text-sub) gap-8px padding-20px">
                        <span class="fontSize-32px">📅</span>
                        <span class="fontSize-13px">No scheduled events today</span>
                    </div>`;
            } else {
                dayEvents.forEach(e => {
                    const item = document.createElement('div');
                    item.className = 'padding-12px borderRadius-8px border-1px_solid_var(--border-color) hover:backgroundColor-var(--primary-light) cursor-pointer transition-0.2s';
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
            document.getElementById('day-events-modal').style.display = 'flex';
            if (window.ECStyleSheet) window.ECStyleSheet.scan();
        }

        function closeDayEventsModal() {
            document.getElementById('day-events-modal').style.display = 'none';
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