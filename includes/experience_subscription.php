<?php
/**
 * Experience post quota: 5 free posts, then active subscription required.
 */

if (!defined('EXPERIENCE_FREE_POST_LIMIT')) {
    define('EXPERIENCE_FREE_POST_LIMIT', 5);
}

function experience_has_active_subscription(mysqli $conn, int $user_id): bool {
    $stmt = $conn->prepare(
        "SELECT id FROM user_experience_subscriptions
         WHERE user_id = ? AND status = 'active'
           AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$row;
}

function experience_get_post_count(mysqli $conn, int $user_id): int {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count;
}

function experience_get_post_quota(mysqli $conn, int $user_id): array {
    $posts_used = experience_get_post_count($conn, $user_id);
    $free_limit = EXPERIENCE_FREE_POST_LIMIT;
    $free_remaining = max(0, $free_limit - $posts_used);

    $stmt = $conn->prepare(
        "SELECT id, expires_at FROM user_experience_subscriptions
         WHERE user_id = ? AND status = 'active'
           AND (expires_at IS NULL OR expires_at > NOW())
         ORDER BY expires_at DESC LIMIT 1"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $sub = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $has_active = (bool)$sub;
    $requires_subscription = $posts_used >= $free_limit && !$has_active;
    $can_post = !$requires_subscription;

    return [
        'posts_used' => $posts_used,
        'free_limit' => $free_limit,
        'free_remaining' => $free_remaining,
        'has_active_subscription' => $has_active,
        'subscription_expires_at' => $sub['expires_at'] ?? null,
        'can_post' => $can_post,
        'requires_subscription' => $requires_subscription,
    ];
}

function experience_can_create_post(mysqli $conn, int $user_id): bool {
    $quota = experience_get_post_quota($conn, $user_id);
    return $quota['can_post'];
}
