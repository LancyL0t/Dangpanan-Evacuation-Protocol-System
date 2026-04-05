/**
 * DANGPANAN Chat System - Client-Side Logic
 * Handles message sending, polling, auto-scroll, and textarea auto-resize
 */

(function() {
    'use strict';

    // --- DOM References ---
    const chatMessages  = document.getElementById('chatMessages');
    const chatInput     = document.getElementById('chatInput');
    const sendBtn       = document.getElementById('sendBtn');
    const chatForm      = document.getElementById('chatForm');
    const requestId     = document.getElementById('requestId')?.value;
    const currentUserId = document.getElementById('currentUserId')?.value;

    let lastMessageId = 0;
    let pollingInterval = null;
    const POLL_MS = 3000;

    // --- Initialize ---
    function init() {
        if (!requestId || !currentUserId) return;

        // Find the highest message ID on page load
        const allBubbles = document.querySelectorAll('.chat-bubble[data-msg-id]');
        allBubbles.forEach(b => {
            const id = parseInt(b.dataset.msgId, 10);
            if (id > lastMessageId) lastMessageId = id;
        });

        // Create Lucide icons
        if (window.lucide) lucide.createIcons();

        // Setup textarea auto-resize
        setupTextarea();

        // Scroll to bottom
        scrollToBottom(false);

        // Start polling
        startPolling();

        // Pause polling when tab hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopPolling();
            } else {
                pollMessages();
                startPolling();
            }
        });
    }

    // --- Textarea Setup ---
    function setupTextarea() {
        if (!chatInput) return;

        chatInput.addEventListener('input', () => {
            // Auto-resize
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';

            // Enable/disable send button
            sendBtn.disabled = chatInput.value.trim().length === 0;
        });

        // Send on Enter (Shift+Enter for newline)
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (chatInput.value.trim()) {
                    handleSendMessage(e);
                }
            }
        });
    }

    // --- Send Message ---
    window.handleSendMessage = function(e) {
        if (e && e.preventDefault) e.preventDefault();

        const message = chatInput.value.trim();
        if (!message) return false;

        // Disable input while sending
        chatInput.disabled = true;
        sendBtn.disabled = true;

        // Optimistic append
        appendMessage({
            id: 'temp-' + Date.now(),
            sender_id: currentUserId,
            message: message,
            first_name: 'You',
            last_name: '',
            is_read: 0,
            created_at: new Date().toISOString()
        }, true);

        // Clear input
        chatInput.value = '';
        chatInput.style.height = 'auto';

        // POST to server
        fetch('index.php?route=chat_send_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: requestId,
                message: message
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update lastMessageId
                if (data.message_id > lastMessageId) {
                    lastMessageId = data.message_id;
                }
            } else {
                showToast('Failed to send message', 'error');
            }
        })
        .catch(err => {
            console.error('Send error:', err);
            showToast('Network error. Message may not have sent.', 'error');
        })
        .finally(() => {
            chatInput.disabled = false;
            chatInput.focus();
        });

        return false;
    };

    // --- Append Message to DOM ---
    function appendMessage(msg, isOptimistic) {
        // Remove empty state if present
        const emptyState = document.getElementById('chatEmptyState');
        if (emptyState) emptyState.remove();

        // Check if date divider needed
        const msgDate = formatDate(msg.created_at);
        const lastDivider = chatMessages.querySelector('.chat-date-divider:last-of-type span');
        if (!lastDivider || lastDivider.textContent !== msgDate) {
            const divider = document.createElement('div');
            divider.className = 'chat-date-divider';
            divider.innerHTML = `<span>${msgDate}</span>`;
            chatMessages.appendChild(divider);
        }

        const isSent = String(msg.sender_id) === String(currentUserId);
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${isSent ? 'sent' : 'received'}`;
        if (msg.id) bubble.dataset.msgId = msg.id;

        const timeStr = formatTime(msg.created_at);
        const readIcon = isSent 
            ? `<i data-lucide="${msg.is_read ? 'check-check' : 'check'}" class="read-indicator ${msg.is_read ? 'read' : ''}"></i>`
            : '';

        bubble.innerHTML = `
            <div class="bubble-content">
                ${!isSent ? `<span class="bubble-sender">${escapeHtml(msg.first_name)}</span>` : ''}
                <p class="bubble-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</p>
                <span class="bubble-time">
                    ${timeStr}
                    ${readIcon}
                </span>
            </div>
        `;

        chatMessages.appendChild(bubble);
        if (window.lucide) lucide.createIcons();
        scrollToBottom(true);
    }

    // --- Poll for New Messages ---
    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(pollMessages, POLL_MS);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    function pollMessages() {
        fetch(`index.php?route=chat_get_messages&request_id=${requestId}&after_id=${lastMessageId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    // Remove temp messages
                    document.querySelectorAll('.chat-bubble[data-msg-id^="temp-"]').forEach(el => el.remove());

                    data.messages.forEach(msg => {
                        const msgId = parseInt(msg.id, 10);
                        // Only append if not already in DOM
                        if (!document.querySelector(`.chat-bubble[data-msg-id="${msgId}"]`)) {
                            appendMessage(msg, false);
                        }
                        if (msgId > lastMessageId) lastMessageId = msgId;
                    });
                }
            })
            .catch(err => console.error('Poll error:', err));
    }

    // --- Scroll ---
    function scrollToBottom(smooth) {
        if (!chatMessages) return;
        if (smooth) {
            chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
        } else {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    // --- Helpers ---
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${months[d.getMonth()]} ${String(d.getDate()).padStart(2, '0')}, ${d.getFullYear()}`;
    }

    function formatTime(dateStr) {
        const d = new Date(dateStr);
        let h = d.getHours();
        const m = String(d.getMinutes()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${ampm}`;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
            background: ${type === 'error' ? '#ef4444' : '#22c55e'}; color: #fff;
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            z-index: 9999; animation: bubbleIn 0.3s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // --- Info Panel Toggle ---
    window.toggleChatInfo = function() {
        const panel = document.getElementById('chatInfoPanel');
        if (panel) {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
    };

    // --- Boot ---
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
