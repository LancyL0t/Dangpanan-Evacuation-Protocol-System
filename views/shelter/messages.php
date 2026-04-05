<?php 
require_once 'config/auth_guard.php'; 
protect_page(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Messages</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="light-portal chat-theme">
<?php require __DIR__ . '/../partials/nav_portal.php'; ?>

<main class="messages-container">
    <header class="messages-header">
        <div class="messages-header-left">
            <h1 class="messages-title">
                <i data-lucide="message-circle"></i> Messages
            </h1>
            <p class="messages-subtitle">Your conversations with hosts and evacuees</p>
        </div>
    </header>

    <div class="conversations-list">
        <?php if (empty($conversations)): ?>
            <div class="messages-empty-state">
                <div class="empty-icon-wrap">
                    <i data-lucide="message-square-off"></i>
                </div>
                <h3>No Conversations Yet</h3>
                <p>Conversations will appear here once you have an approved shelter request. Chat with your host for arrival details, or with evacuees as a host.</p>
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $convo): 
                $userId = $_SESSION['user_id'];
                $isHost = ($userId == $convo['host_id']);
                $otherName = $isHost 
                    ? $convo['evacuee_first'] . ' ' . $convo['evacuee_last']
                    : $convo['host_first'] . ' ' . $convo['host_last'];
                $otherRole = $isHost ? 'Evacuee' : 'Host';
                $initial = strtoupper(substr($otherName, 0, 1));
                $unread = (int)$convo['unread_count'];
                $lastMsg = $convo['last_message'] ?? 'No messages yet';
                $lastTime = $convo['last_message_at'] 
                    ? date('M d, g:i A', strtotime($convo['last_message_at']))
                    : '';
            ?>
                <a href="index.php?route=chat&request_id=<?php echo $convo['request_id']; ?>" 
                   class="conversation-card <?php echo $unread > 0 ? 'has-unread' : ''; ?>">
                    <div class="convo-avatar">
                        <?php echo $initial; ?>
                        <?php if ($unread > 0): ?>
                            <span class="convo-unread-dot"></span>
                        <?php endif; ?>
                    </div>
                    <div class="convo-content">
                        <div class="convo-top-row">
                            <h4 class="convo-name"><?php echo htmlspecialchars($otherName); ?></h4>
                            <span class="convo-time"><?php echo $lastTime; ?></span>
                        </div>
                        <div class="convo-bottom-row">
                            <p class="convo-last-message"><?php echo htmlspecialchars(mb_strimwidth($lastMsg, 0, 60, '...')); ?></p>
                            <?php if ($unread > 0): ?>
                                <span class="convo-unread-badge"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="convo-meta">
                            <span class="convo-role-badge <?php echo $isHost ? 'evacuee-badge' : 'host-badge'; ?>"><?php echo $otherRole; ?></span>
                            <span class="convo-shelter"><?php echo htmlspecialchars($convo['shelter_name']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script src="assets/js/nav.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
