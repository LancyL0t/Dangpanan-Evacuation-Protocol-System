<?php 
require_once 'config/auth_guard.php'; 
protect_page(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DANGPANAN | Chat</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="light-portal chat-theme">
<?php require __DIR__ . '/../partials/nav_portal.php'; ?>

<main class="chat-container">
    <!-- Chat Header -->
    <div class="chat-header">
        <a href="<?php echo $isHost ? 'index.php?route=host_portal' : 'index.php?route=evacuee_portal'; ?>" class="chat-back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="chat-header-info">
            <div class="chat-avatar">
                <?php echo strtoupper(substr($otherName, 0, 1)); ?>
            </div>
            <div class="chat-header-text">
                <h2 class="chat-recipient-name"><?php echo htmlspecialchars($otherName); ?></h2>
                <p class="chat-recipient-role">
                    <span class="role-badge <?php echo $isHost ? 'evacuee-badge' : 'host-badge'; ?>">
                        <?php echo $otherRole; ?>
                    </span>
                    <span class="chat-separator">•</span>
                    <span class="chat-shelter-name"><?php echo htmlspecialchars($chatContext['shelter_name']); ?></span>
                </p>
            </div>
        </div>
        <div class="chat-header-actions">
            <button class="chat-info-btn" onclick="toggleChatInfo()" title="Request Info">
                <i data-lucide="info"></i>
            </button>
        </div>
    </div>

    <!-- Request Info Panel (hidden by default) -->
    <div class="chat-info-panel" id="chatInfoPanel" style="display: none;">
        <div class="info-panel-content">
            <div class="info-item">
                <i data-lucide="home"></i>
                <div>
                    <span class="info-label">Shelter</span>
                    <span class="info-value"><?php echo htmlspecialchars($chatContext['shelter_name']); ?></span>
                </div>
            </div>
            <div class="info-item">
                <i data-lucide="users"></i>
                <div>
                    <span class="info-label">Group Size</span>
                    <span class="info-value"><?php echo $chatContext['group_size']; ?> <?php echo $chatContext['group_size'] > 1 ? 'people' : 'person'; ?></span>
                </div>
            </div>
            <div class="info-item">
                <i data-lucide="activity"></i>
                <div>
                    <span class="info-label">Status</span>
                    <span class="info-value status-<?php echo $chatContext['request_status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $chatContext['request_status'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)): ?>
            <div class="chat-empty-state" id="chatEmptyState">
                <div class="empty-chat-icon">
                    <i data-lucide="message-circle"></i>
                </div>
                <h3>Start the Conversation</h3>
                <p>Send a message to coordinate arrival details, special needs, or ask questions.</p>
            </div>
        <?php else: ?>
            <?php 
            $currentDate = '';
            foreach ($messages as $msg): 
                $msgDate = date('M d, Y', strtotime($msg['created_at']));
                if ($msgDate !== $currentDate):
                    $currentDate = $msgDate;
            ?>
                <div class="chat-date-divider">
                    <span><?php echo $msgDate; ?></span>
                </div>
            <?php endif; ?>
                <div class="chat-bubble <?php echo ($msg['sender_id'] == $_SESSION['user_id']) ? 'sent' : 'received'; ?>"
                     data-msg-id="<?php echo $msg['id']; ?>">
                    <div class="bubble-content">
                        <?php if ($msg['sender_id'] != $_SESSION['user_id']): ?>
                            <span class="bubble-sender"><?php echo htmlspecialchars($msg['first_name']); ?></span>
                        <?php endif; ?>
                        <p class="bubble-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <span class="bubble-time">
                            <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                            <?php if ($msg['sender_id'] == $_SESSION['user_id']): ?>
                                <i data-lucide="<?php echo $msg['is_read'] ? 'check-check' : 'check'; ?>" class="read-indicator <?php echo $msg['is_read'] ? 'read' : ''; ?>"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Message Input -->
    <div class="chat-input-area">
        <form class="chat-input-form" id="chatForm" onsubmit="return handleSendMessage(event);">
            <div class="chat-input-wrapper">
                <textarea 
                    id="chatInput" 
                    class="chat-input" 
                    placeholder="Type a message..."
                    rows="1"
                    maxlength="1000"
                    autofocus
                ></textarea>
                <button type="submit" class="chat-send-btn" id="sendBtn" disabled>
                    <i data-lucide="send"></i>
                </button>
            </div>
        </form>
    </div>
</main>

<!-- Hidden data for JS -->
<input type="hidden" id="requestId" value="<?php echo (int)$request_id; ?>">
<input type="hidden" id="currentUserId" value="<?php echo (int)$_SESSION['user_id']; ?>">

<script src="assets/js/nav.js"></script>
<script src="assets/js/chat.js"></script>
</body>
</html>
