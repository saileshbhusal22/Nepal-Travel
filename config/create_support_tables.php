<?php
require_once __DIR__ . '/db.php';

$sql1 = "CREATE TABLE IF NOT EXISTS support_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  guest_name VARCHAR(100) DEFAULT 'Guest',
  guest_email VARCHAR(150) DEFAULT NULL,
  status ENUM('open','closed') DEFAULT 'open',
  last_message TEXT DEFAULT NULL,
  last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  unread_admin INT DEFAULT 0,
  unread_user INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sql2 = "CREATE TABLE IF NOT EXISTS support_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  sender ENUM('user','admin') NOT NULL,
  message TEXT NOT NULL,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_read TINYINT(1) DEFAULT 0,
  FOREIGN KEY (session_id) REFERENCES support_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql1)) {
    echo "✅ support_sessions table created/exists OK\n";
} else {
    echo "❌ Error creating support_sessions: " . $conn->error . "\n";
}

if ($conn->query($sql2)) {
    echo "✅ support_messages table created/exists OK\n";
} else {
    echo "❌ Error creating support_messages: " . $conn->error . "\n";
}

$conn->close();
echo "\nDone!";
?>
