<?php
session_start();
require_once 'db.php';

$auth_error = "";
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        if ($username && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ./");
                exit;
            } else {
                $auth_error = "Invalid username or password.";
            }
        } else {
            $auth_error = "All fields are required.";
        }
    } elseif ($_POST['action'] === 'register') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $display_name = trim($_POST['display_name']);
        if ($username && $password && $display_name) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $auth_error = "Username is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, display_name) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hash, $display_name]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header("Location: ./");
                exit;
            }
        } else {
            $auth_error = "All fields are required.";
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ./");
    exit;
}

// Fetch active logged in profile
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch();
}

// Read dynamic minigames list
$gamesList = [];
$minigamesDir = __DIR__ . '/minigames';
if (is_dir($minigamesDir)) {
    $dirs = glob($minigamesDir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        if (file_exists($dir . '/index.html')) {
            $folderName = basename($dir);
            $gamesList[] = [
                'folder' => $folderName,
                'name' => ucwords(str_replace(['-', '_'], ' ', $folderName)),
                'path' => 'minigames/' . $folderName . '/index.html'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeGC</title>
    <script src="https://isaiahnoelpulidosalazar.github.io/js/ECStyleSheet.js"></script>
    <style>
        :root {
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            margin: 0;
            font-family: var(--font-family);
            background-color: var(--bg-body);
            color: var(--text-main);
            transition: background 0.25s ease, color 0.25s ease;
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
        body.light.blue { 
            --primary: #1877f2;
            --primary-hover: #166fe5;
            --primary-light: #e7f3ff;
            background: radial-gradient(at 0% 0%, #e0f2fe 0px, transparent 50%), radial-gradient(at 100% 100%, #e0f2fe 0px, transparent 50%), #f1f5f9 !important; 
        }
        body.light.purple { 
            --primary: #8a2be2;
            --primary-hover: #7b1fa2;
            --primary-light: #f3e5f5;
            background: radial-gradient(at 0% 0%, #f3e8ff 0px, transparent 50%), radial-gradient(at 100% 100%, #fae8ff 0px, transparent 50%), #f1f5f9 !important; 
        }
        body.light.green { 
            --primary: #2ecc71;
            --primary-hover: #27ae60;
            --primary-light: #e8f8f5;
            background: radial-gradient(at 0% 0%, #dcfce7 0px, transparent 50%), radial-gradient(at 100% 100%, #f0fdf4 0px, transparent 50%), #f1f5f9 !important; 
        }
        body.light.multi-sunset { 
            --primary: #ff4757;
            --primary-hover: #ff6b81;
            --primary-light: #ffe0e6;
            background: radial-gradient(at 0% 0%, #ffedd5 0px, transparent 50%), radial-gradient(at 100% 100%, #fce7f3 0px, transparent 50%), #f1f5f9 !important; 
        }
        body.light.multi-ocean { 
            --primary: #00bcd4;
            --primary-hover: #00acc1;
            --primary-light: #e0f7fa;
            background: radial-gradient(at 0% 0%, #ccfbf1 0px, transparent 50%), radial-gradient(at 100% 100%, #e0f2fe 0px, transparent 50%), #f1f5f9 !important; 
        }

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
        body.dark.blue { 
            --primary: #2d88ff;
            --primary-hover: #3578e5;
            --primary-light: #263951;
            background: radial-gradient(at 0% 0%, #0c1524 0px, transparent 50%), radial-gradient(at 100% 100%, #07101e 0px, transparent 50%), #030712 !important; 
        }
        body.dark.purple { 
            --primary: #a040ff;
            --primary-hover: #8f2be2;
            --primary-light: #3e2751;
            background: radial-gradient(at 0% 0%, #1a0b2e 0px, transparent 50%), radial-gradient(at 100% 100%, #0d041e 0px, transparent 50%), #03010c !important; 
        }
        body.dark.green { 
            --primary: #2ecc71;
            --primary-hover: #27ae60;
            --primary-light: #1b3d2b;
            background: radial-gradient(at 0% 0%, #052e16 0px, transparent 30%), radial-gradient(at 100% 100%, #022c22 0px, transparent 35%), #020617 !important; 
        }
        body.dark.multi-sunset { 
            --primary: #ff4757;
            --primary-hover: #ff6b81;
            --primary-light: #4c1d24;
            background: radial-gradient(at 0% 0%, #2e1015 0px, transparent 50%), radial-gradient(at 100% 100%, #1e1b4b 0px, transparent 50%), #030712 !important; 
        }
        body.dark.multi-ocean { 
            --primary: #00bcd4;
            --primary-hover: #00acc1;
            --primary-light: #1b3d42;
            background: radial-gradient(at 0% 0%, #115e59 0px, transparent 40%), radial-gradient(at 100% 100%, #075985 0px, transparent 40%), #020617 !important; 
        }

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
        .eccard, #day-events-modal > div, #event-modal > div, #alert-modal > div, [class*="ECModal"] > div, [class*="ec-box"] {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            backdrop-filter: blur(12px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(180%) !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1) !important;
        }

        .sidebar-glass {
            background: var(--bg-card) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border-right: 1px solid var(--border-color) !important;
        }

        .topbar-glass {
            background: var(--bg-card) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        /* SIDEBAR SELECTED EFFECT */
        .active-link {
            background-color: var(--primary) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .active-link span {
            color: #ffffff !important;
        }

        .spinner {
            width: 35px;
            height: 35px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 40px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

<?php if (!$currentUser): ?>
    <!-- UNAUTHENTICATED: LOGIN / REGISTER PORTAL -->
    <div class="width-100% height-100vh display-flex justifyContent-center alignItems-center backgroundColor-var(--bg-body)">
        <div class="eccard width-100% maxWidth-400px padding-30px display-flex flexDirection-column gap-20px backgroundColor-var(--bg-card)">
            <div class="textAlign-center">
                <h1 class="color-var(--primary) fontSize-36px fontWeight-bold margin-0px">LeGC</h1>
                <p class="color-var(--text-sub) fontSize-14px marginTop-5px">Connect with peers and play together</p>
            </div>

            <?php if ($auth_error): ?>
                <div class="padding-10px borderRadius-6px backgroundColor-rgba(255,0,0,0.1) color-red fontSize-13px textAlign-center">
                    <?php echo htmlspecialchars($auth_error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="form-login" method="POST" class="display-flex flexDirection-column gap-15px">
                <input type="hidden" name="action" value="login">
                <div class="display-flex flexDirection-column gap-5px">
                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Username</label>
                    <input type="text" name="username" required class="padding-12px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <div class="display-flex flexDirection-column gap-5px">
                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Password</label>
                    <input type="password" name="password" required class="padding-12px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <button type="submit" class="backgroundColor-var(--primary) hover:backgroundColor-var(--primary-hover) color-white border-none padding-12px borderRadius-6px fontWeight-bold cursor-pointer ecbounce-2 transition-0.2s">Log In</button>
            </form>

            <!-- Registration Form Toggle -->
            <form id="form-register" method="POST" class="display-none flexDirection-column gap-15px">
                <input type="hidden" name="action" value="register">
                <div class="display-flex flexDirection-column gap-5px">
                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Display Name</label>
                    <input type="text" name="display_name" required class="padding-12px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <div class="display-flex flexDirection-column gap-5px">
                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Username</label>
                    <input type="text" name="username" required class="padding-12px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <div class="display-flex flexDirection-column gap-5px">
                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Password</label>
                    <input type="password" name="password" required class="padding-12px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <button type="submit" class="backgroundColor-green hover:backgroundColor-var(--primary-hover) color-white border-none padding-12px borderRadius-6px fontWeight-bold cursor-pointer ecbounce-2 transition-0.2s">Create Account</button>
            </form>

            <div class="borderTop-1px_solid_var(--border-color) paddingTop-15px textAlign-center">
                <a href="#" id="auth-toggle" class="color-var(--primary) fontWeight-bold fontSize-14px textDecoration-none hover:textDecoration-underline">Create an account instead</a>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('form-login');
        const registerForm = document.getElementById('form-register');
        const authToggle = document.getElementById('auth-toggle');

        authToggle.addEventListener('click', (e) => {
            e.preventDefault();
            if (loginForm.classList.contains('display-none')) {
                loginForm.classList.remove('display-none');
                loginForm.classList.add('display-flex');
                registerForm.classList.remove('display-flex');
                registerForm.classList.add('display-none');
                authToggle.innerText = "Create an account instead";
            } else {
                loginForm.classList.add('display-none');
                loginForm.classList.remove('display-flex');
                registerForm.classList.add('display-flex');
                registerForm.classList.remove('display-none');
                authToggle.innerText = "Already have an account? Log In";
            }
        });
    </script>

<?php else: ?>
    <!-- AUTHENTICATED SYSTEM PORTAL -->
    <div class="display-flex flexDirection-column height-100vh overflow-hidden">
        
        <!-- 1. TOPBAR (UPDATED WITH GLASS EFFECT) -->
        <div class="height-60px topbar-glass display-flex justifyContent-space-between alignItems-center paddingLeft-20px paddingRight-20px position-sticky top-0px zIndex-100 boxSizing-border-box">
            
            <!-- Left Side User Info -->
            <div class="display-flex alignItems-center gap-10px">
                <img id="topbar-avatar" src="" class="width-36px height-36px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                <span class="fontWeight-bold color-var(--text-main) fontSize-15px mobile:display-none" id="topbar-name"></span>
            </div>

            <!-- Central Brand Title -->
            <div class="fontSize-22px fontWeight-bold color-var(--primary) cursor-pointer ecbounce-3" onclick="showTab('home')">LeGC</div>

            <!-- Right Side Actions -->
            <div class="display-flex alignItems-center gap-15px">
                <!-- Notifications -->
                <div class="position-relative">
                    <button onclick="toggleNotificationsDropdown()" class="backgroundColor-transparent border-none fontSize-22px cursor-pointer position-relative">
                        🔔
                        <span id="notif-badge" style="display:none;" class="position-absolute top-0px right-0px backgroundColor-red color-white fontSize-9px padding-2px_5px borderRadius-50%">0</span>
                    </button>
                    <!-- Dropdown -->
                    <div id="notif-dropdown" style="display:none;" class="position-absolute right-0px top-35px width-300px eccard padding-12px zIndex-1000 display-flex flexDirection-column gap-10px max-height-350px overflowY-auto">
                        <div class="borderBottom-1px_solid_var(--border-color) paddingBottom-5px fontWeight-bold color-var(--text-main) fontSize-14px">Notifications</div>
                        <div id="notif-list" class="display-flex flexDirection-column gap-8px"></div>
                    </div>
                </div>

                <!-- Logout -->
                <a href="?logout=1" class="backgroundColor-var(--primary) hover:backgroundColor-var(--primary-hover) color-white textDecoration-none padding-8px_16px borderRadius-6px fontSize-13px fontWeight-bold cursor-pointer transition-0.2s ecbounce-3">Logout</a>
            </div>
        </div>

        <!-- 2. MAIN CORE LAYOUT -->
        <div class="display-flex flex-1 height-calc(100vh_-_60px) width-100% overflow-hidden">
            
            <!-- Left Navigation Sidebar (UPDATED WITH GLASS EFFECT) -->
            <div class="width-250px sidebar-glass padding-20px display-flex flexDirection-column gap-10px height-100% boxSizing-border-box mobile:width-70px mobile:padding-10px transition-0.2s">
                <div id="link-home" onclick="showTab('home')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Home</span>
                </div>
                <div id="link-activities" onclick="showTab('activities')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Activities</span>
                </div>
                <div id="link-messages" onclick="showTab('messages')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Messages</span>
                </div>
                <div id="link-minigames" onclick="showTab('minigames')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Minigames</span>
                </div>
                <div id="link-profile" onclick="showTab('profile')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Profile</span>
                </div>
                <div id="link-settings" onclick="showTab('settings')" class="sidebar-link display-flex alignItems-center gap-12px padding-12px borderRadius-8px cursor-pointer transition-0.2s hover:backgroundColor-var(--primary-light)">
                    <span class="fontWeight-600 color-var(--text-main) mobile:display-none">Settings</span>
                </div>
            </div>

            <!-- Content Area Workspaces -->
            <div class="flex-1 padding-25px overflowY-auto height-100% boxSizing-border-box mobile:padding-15px">
                
                <!-- TAB A: HOME FEED -->
                <div id="tab-home" class="tab-content" style="display:none;">
                    <div class="width-100% maxWidth-650px margin-0_auto">
                        <!-- Create Post Interface -->
                        <div class="eccard padding-15px marginBottom-20px display-flex flexDirection-column gap-12px backgroundColor-var(--bg-card)">
                            <div class="fontSize-15px fontWeight-bold color-var(--text-main)">Create Post</div>
                            <textarea id="post-content" class="padding-12px borderRadius-8px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none resize-none fontSize-14px" rows="3" placeholder="What's on your mind?"></textarea>
                            
                            <div class="display-flex justifyContent-space-between alignItems-center flexWrap-wrap gap-10px">
                                <label class="display-flex alignItems-center gap-8px cursor-pointer color-var(--primary) fontWeight-600 hover:opacity-0.8 transition-0.2s fontSize-14px">
                                    📷 Add Image
                                    <input type="file" id="post-image-input" accept="image/png, image/jpeg, image/webp" class="display-none" onchange="previewPostImage(event)">
                                </label>
                                <!-- DYNAMIC MOUNT FOR POST BUTTON -->
                                <div id="post-btn-container"></div>
                            </div>
                            <div id="post-image-preview-container" class="display-none position-relative marginTop-10px width-100% maxHeight-250px borderRadius-8px overflow-hidden">
                                <img id="post-image-preview" src="" class="width-100% height-auto objectFit-cover">
                                <button onclick="clearPostImage()" class="position-absolute top-10px right-10px backgroundColor-rgba(0,0,0,0.6) color-white border-none borderRadius-50% width-30px height-30px cursor-pointer display-flex alignItems-center justifyContent-center fontWeight-bold">✕</button>
                            </div>
                        </div>

                        <!-- Posts Stream -->
                        <div id="posts-container" class="display-flex flexDirection-column gap-15px"></div>
                    </div>
                </div>

                <!-- TAB B: ACTIVITIES (CALENDAR VIEW) -->
                <div id="tab-activities" class="tab-content" style="display:none;">
                    <div class="width-100% maxWidth-850px margin-0_auto">
                        <!-- Public Share Header Link -->
                        <div class="eccard padding-15px marginBottom-20px display-flex justifyContent-space-between alignItems-center flexWrap-wrap gap-10px backgroundColor-var(--bg-card)">
                            <span class="color-var(--text-sub) fontSize-13px">
                                🔗 Public Calendar: <a href="calendar_public.php?user=<?php echo htmlspecialchars($currentUser['username']); ?>" target="_blank" class="color-var(--primary) fontWeight-bold textDecoration-none hover:textDecoration-underline">View public page</a>
                            </span>
                            <span class="color-var(--text-sub) fontSize-11px fontStyle-italic">Public users can read your event schedule</span>
                        </div>

                        <!-- Calendar Grid Navigation -->
                        <div class="display-flex justifyContent-space-between alignItems-center marginBottom-15px">
                            <div id="cal-prev-container"></div>
                            <h2 id="calendar-title" class="fontSize-20px fontWeight-bold color-var(--text-main) margin-0px"></h2>
                            <div class="display-flex gap-10px">
                                <div id="add-event-btn-container"></div>
                                <div id="cal-next-container"></div>
                            </div>
                        </div>

                        <!-- Calendar Master Panel -->
                        <div class="eccard padding-20px backgroundColor-var(--bg-card)">
                            <div class="display-grid gridTemplateColumns-repeat(7,_1fr) gap-10px textAlign-center fontWeight-bold color-var(--text-sub) borderBottom-1px_solid_var(--border-color) paddingBottom-10px marginBottom-10px fontSize-13px">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>
                            <div id="calendar-grid" class="display-grid gridTemplateColumns-repeat(7,_1fr) gap-10px"></div>
                        </div>
                    </div>
                </div>

                <!-- TAB C: DIRECT MESSAGES -->
                <div id="tab-messages" class="tab-content" style="display:none; height:100%;">
                    <div class="width-100% maxWidth-900px margin-0_auto height-100% display-flex flex-row mobile:flexDirection-column gap-15px">
                        <!-- Left Panel: Chat lists -->
                        <div class="width-300px mobile:width-100% eccard display-flex flexDirection-column backgroundColor-var(--bg-card) height-100%">
                            <div class="padding-15px borderBottom-1px_solid_var(--border-color) fontWeight-bold fontSize-16px color-var(--text-main)">Messages</div>
                            <div id="chats-list" class="flex-1 overflowY-auto padding-10px display-flex flexDirection-column gap-5px"></div>
                        </div>

                        <!-- Right Panel: Converstation Stream -->
                        <div class="flex-1 eccard display-flex flexDirection-column backgroundColor-var(--bg-card) height-100%">
                            <div id="chat-header" class="padding-15px borderBottom-1px_solid_var(--border-color) display-flex alignItems-center gap-12px minHeight-50px">
                                <span class="color-var(--text-sub) fontStyle-italic">Select a conversation bubble to begin</span>
                            </div>
                            <div id="chat-messages" class="flex-1 overflowY-auto padding-15px display-flex flexDirection-column gap-10px"></div>
                            
                            <!-- Input Tray -->
                            <div id="chat-input-area" class="padding-15px borderTop-1px_solid_var(--border-color)" style="display:none;">
                                <div class="display-flex gap-10px">
                                    <input type="text" id="message-text" class="flex-1 padding-12px borderRadius-8px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none" placeholder="Type a message..." onkeydown="handleChatKey(event)">
                                    <div id="send-msg-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB D: MINIGAMES -->
                <div id="tab-minigames" class="tab-content" style="display:none;">
                    <div class="width-100% maxWidth-850px margin-0_auto display-flex flexDirection-column gap-20px">
                        <div class="display-flex justifyContent-space-between alignItems-center flexWrap-wrap gap-15px">
                            <div class="fontSize-20px fontWeight-bold color-var(--text-main)">Minigames</div>
                            <!-- Real-time dynamic search filter -->
                            <input type="text" id="arcade-search" oninput="filterMinigames()" class="padding-10px_16px width-100% maxWidth-300px borderRadius-8px border-1px_solid_var(--border-color) backgroundColor-var(--bg-card) color-var(--text-main) outline-none" placeholder="Search minigames...">
                        </div>
                        
                        <!-- List view container instead of previous grid -->
                        <div id="minigames-container" class="display-flex flexDirection-column gap-10px"></div>
                        
                        <!-- Embedded game frame -->
                        <div id="game-arena" style="display:none;" class="eccard marginTop-20px padding-15px position-relative display-flex flexDirection-column gap-15px backgroundColor-var(--bg-card)">
                            <div class="display-flex justifyContent-space-between alignItems-center">
                                <span id="arena-game-title" class="fontWeight-bold fontSize-18px color-var(--text-main)">Playing</span>
                                <div id="exit-game-container"></div>
                            </div>
                            <iframe id="game-iframe" class="width-100% height-500px borderRadius-8px border-none" src=""></iframe>
                        </div>
                    </div>
                </div>

                <!-- TAB E: PROFILE MANAGEMENT & FRIEND EXPLORER -->
                <div id="tab-profile" class="tab-content" style="display:none;">
                    <div class="width-100% maxWidth-800px margin-0_auto display-flex flexDirection-column gap-25px">
                        
                        <!-- Profile Editing Pane -->
                        <div class="eccard padding-20px display-flex flexDirection-column gap-15px backgroundColor-var(--bg-card)">
                            <div class="fontSize-18px fontWeight-bold color-var(--text-main)">Edit Profile Settings</div>
                            <div class="display-flex flexWrap-wrap gap-20px alignItems-center">
                                <img id="profile-avatar-preview" src="" class="width-80px height-80px borderRadius-50% objectFit-cover border-2px_solid_var(--primary)">
                                <div class="display-flex flexDirection-column gap-5px">
                                    <label class="backgroundColor-var(--primary) color-white padding-8px_16px borderRadius-6px cursor-pointer fontSize-13px fontWeight-bold ecbounce-3">
                                        Upload New Avatar
                                        <input type="file" id="profile-avatar-input" accept="image/png, image/jpeg, image/webp" class="display-none" onchange="previewProfileAvatar(event)">
                                    </label>
                                    <span class="color-var(--text-sub) fontSize-11px">Auto-saves to database in base64 format</span>
                                </div>
                            </div>

                            <div class="display-flex flexWrap-wrap gap-15px">
                                <div class="flex-1 minWidth-250px display-flex flexDirection-column gap-5px">
                                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Display Name</label>
                                    <input type="text" id="profile-display-name" class="padding-10px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                                </div>
                                <div class="flex-1 minWidth-250px display-flex flexDirection-column gap-5px">
                                    <label class="fontSize-13px fontWeight-bold color-var(--text-sub)">Update Password (Optional)</label>
                                    <input type="password" id="profile-password" class="padding-10px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none" placeholder="Enter password to change">
                                </div>
                            </div>
                            <div class="display-flex justifyContent-flex-end">
                                <div id="save-profile-container"></div>
                            </div>
                        </div>

                        <!-- Explore Friend Finder Panel -->
                        <div class="eccard padding-20px display-flex flexDirection-column gap-15px backgroundColor-var(--bg-card)">
                            <div class="fontSize-18px fontWeight-bold color-var(--text-main)">Find Peers & Friends</div>
                            <p class="color-var(--text-sub) fontSize-13px margin-0px">Establish mutual connections with other registered users on LeGC to exchange posts, chats, and calendars.</p>
                            <div id="explore-friends-list" class="display-flex flexDirection-column gap-10px"></div>
                        </div>

                    </div>
                </div>

                <!-- TAB F: SETTINGS & VISUAL STYLING SYSTEM -->
                <div id="tab-settings" class="tab-content" style="display:none;">
                    <div class="width-100% maxWidth-650px margin-0_auto eccard padding-25px display-flex flexDirection-column gap-20px backgroundColor-var(--bg-card)">
                        <div class="fontSize-20px fontWeight-bold color-var(--text-main) borderBottom-1px_solid_var(--border-color) paddingBottom-10px">Theme Customization Panel</div>
                        
                        <div class="display-flex flexDirection-column gap-10px">
                            <span class="fontWeight-bold color-var(--text-main) fontSize-15px">Theme Mode</span>
                            <div id="theme-mode-radio-container"></div>
                        </div>

                        <div class="display-flex flexDirection-column gap-10px">
                            <span class="fontWeight-bold color-var(--text-main) fontSize-15px">Color Accents</span>
                            <div id="theme-color-radio-container"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- DATA EXPORTS TO SPA CONTROLLER -->
    <script>
        const currentUser = <?php echo json_encode([
            'id' => $currentUser['id'],
            'username' => $currentUser['username'],
            'display_name' => $currentUser['display_name'],
            'avatar' => $currentUser['avatar'],
            'theme_mode' => $currentUser['theme_mode'],
            'theme_color' => $currentUser['theme_color']
        ]); ?>;

        const minigamesList = <?php echo json_encode($gamesList); ?>;
    </script>

    <!-- JS MODULE CONTROLLER -->
    <script>
        let postBase64Image = null;
        let profileBase64Avatar = null;
        let activeChatFriendId = null;
        let chatInterval = null;
        let calendarDate = new Date();
        let loadedEvents = [];

        // ECELEMENTS CO-ORDINATION REGISTRY
        let registeredComponents = [];

        let appAlertModal = null;
        let appEventModal = null;
        let appDayEventsModal = null;

        window.addEventListener('DOMContentLoaded', () => {
            initECElements();
            applyThemeState(currentUser.theme_mode, currentUser.theme_color);
            document.getElementById('topbar-avatar').src = currentUser.avatar || getDefaultAvatar(currentUser.display_name);
            document.getElementById('topbar-name').innerText = currentUser.display_name;
            showTab('home');
            loadNotifications();
            setInterval(loadNotifications, 8000);
        });

        // INTEGRATE NEW DYNAMIC REGISTRY PIPELINE
        function registerComponent(comp) {
            if (comp) {
                registeredComponents.push(comp);
                applyThemeToComponent(comp); // Inject active colors immediately
            }
            return comp;
        }

        // FORCE INJECT ACTIVE CUSTOM ECTHEME PROFILES INTO ALL ACTIVE COMPONENT HANDLERS
        function applyThemeToComponent(comp) {
            const isDark = document.body.classList.contains('dark');
            if (isDark) {
                comp.enableDarkMode();
            } else {
                comp.disableDarkMode();
            }

            const colorClass = getActiveThemeColor();
            let primaryColor = "#1877f2"; // Fallback blue
            
            if (colorClass === 'purple') primaryColor = "#8a2be2";
            else if (colorClass === 'green') primaryColor = "#2ecc71";
            else if (colorClass === 'multi-sunset') primaryColor = "#ff4757";
            else if (colorClass === 'multi-ocean') primaryColor = "#00bcd4";

            // Establish unified dynamic configuration mapping
            const activeTheme = new ECTheme({
                primary: primaryColor,
                background: isDark ? "rgba(17, 24, 39, 0.7)" : "rgba(255, 255, 255, 0.65)",
                text: isDark ? "#f9fafb" : "#0f172a",
                textMuted: isDark ? "#9ca3af" : "#475569",
                border: isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(15, 23, 42, 0.08)"
            });

            comp.setTheme(activeTheme);
        }

        function updateAllComponentThemes() {
            registeredComponents.forEach(comp => {
                applyThemeToComponent(comp);
            });
        }

        // BOOTSTRAP INITIALIZER (UPDATED WITH COMPONENT REGISTRATIONS AND STRIPPED EMOJIS)
        function initECElements() {
            // 1. Alert Modal
            appAlertModal = registerComponent(new ECModal("Notification"));
            document.body.appendChild(appAlertModal.element);

            // 2. Add Event Form
            appEventModal = registerComponent(new ECModal("Create New Event"));
            document.body.appendChild(appEventModal.element);

            const eventForm = document.createElement('div');
            eventForm.className = "display-flex flexDirection-column gap-15px width-100% minWidth-320px";
            eventForm.innerHTML = `
                <div class="display-flex flexDirection-column gap-4px">
                    <label class="color-var(--text-sub) fontSize-12px fontWeight-bold">Event Title</label>
                    <input type="text" id="event-title" class="padding-10px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
                <div class="display-flex flexDirection-column gap-4px">
                    <label class="color-var(--text-sub) fontSize-12px fontWeight-bold">Description</label>
                    <textarea id="event-desc" class="padding-10px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) resize-none outline-none" rows="3"></textarea>
                </div>
                <div class="display-flex flexDirection-column gap-4px">
                    <label class="color-var(--text-sub) fontSize-12px fontWeight-bold">Date</label>
                    <input type="date" id="event-date" class="padding-10px borderRadius-6px border-1px_solid_var(--border-color) backgroundColor-var(--bg-body) color-var(--text-main) outline-none">
                </div>
            `;
            appEventModal.setContent(eventForm);
            appEventModal._footer.innerHTML = '';
            appEventModal.addFooterButton("Cancel", () => appEventModal.close(), "outline");
            appEventModal.addFooterButton("Save", () => submitAddEvent());

            // 3. Sliding Day list logs
            appDayEventsModal = registerComponent(new ECModal("Events", { width: "320px" }));
            document.body.appendChild(appDayEventsModal.element);

            const dayLogsLayout = document.createElement('div');
            dayLogsLayout.className = "overflow-hidden width-100% position-relative";
            dayLogsLayout.style.height = "280px";
            dayLogsLayout.innerHTML = `
                <div id="modal-slider" class="display-flex width-200% height-100%" style="transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: translateX(0);">
                    <div class="width-50% height-100% display-flex flexDirection-column boxSizing-border-box">
                        <div id="day-modal-list" class="flex-1 overflowY-auto display-flex flexDirection-column gap-10px"></div>
                    </div>
                    <div class="width-50% height-100% display-flex flexDirection-column boxSizing-border-box">
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

            // 4. Action Post Button
            const postBtnContainer = document.getElementById('post-btn-container');
            if (postBtnContainer) {
                const btn = registerComponent(new ECButton("Post"));
                btn.onClick(() => submitPost());
                postBtnContainer.appendChild(btn.element);
            }

            // 5. Add Event Action
            const addEventContainer = document.getElementById('add-event-btn-container');
            if (addEventContainer) {
                const btn = registerComponent(new ECButton("+ Add Event"));
                btn.onClick(() => openAddEventModal());
                addEventContainer.appendChild(btn.element);
            }

            // 6. Navigation Triggers
            const prevContainer = document.getElementById('cal-prev-container');
            if (prevContainer) {
                const btn = registerComponent(new ECButton("◀ Prev", { variant: "outline" }));
                btn.onClick(() => prevMonth());
                prevContainer.appendChild(btn.element);
            }
            const nextContainer = document.getElementById('cal-next-container');
            if (nextContainer) {
                const btn = registerComponent(new ECButton("Next ▶", { variant: "outline" }));
                btn.onClick(() => nextMonth());
                nextContainer.appendChild(btn.element);
            }

            // 7. Message Sending Tool
            const sendMsgContainer = document.getElementById('send-msg-container');
            if (sendMsgContainer) {
                const btn = registerComponent(new ECButton("Send"));
                btn.onClick(() => sendChatMessage());
                sendMsgContainer.appendChild(btn.element);
            }

            // 8. Exit Game Trigger
            const exitGameContainer = document.getElementById('exit-game-container');
            if (exitGameContainer) {
                const btn = registerComponent(new ECButton("Exit Arcade", { variant: "outline" }));
                btn.onClick(() => closeGameArena());
                exitGameContainer.appendChild(btn.element);
            }

            // 9. Save settings trigger
            const saveProfileContainer = document.getElementById('save-profile-container');
            if (saveProfileContainer) {
                const btn = registerComponent(new ECButton("Save Changes"));
                btn.onClick(() => saveProfileChanges());
                saveProfileContainer.appendChild(btn.element);
            }

            // 10. Theme Toggles (EMOJIS EXCLUDED)
            const modeRadioContainer = document.getElementById('theme-mode-radio-container');
            if (modeRadioContainer) {
                const radioMode = registerComponent(new ECRadio("theme_mode", [
                    { value: "light", label: "Light Theme", checked: currentUser.theme_mode === 'light' },
                    { value: "dark", label: "Dark Theme", checked: currentUser.theme_mode === 'dark' }
                ]));
                radioMode.onChange((val) => saveLocalTheme(val, null));
                modeRadioContainer.appendChild(radioMode.element);
            }

            const colorRadioContainer = document.getElementById('theme-color-radio-container');
            if (colorRadioContainer) {
                const radioColor = registerComponent(new ECRadio("theme_color", [
                    { value: "blue", label: "Classic Blue", checked: currentUser.theme_color === 'blue' },
                    { value: "purple", label: "Royal Purple", checked: currentUser.theme_color === 'purple' },
                    { value: "green", label: "Forest Green", checked: currentUser.theme_color === 'green' },
                    { value: "multi-sunset", label: "Sunset (Gradient Theme)", checked: currentUser.theme_color === 'multi-sunset' },
                    { value: "multi-ocean", label: "Ocean (Gradient Theme)", checked: currentUser.theme_color === 'multi-ocean' }
                ]));
                radioColor.onChange((val) => saveLocalTheme(null, val));
                colorRadioContainer.appendChild(radioColor.element);
            }
        }

        // REDESIGNED POPUP PORTALS USING DYNAMIC CO-ORDINATED APPALERTMODAL
        function showAlert(title, message, callback = null) {
            appAlertModal.setTitle(title);
            appAlertModal.clearContent();
            
            const msgEl = document.createElement('div');
            msgEl.className = "color-var(--text-sub) fontSize-14px lineHeight-1.5";
            msgEl.style.whiteSpace = "pre-wrap";
            msgEl.innerText = message;
            appAlertModal.setContent(msgEl);

            appAlertModal._footer.innerHTML = '';
            appAlertModal.addFooterButton("OK", () => {
                appAlertModal.close();
                if (callback) callback();
            });

            applyThemeToComponent(appAlertModal); // Inject theme mapping
            appAlertModal.open();
        }

        function showEventDetailPopup(title, description, host) {
            showAlert("Event Details", `Title: ${title}\nDescription: ${description}\nHost: ${host}`);
        }

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
                        <span class="fontSize-32px">📅</span>
                        <span class="fontSize-13px">No scheduled events today</span>
                    </div>`;
            } else {
                dayEvents.forEach(e => {
                    const item = document.createElement('div');
                    item.className = 'padding-12px borderRadius-8px border-1px_solid_var(--border-color) hover:backgroundColor-var(--primary-light) cursor-pointer transition-0.2s';
                    item.onclick = (event) => {
                        event.stopPropagation();
                        showEventDetailSlide(e.title, e.description || "No description provided.", e.display_name);
                    };
                    item.innerHTML = `
                        <div class="fontWeight-bold fontSize-14px color-var(--text-main) textOverflow-ellipsis overflow-hidden whiteSpace-nowrap">${escapeHtml(e.title)}</div>
                        <div class="fontSize-11px color-var(--text-sub) marginTop-3px">by ${escapeHtml(e.display_name)}</div>
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

        function openAddEventModal() { appEventModal.open(); }
        function closeAddEventModal() { appEventModal.close(); }

        // 2. SPA ROUTER SWITCH
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.sidebar-link').forEach(el => {
                el.classList.remove('active-link');
            });

            const activeTab = document.getElementById('tab-' + tabName);
            if (activeTab) activeTab.style.display = 'block';

            const activeLink = document.getElementById('link-' + tabName);
            if (activeLink) {
                activeLink.classList.add('active-link');
            }

            if (tabName !== 'messages' && chatInterval) {
                clearInterval(chatInterval);
                activeChatFriendId = null;
            }

            if (tabName === 'home') loadPosts();
            else if (tabName === 'activities') loadCalendar();
            else if (tabName === 'messages') loadChats();
            else if (tabName === 'minigames') loadMinigamesView();
            else if (tabName === 'profile') loadProfileView();
            else if (tabName === 'settings') loadSettingsView();

            updateAllComponentThemes(); // Repaint registered components with matched settings
            if (window.ECStyleSheet) window.ECStyleSheet.scan();
        }

        function applyThemeState(mode, color) {
            document.body.className = `${mode} ${color}`;
            updateAllComponentThemes(); // Direct re-theme mapping
        }

        function saveLocalTheme(mode, color) {
            let currentMode = mode || (document.body.classList.contains('dark') ? 'dark' : 'light');
            let currentColor = color || getActiveThemeColor();
            applyThemeState(currentMode, currentColor);

            fetch('api.php?action=update_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme_mode: currentMode, theme_color: currentColor })
            });
        }

        function getActiveThemeColor() {
            const list = ['blue', 'purple', 'green', 'multi-sunset', 'multi-ocean'];
            for (let c of list) {
                if (document.body.classList.contains(c)) return c;
            }
            return 'blue';
        }

        function loadSettingsView() {}

        const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

        function previewPostImage(event) {
            const file = event.target.files[0];
            if (file) {
                if (!SUPPORTED_IMAGE_TYPES.includes(file.type)) {
                    showAlert("Format Error", "Unsupported file type! Please upload PNG, JPG, or WEBP.");
                    event.target.value = "";
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    postBase64Image = e.target.result;
                    document.getElementById('post-image-preview').src = postBase64Image;
                    document.getElementById('post-image-preview-container').classList.remove('display-none');
                };
                reader.readAsDataURL(file);
            }
        }

        function clearPostImage() {
            postBase64Image = null;
            document.getElementById('post-image-input').value = "";
            document.getElementById('post-image-preview-container').classList.add('display-none');
        }

        function submitPost() {
            const textEl = document.getElementById('post-content');
            const content = textEl.value.trim();
            if (!content && !postBase64Image) return;

            fetch('api.php?action=create_post', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: content, image: postBase64Image })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    textEl.value = '';
                    clearPostImage();
                    loadPosts();
                } else {
                    showAlert("Action Denied", "Unable to post: " + (res.error || "Unknown backend mismatch."));
                }
            })
            .catch(err => {
                console.error("Post exception:", err);
                showAlert("Network Error", "Unable to connect to the database host.");
            });
        }

        // 3. HOME POSTS LOADING SPINNER COMPILER
        function loadPosts() {
            const container = document.getElementById('posts-container');
            container.innerHTML = '<div class="spinner"></div>'; // Inject loading animation

            fetch('api.php?action=get_posts')
                .then(res => res.json())
                .then(posts => {
                    container.innerHTML = '';
                    if (!posts || posts.length === 0) {
                        container.innerHTML = `
                            <div class="eccard padding-30px textAlign-center color-var(--text-sub) backgroundColor-var(--bg-card)">
                                <span class="fontSize-36px">👥</span>
                                <p class="marginTop-10px fontSize-14px">Follow and add friends under Profile view to see shared activities and posts here.</p>
                            </div>`;
                        return;
                    }
                    posts.forEach(p => {
                        const avatar = p.avatar || getDefaultAvatar(p.display_name);
                        const imgNode = p.image ? `<img src="${p.image}" class="width-100% borderRadius-8px maxHeight-350px objectFit-cover marginTop-10px">` : '';
                        const postCard = document.createElement('div');
                        postCard.className = 'eccard padding-20px display-flex flexDirection-column gap-12px backgroundColor-var(--bg-card) ecbounce-1';
                        postCard.innerHTML = `
                            <div class="display-flex alignItems-center gap-10px">
                                <img src="${avatar}" class="width-40px height-40px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                                <div class="display-flex flexDirection-column">
                                    <span class="fontWeight-bold color-var(--text-main) fontSize-14px">${escapeHtml(p.display_name)}</span>
                                    <span class="color-var(--text-sub) fontSize-11px">${p.created_at}</span>
                                </div>
                            </div>
                            <div class="color-var(--text-main) fontSize-14px lineHeight-1.5">${escapeHtml(p.content)}</div>
                            ${imgNode}
                        `;
                        container.appendChild(postCard);
                    });
                    if (window.ECStyleSheet) window.ECStyleSheet.scan();
                })
                .catch(err => {
                    console.error("Load posts error:", err);
                    container.innerHTML = '<p class="color-var(--text-sub) textAlign-center">Failed to fetch feed parameters.</p>';
                });
        }

        function loadCalendar() {
            fetch('api.php?action=get_events')
                .then(res => {
                    if (!res.ok) throw new Error("HTTP connection error");
                    return res.json();
                })
                .then(events => {
                    loadedEvents = Array.isArray(events) ? events : [];
                    renderCalendar();
                })
                .catch(err => {
                    console.error("Load events failure, loading empty layout fallback:", err);
                    loadedEvents = [];
                    renderCalendar();
                });
        }

        // 4. RENDERING BOUNCY, CLICKABLE DAY CELLS
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

            // Grey leading cells
            for (let i = firstDay - 1; i >= 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'eccard minHeight-90px padding-10px opacity-0.4 backgroundColor-var(--bg-card)';
                cell.innerHTML = `<span class="fontSize-12px color-var(--text-sub)">${prevDaysInMonth - i}</span>`;
                grid.appendChild(cell);
            }

            // Clickable Day Cells
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isToday = (new Date().getDate() === day && new Date().getMonth() === month && new Date().getFullYear() === year);
                const highlight = isToday ? 'border-2px_solid_var(--primary)' : 'border-1px_solid_var(--border-color)';

                const cell = document.createElement('div');
                // Added cursor-pointer & ecbounce-2 to the entire Day Cell
                cell.className = `eccard minHeight-90px padding-8px display-flex flexDirection-column justifyContent-space-between backgroundColor-var(--bg-card) cursor-pointer ecbounce-2 ${highlight}`;
                cell.onclick = () => openDayEventsModal(dateStr, day);
                
                let dayEvents = loadedEvents.filter(e => e.event_date === dateStr);
                let eventsHtml = '';
                dayEvents.forEach(e => {
                    // Stripped clicks/interactive effects from nested event tags to preserve day-click triggers
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
        
        function submitAddEvent() {
            const title = document.getElementById('event-title').value.trim();
            const description = document.getElementById('event-desc').value.trim();
            const date = document.getElementById('event-date').value;

            if (!title || !date) {
                showAlert("Field Error", "Event title and date are required parameters.");
                return;
            }

            fetch('api.php?action=add_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, description, event_date: date })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    closeAddEventModal();
                    document.getElementById('event-title').value = '';
                    document.getElementById('event-desc').value = '';
                    document.getElementById('event-date').value = '';
                    loadCalendar();
                } else {
                    showAlert("Database Error", "Unable to write schedule parameters: " + (res.error || "Unknown backend mismatch."));
                }
            })
            .catch(err => {
                console.error("Event creation error:", err);
                showAlert("Network Error", "Unable to transmit schedule parameters.");
            });
        }

        function loadChats() {
            fetch('api.php?action=get_friends_list')
                .then(res => res.json())
                .then(friends => {
                    const list = document.getElementById('chats-list');
                    list.innerHTML = '';
                    if (friends.length === 0) {
                        list.innerHTML = `<span class="color-var(--text-sub) fontSize-12px padding-10px">Find and add friends from the profile tab to start messaging!</span>`;
                        return;
                    }
                    friends.forEach(f => {
                        const avatar = f.avatar || getDefaultAvatar(f.display_name);
                        const isSelected = activeChatFriendId === f.id;
                        const cardBg = isSelected ? 'backgroundColor-var(--primary-light)' : 'hover:backgroundColor-var(--bg-body)';
                        
                        const item = document.createElement('div');
                        item.className = `display-flex alignItems-center gap-10px padding-10px borderRadius-8px cursor-pointer transition-0.2s ecbounce-2 ${cardBg}`;
                        item.onclick = () => selectChat(f.id, f.display_name, avatar);
                        item.innerHTML = `
                            <img src="${avatar}" class="width-36px height-36px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                            <div class="display-flex flexDirection-column">
                                <span class="fontWeight-bold color-var(--text-main) fontSize-13px">${escapeHtml(f.display_name)}</span>
                                <span class="color-var(--text-sub) fontSize-10px">@${f.username}</span>
                            </div>
                        `;
                        list.appendChild(item);
                    });
                    if (window.ECStyleSheet) window.ECStyleSheet.scan();
                });
        }

        function selectChat(friendId, name, avatar) {
            activeChatFriendId = friendId;
            document.getElementById('chat-header').innerHTML = `
                <img src="${avatar}" class="width-36px height-36px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                <div class="display-flex flexDirection-column">
                    <span class="fontWeight-bold color-var(--text-main) fontSize-14px">${escapeHtml(name)}</span>
                    <span class="color-green fontSize-10px">● Active Chat</span>
                </div>
            `;
            document.getElementById('chat-input-area').style.display = 'block';
            loadChats();
            loadMessages();

            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(loadMessages, 3000);
        }

        function loadMessages() {
            if (!activeChatFriendId) return;
            fetch(`api.php?action=get_messages&friend_id=${activeChatFriendId}`)
                .then(res => res.json())
                .then(msgs => {
                    const container = document.getElementById('chat-messages');
                    const isScrolledToBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 50;

                    container.innerHTML = '';
                    msgs.forEach(m => {
                        const isSender = (m.sender_id == currentUser.id);
                        const align = isSender ? 'alignSelf-flex-end' : 'alignSelf-flex-start';
                        const bg = isSender ? 'backgroundColor-var(--primary) color-white' : 'backgroundColor-var(--bg-body) color-var(--text-main)';
                        const border = isSender ? '' : 'border-1px_solid_var(--border-color)';

                        const bubble = document.createElement('div');
                        bubble.className = `${align} ${bg} ${border} padding-10px_14px borderRadius-15px maxWidth-70% fontSize-13px wordBreak-break-word ecbounce-1`;
                        bubble.innerText = m.message;
                        container.appendChild(bubble);
                    });

                    if (isScrolledToBottom || container.scrollTop === 0) {
                        container.scrollTop = container.scrollHeight;
                    }
                    if (window.ECStyleSheet) window.ECStyleSheet.scan();
                });
        }

        function sendChatMessage() {
            const input = document.getElementById('message-text');
            const message = input.value.trim();
            if (!message || !activeChatFriendId) return;

            fetch('api.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friend_id: activeChatFriendId, message })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    input.value = '';
                    loadMessages();
                }
            });
        }

        function handleChatKey(e) {
            if (e.key === 'Enter') sendChatMessage();
        }

        function loadMinigamesView(query = "") {
            const container = document.getElementById('minigames-container');
            container.innerHTML = '';
            
            if (minigamesList.length === 0) {
                container.innerHTML = `<span class="color-var(--text-sub) fontSize-13px">No interactive game objects loaded.</span>`;
                return;
            }

            const filtered = minigamesList.filter(g => g.name.toLowerCase().includes(query.toLowerCase()));

            if (filtered.length === 0) {
                container.innerHTML = `<span class="color-var(--text-sub) fontSize-13px padding-15px">No matches found for "${escapeHtml(query)}"</span>`;
                return;
            }

            filtered.forEach(game => {
                const card = document.createElement('div');
                card.className = 'eccard padding-15px display-flex alignItems-center justifyContent-space-between cursor-pointer ecbounce-2 backgroundColor-var(--bg-card) hover:backgroundColor-var(--primary-light) transition-0.2s';
                card.onclick = () => launchGame(game.path, game.name);
                card.innerHTML = `
                    <div class="display-flex alignItems-center gap-15px">
                        <span class="fontSize-28px">🎮</span>
                        <div class="display-flex flexDirection-column">
                            <span class="fontWeight-bold color-var(--text-main) fontSize-15px">${escapeHtml(game.name)}</span>
                            <span class="color-var(--text-sub) fontSize-11px">Local Arcade Module</span>
                        </div>
                    </div>
                    <div class="display-flex alignItems-center gap-5px color-var(--primary) fontWeight-bold fontSize-13px">
                        Play Game <span class="fontSize-10px">▶</span>
                    </div>
                `;
                container.appendChild(card);
            });
            if (window.ECStyleSheet) window.ECStyleSheet.scan();
        }

        function filterMinigames() {
            const query = document.getElementById('arcade-search').value;
            loadMinigamesView(query);
        }

        function launchGame(path, name) {
            document.getElementById('minigames-container').style.display = 'none';
            const arena = document.getElementById('game-arena');
            arena.style.display = 'flex';
            document.getElementById('arena-game-title').innerText = `Playing: ${name}`;
            document.getElementById('game-iframe').src = path;
        }

        function closeGameArena() {
            document.getElementById('game-arena').style.display = 'none';
            document.getElementById('game-iframe').src = '';
            document.getElementById('minigames-container').style.display = 'grid';
        }

        function loadProfileView() {
            document.getElementById('profile-display-name').value = currentUser.display_name;
            document.getElementById('profile-avatar-preview').src = currentUser.avatar || getDefaultAvatar(currentUser.display_name);
            loadExploreFriends();
        }

        function previewProfileAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                if (!SUPPORTED_IMAGE_TYPES.includes(file.type)) {
                    showAlert("Format Error", "Unsupported file type! Please upload PNG, JPG, or WEBP.");
                    event.target.value = "";
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    profileBase64Avatar = e.target.result;
                    document.getElementById('profile-avatar-preview').src = profileBase64Avatar;
                };
                reader.readAsDataURL(file);
            }
        }

        function saveProfileChanges() {
            const displayName = document.getElementById('profile-display-name').value.trim();
            const password = document.getElementById('profile-password').value;

            if (!displayName) return;

            fetch('api.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ display_name: displayName, avatar: profileBase64Avatar, password: password || null })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showAlert("Settings Saved", "Profile parameters updated successfully.", () => {
                        window.location.reload();
                    });
                }
            });
        }

        function loadExploreFriends() {
            fetch('api.php?action=get_all_users')
                .then(res => res.json())
                .then(users => {
                    const list = document.getElementById('explore-friends-list');
                    list.innerHTML = '';
                    if (users.length === 0) {
                        list.innerHTML = `<span class="color-var(--text-sub) fontSize-13px">No alternative accounts present to connect with.</span>`;
                        return;
                    }
                    users.forEach(u => {
                        const avatar = u.avatar || getDefaultAvatar(u.display_name);
                        const isFriend = u.friendship_status === 'accepted';
                        const text = isFriend ? 'Remove Connection' : 'Establish Connection';

                        const card = document.createElement('div');
                        card.className = 'eccard padding-12px display-flex alignItems-center justifyContent-space-between backgroundColor-var(--bg-card) ecbounce-1';
                        
                        const profileLayout = document.createElement('div');
                        profileLayout.className = 'display-flex alignItems-center gap-10px';
                        profileLayout.innerHTML = `
                            <img src="${avatar}" class="width-36px height-36px borderRadius-50% objectFit-cover border-1px_solid_var(--border-color)">
                            <div class="display-flex flexDirection-column">
                                <span class="fontWeight-bold color-var(--text-main) fontSize-13px">${escapeHtml(u.display_name)}</span>
                                <span class="color-var(--text-sub) fontSize-10px">@${u.username}</span>
                            </div>
                        `;
                        card.appendChild(profileLayout);

                        // Mount active modular connection actions directly
                        const btn = registerComponent(new ECButton(text, { variant: isFriend ? "outline" : "filled" }));
                        btn.onClick(() => toggleFriendship(u.id));
                        card.appendChild(btn.element);

                        list.appendChild(card);
                    });
                    if (window.ECStyleSheet) window.ECStyleSheet.scan();
                });
        }

        function toggleFriendship(targetId) {
            fetch('api.php?action=toggle_friendship', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ target_id: targetId })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) loadExploreFriends();
            });
        }

        function loadNotifications() {
            fetch('api.php?action=get_notifications')
                .then(res => res.json())
                .then(notifs => {
                    const badge = document.getElementById('notif-badge');
                    const list = document.getElementById('notif-list');

                    const unreadCount = notifs.filter(n => n.is_read == 0).length;
                    if (unreadCount > 0) {
                        badge.innerText = unreadCount;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }

                    list.innerHTML = '';
                    if (notifs.length === 0) {
                        list.innerHTML = `<span class="color-var(--text-sub) fontSize-11px padding-10px textAlign-center">No new updates</span>`;
                        return;
                    }
                    notifs.forEach(n => {
                        const styleClass = n.is_read == 0 ? 'backgroundColor-var(--primary-light)' : 'backgroundColor-transparent';
                        const item = document.createElement('div');
                        item.className = `padding-8px borderRadius-6px fontSize-12px color-var(--text-main) ${styleClass} borderBottom-1px_solid_var(--border-color) ecbounce-1`;
                        item.innerText = n.content;
                        list.appendChild(item);
                    });
                    if (window.ECStyleSheet) window.ECStyleSheet.scan();
                });
        }

        function toggleNotificationsDropdown() {
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'flex';
                fetch('api.php?action=mark_notifications_read')
                    .then(res => res.json())
                    .then(() => {
                        setTimeout(loadNotifications, 1000);
                    });
            } else {
                dropdown.style.display = 'none';
            }
        }

        function getDefaultAvatar(name) {
            const char = name ? name.charAt(0).toUpperCase() : 'U';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%231877f2"/><text x="50" y="65" font-family="sans-serif" font-size="45" font-weight="bold" fill="white" text-anchor="middle">${char}</text></svg>`;
            return `data:image/svg+xml;base64,${btoa(svg)}`;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
<?php endif; ?>