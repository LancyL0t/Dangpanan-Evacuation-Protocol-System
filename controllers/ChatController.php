<?php
// controllers/ChatController.php

require_once 'models/ChatModel.php';

class ChatController {
    private $db;
    private $chatModel;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->chatModel = new ChatModel($db_conn);
    }

    /**
     * Show the chat page for a specific request
     */
    public function showChatPage() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?route=login");
            exit();
        }

        $request_id = $_GET['request_id'] ?? null;
        $user_id = $_SESSION['user_id'];

        if (!$request_id) {
            header("Location: index.php?route=messages");
            exit();
        }

        // Verify access
        if (!$this->chatModel->canAccessChat($request_id, $user_id)) {
            header("Location: index.php?route=home&error=unauthorized");
            exit();
        }

        // Get chat context
        $chatContext = $this->chatModel->getChatContext($request_id);

        if (!$chatContext) {
            header("Location: index.php?route=messages");
            exit();
        }

        // Determine if current user is host or evacuee
        $isHost = ($user_id == $chatContext['host_id']);
        $otherName = $isHost 
            ? $chatContext['evacuee_first'] . ' ' . $chatContext['evacuee_last']
            : $chatContext['host_first'] . ' ' . $chatContext['host_last'];
        $otherRole = $isHost ? 'Evacuee' : 'Shelter Host';

        // Mark messages as read
        $this->chatModel->markAsRead($request_id, $user_id);

        // Load initial messages
        $messages = $this->chatModel->getMessages($request_id);

        require 'views/shelter/chat.php';
    }

    /**
     * Show messages list page (all conversations)
     */
    public function showMessagesPage() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?route=login");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $conversations = $this->chatModel->getUserConversations($user_id);

        require 'views/shelter/messages.php';
    }

    /**
     * API: Get messages (for AJAX polling)
     */
    public function getMessages() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $request_id = $_GET['request_id'] ?? null;
        $after_id = $_GET['after_id'] ?? 0;
        $user_id = $_SESSION['user_id'];

        if (!$request_id) {
            echo json_encode(['success' => false, 'message' => 'Request ID required']);
            exit();
        }

        if (!$this->chatModel->canAccessChat($request_id, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        // Mark as read
        $this->chatModel->markAsRead($request_id, $user_id);

        $messages = $this->chatModel->getMessages($request_id, $after_id);

        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'user_id' => $user_id
        ]);
        exit();
    }

    /**
     * API: Send a message
     */
    public function sendMessage() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $request_id = $data['request_id'] ?? null;
        $message = trim($data['message'] ?? '');
        $user_id = $_SESSION['user_id'];

        if (!$request_id || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Request ID and message required']);
            exit();
        }

        if (!$this->chatModel->canAccessChat($request_id, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $msg_id = $this->chatModel->sendMessage($request_id, $user_id, $message);

        if ($msg_id) {
            echo json_encode([
                'success' => true,
                'message_id' => $msg_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
        exit();
    }

    /**
     * API: Get total unread count for nav badge
     */
    public function getUnreadCount() {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'count' => 0]);
            exit();
        }

        $count = $this->chatModel->getTotalUnreadCount($_SESSION['user_id']);

        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        exit();
    }
}
