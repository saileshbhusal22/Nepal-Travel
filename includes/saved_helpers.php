<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
function ensureSavedDealsTable(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS saved_deals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        deal_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_deal (user_id, deal_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getSavedDealIds(mysqli $conn): array
{
    $ids = array_map('intval', $_SESSION['saved_deals'] ?? []);

    if (isset($_SESSION['user_id'])) {
        ensureSavedDealsTable($conn);
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare('SELECT deal_id FROM saved_deals WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['deal_id'];
        }
        $stmt->close();
    }

    $ids = array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    if (empty($ids)) {
        $_SESSION['saved_deals'] = [];
        return [];
    }

    // Keep only deals that still exist (removes stale session/db ids)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT id FROM deals WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $validIds = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $validIds[] = (int)$row['id'];
    }
    $stmt->close();

    pruneInvalidSavedDeals($conn, $ids, $validIds);
    $_SESSION['saved_deals'] = $validIds;

    return $validIds;
}

function pruneInvalidSavedDeals(mysqli $conn, array $allIds, array $validIds): void
{
    $invalidIds = array_diff($allIds, $validIds);
    if (empty($invalidIds)) {
        return;
    }

    if (isset($_SESSION['user_id'])) {
        ensureSavedDealsTable($conn);
        $uid = (int)$_SESSION['user_id'];
        foreach ($invalidIds as $dealId) {
            $stmt = $conn->prepare('DELETE FROM saved_deals WHERE user_id = ? AND deal_id = ?');
            $dealId = (int)$dealId;
            $stmt->bind_param('ii', $uid, $dealId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function saveDealId(mysqli $conn, int $dealId): bool
{
    if ($dealId <= 0) {
        return false;
    }

    if (!isset($_SESSION['saved_deals'])) {
        $_SESSION['saved_deals'] = [];
    }

    if (!in_array($dealId, $_SESSION['saved_deals'], true)) {
        $_SESSION['saved_deals'][] = $dealId;
    }

    if (isset($_SESSION['user_id'])) {
        ensureSavedDealsTable($conn);
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare('INSERT IGNORE INTO saved_deals (user_id, deal_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $uid, $dealId);
        $stmt->execute();
        $stmt->close();
    }

    return true;
}

function removeDealId(mysqli $conn, int $dealId): void
{
    $_SESSION['saved_deals'] = array_values(array_diff(
        array_map('intval', $_SESSION['saved_deals'] ?? []),
        [$dealId]
    ));

    if (isset($_SESSION['user_id'])) {
        ensureSavedDealsTable($conn);
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare('DELETE FROM saved_deals WHERE user_id = ? AND deal_id = ?');
        $stmt->bind_param('ii', $uid, $dealId);
        $stmt->execute();
        $stmt->close();
    }
}

function isDealSaved(int $dealId): bool
{
    return in_array($dealId, array_map('intval', $_SESSION['saved_deals'] ?? []), true);
}

function getSavedDeals(mysqli $conn): array
{
    $ids = getSavedDealIds($conn);
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT * FROM deals WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
    $bindIds = array_merge($ids, $ids);
    $stmt->bind_param($types . $types, ...$bindIds);
    $stmt->execute();
    $deals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $deals;
}

function getSavedExperiencePosts(mysqli $conn, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.caption,
            p.image_path,
            p.created_at,
            p.destination,
            p.location,
            p.tags,
            COALESCE(NULLIF(u.full_name, ''), u.username) AS username,
            s.id AS save_id
        FROM saves s
        INNER JOIN posts p ON p.id = s.post_id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE s.user_id = ?
        ORDER BY s.id DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $posts;
}

function pruneInvalidSavedExperiences(mysqli $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = $conn->prepare('
        DELETE s FROM saves s
        LEFT JOIN posts p ON p.id = s.post_id
        WHERE s.user_id = ? AND p.id IS NULL
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

function getTotalSavedCount(mysqli $conn): int
{
    $dealCount = count(getSavedDeals($conn));

    $experienceCount = 0;
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        pruneInvalidSavedExperiences($conn, $uid);
        $experienceCount = count(getSavedExperiencePosts($conn, $uid));
    }

    return $dealCount + $experienceCount;
}

function formatSavedExperienceImage(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '/Nepal-Travel/images/annapurna_trek.png';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if ($path[0] === '/') {
        return $path;
    }
    if (strpos($path, 'images/') === 0) {
        return '/Nepal-Travel/' . $path;
    }
    return '/Nepal-Travel/images/' . ltrim($path, '/');
}
