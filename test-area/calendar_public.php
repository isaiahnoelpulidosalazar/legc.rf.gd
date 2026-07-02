<?php
require_once 'db.php';

$username = $_GET['user'] ?? '';
$user = null;
$events = [];

if ($username) {
    $stmt = $pdo->prepare("SELECT id, display_name, avatar FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmtEvents = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY event_date ASC");
        $stmtEvents->execute([$user['id']]);
        $events = $stmtEvents->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user ? $user['display_name'] : 'Guest'); ?>'s Calendar - LeGC</title>
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
    </style>
</head>
<body class="light blue">

    <div class="width-100% maxWidth-800px margin-0_auto">
        <?php if ($user): ?>
            <div class="eccard padding-20px marginBottom-20px display-flex alignItems-center gap-15px backgroundColor-var(--bg-card)">
                <img src="<?php echo $user['avatar'] ?: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%231877f2"/><text x="50" y="62" font-family="sans-serif" font-size="40" font-weight="bold" fill="white" text-anchor="middle">' . strtoupper(substr($user['display_name'], 0, 1)) . '</text></svg>'; ?>" class="width-60px height-60px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
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

    <script src="ECStyleSheet.js"></script>
    <script>
        const loadedEvents = <?php echo json_encode($events); ?>;
        let calendarDate = new Date();

        window.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
        });

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

            // Fill empty leading calendar boxes
            for (let i = firstDay - 1; i >= 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'eccard minHeight-90px padding-10px opacity-0.4 backgroundColor-var(--bg-card)';
                cell.innerHTML = `<span class="fontSize-12px color-var(--text-sub)">${prevDaysInMonth - i}</span>`;
                grid.appendChild(cell);
            }

            // Fill active calendar month boxes
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isToday = (new Date().getDate() === day && new Date().getMonth() === month && new Date().getFullYear() === year);
                const highlight = isToday ? 'border-2px_solid_var(--primary)' : 'border-1px_solid_var(--border-color)';

                const cell = document.createElement('div');
                cell.className = `eccard minHeight-90px padding-8px display-flex flexDirection-column justifyContent-space-between backgroundColor-var(--bg-card) ${highlight}`;
                
                let dayEvents = loadedEvents.filter(e => e.event_date === dateStr);
                let eventsHtml = '';
                dayEvents.forEach(e => {
                    eventsHtml += `
                        <div onclick="alert('Event: ${escapeHtml(e.title)}\\nDescription: ${escapeHtml(e.description || 'N/A')}')" class="fontSize-10px backgroundColor-var(--primary) color-white padding-2px_4px borderRadius-4px cursor-pointer textOverflow-ellipsis whiteSpace-nowrap overflow-hidden hover:opacity-0.8" title="${escapeHtml(e.title)}">
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