<?php
ob_start();
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

function generateSystemNotification($pdo, $uid, $msg) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, content) VALUES (?, ?)");
    $stmt->execute([$uid, $msg]);
}

try {
    // 1. CREATE NEW POST
    if ($action === 'create_post') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $content = trim($raw['content'] ?? '');
        $image = $raw['image'] ?? null;

        if (!$content && !$image) {
            echo json_encode(['success' => false, 'error' => 'Input body empty']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $content, $image]);

        // Fetch friends using positional parameters
        $stmtFriends = $pdo->prepare("
            SELECT friend_id AS fid FROM friendships WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id AS fid FROM friendships WHERE friend_id = ? AND status = 'accepted'
        ");
        $stmtFriends->execute([$userId, $userId]);
        $friends = $stmtFriends->fetchAll();

        $stmtUser = $pdo->prepare("SELECT display_name FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $senderName = $stmtUser->fetchColumn();

        foreach ($friends as $f) {
            if (!empty($f['fid'])) {
                generateSystemNotification($pdo, $f['fid'], "{$senderName} uploaded a new post.");
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // 2. GET POSTS
    if ($action === 'get_posts') {
        $stmt = $pdo->prepare("
            SELECT p.*, u.display_name, u.avatar 
            FROM posts p
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? 
               OR p.user_id IN (
                   SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'
                   UNION
                   SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted'
               )
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 3. CALENDAR EVENTS
    if ($action === 'get_events') {
        $stmt = $pdo->prepare("
            SELECT e.*, u.display_name 
            FROM events e
            JOIN users u ON e.user_id = u.id 
            WHERE e.user_id = ? 
               OR e.user_id IN (
                   SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'
                   UNION
                   SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted'
               )
            ORDER BY e.event_date ASC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 4. ADD CALENDAR EVENT
    if ($action === 'add_event') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $title = trim($raw['title'] ?? '');
        $desc = trim($raw['description'] ?? '');
        $date = $raw['event_date'] ?? '';

        if (!$title || !$date) {
            echo json_encode(['success' => false, 'error' => 'Title and date are required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, description, event_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $desc, $date]);

        // Fetch friends using positional parameters
        $stmtFriends = $pdo->prepare("
            SELECT friend_id AS fid FROM friendships WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id AS fid FROM friendships WHERE friend_id = ? AND status = 'accepted'
        ");
        $stmtFriends->execute([$userId, $userId]);
        $friends = $stmtFriends->fetchAll();

        $stmtUser = $pdo->prepare("SELECT display_name FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $senderName = $stmtUser->fetchColumn();

        foreach ($friends as $f) {
            if (!empty($f['fid'])) {
                generateSystemNotification($pdo, $f['fid'], "{$senderName} added an activity event: {$title}");
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // 5. DIRECT MESSAGES
    if ($action === 'get_friends_list') {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar 
            FROM users 
            WHERE id IN (
                SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'
                UNION
                SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted'
            )
        ");
        $stmt->execute([$userId, $userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'get_messages') {
        $friendId = $_GET['friend_id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'send_message') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $friendId = $raw['friend_id'] ?? 0;
        $msgText = trim($raw['message'] ?? '');

        if (!$friendId || !$msgText) {
            echo json_encode(['success' => false]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $friendId, $msgText]);

        $stmtUser = $pdo->prepare("SELECT display_name FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $senderName = $stmtUser->fetchColumn();
        generateSystemNotification($pdo, $friendId, "Received a new chat from {$senderName}.");

        echo json_encode(['success' => true]);
        exit;
    }

    // 6. FRIEND EXPLORER
    if ($action === 'get_all_users') {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar,
                (SELECT status FROM friendships WHERE (user_id = ? AND friend_id = users.id) OR (user_id = users.id AND friend_id = ?) LIMIT 1) AS friendship_status
            FROM users 
            WHERE id != ?
        ");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'toggle_friendship') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $targetId = $raw['target_id'] ?? 0;

        if (!$targetId) {
            echo json_encode(['success' => false]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$userId, $targetId, $targetId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmtDel = $pdo->prepare("DELETE FROM friendships WHERE id = ?");
            $stmtDel->execute([$existing['id']]);
            echo json_encode(['success' => true, 'status' => null]);
        } else {
            $stmtAdd = $pdo->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
            $stmtAdd->execute([$userId, $targetId]);

            $stmtUser = $pdo->prepare("SELECT display_name FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $senderName = $stmtUser->fetchColumn();
            generateSystemNotification($pdo, $targetId, "You and {$senderName} are now connected friends.");

            echo json_encode(['success' => true, 'status' => 'accepted']);
        }
        exit;
    }

    // 7. PROFILE AND SETTINGS
    if ($action === 'update_profile') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $displayName = trim($raw['display_name'] ?? '');
        $avatar = $raw['avatar'] ?? null;
        $password = $raw['password'] ?? '';

        if (!$displayName) {
            echo json_encode(['success' => false]);
            exit;
        }

        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($avatar) {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ?, avatar = ?, password = ? WHERE id = ?");
                $stmt->execute([$displayName, $avatar, $hash, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ?, password = ? WHERE id = ?");
                $stmt->execute([$displayName, $hash, $userId]);
            }
        } else {
            if ($avatar) {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$displayName, $avatar, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
                $stmt->execute([$displayName, $userId]);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update_settings') {
        $raw = json_decode(file_get_contents('php://input'), true);
        $mode = $raw['theme_mode'] ?? 'light';
        $color = $raw['theme_color'] ?? 'blue';

        $stmt = $pdo->prepare("UPDATE users SET theme_mode = ?, theme_color = ? WHERE id = ?");
        $stmt->execute([$mode, $color, $userId]);

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_notifications') {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'mark_notifications_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}