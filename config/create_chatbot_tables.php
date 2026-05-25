<?php
/**
 * Author: Sanskar Shah
 * Group: L5CG6
 */
require_once __DIR__ . '/db.php';

$sqls = [
    // Conversations table
    "CREATE TABLE IF NOT EXISTS ai_conversations (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED NOT NULL,
        title       VARCHAR(255) NOT NULL DEFAULT 'New Chat',
        language    VARCHAR(20)  NOT NULL DEFAULT 'english',
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Messages table
    "CREATE TABLE IF NOT EXISTS ai_messages (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT UNSIGNED NOT NULL,
        user_id         INT UNSIGNED NOT NULL,
        role            ENUM('user','assistant','system') NOT NULL,
        content         LONGTEXT NOT NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation_id (conversation_id),
        INDEX idx_user_id (user_id),
        CONSTRAINT fk_ai_msg_conv FOREIGN KEY (conversation_id)
            REFERENCES ai_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$ok = true;
foreach ($sqls as $sql) {
    if (!$conn->query($sql)) {
        echo "<p style='color:red'>Error: " . htmlspecialchars($conn->error) . "</p>";
        $ok = false;
    }
}

if ($ok) {
    echo "<p style='color:green;font-family:monospace'>✅ AI chatbot tables created successfully!</p>";
    echo "<p style='font-family:monospace'>Tables: <strong>ai_conversations</strong>, <strong>ai_messages</strong></p>";
}
?>
