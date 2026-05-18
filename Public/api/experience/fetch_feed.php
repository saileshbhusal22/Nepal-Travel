<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$post_destination = isset($_GET['destination']) && !empty(trim($_GET['destination'])) ? trim($_GET['destination']) : null;
$my_posts = isset($_GET['my_posts']) && $_GET['my_posts'] == 1 && $current_user_id > 0;
$search = isset($_GET['search']) && !empty(trim($_GET['search'])) ? trim($_GET['search']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$category = isset($_GET['category']) && $_GET['category'] !== 'all' ? trim($_GET['category']) : null;
$profile_id = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : null;

// Fetch posts
$sql = "
    SELECT 
        p.id, p.caption, p.image_path, p.created_at, p.user_id, p.destination, p.location, p.tags,
        COALESCE(NULLIF(u.full_name, ''), u.username) as username,
        NULLIF(u.profile_image, 'default.png') as profile_image,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
        EXISTS(SELECT 1 FROM likes hl WHERE hl.post_id = p.id AND hl.user_id = ?) as has_liked,
        EXISTS(SELECT 1 FROM saves hs WHERE hs.post_id = p.id AND hs.user_id = ?) as has_saved
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE 1=1
";

if ($post_destination) {
    $sql .= " AND p.destination = ? ";
}
if ($my_posts) {
    $sql .= " AND p.user_id = ? ";
}
if ($profile_id) {
    $sql .= " AND p.user_id = ? ";
}
if ($search) {
    $sql .= " AND (p.caption LIKE ? OR p.location LIKE ? OR p.destination LIKE ? OR u.username LIKE ?) ";
}
if ($category) {
    $sql .= " AND p.tags = ? ";
}

// Sorting logic
if ($sort === 'trending') {
    $sql .= " ORDER BY like_count DESC, p.created_at DESC ";
} elseif ($sort === 'most_discussed') {
    $sql .= " ORDER BY comment_count DESC, p.created_at DESC ";
} else {
    $sql .= " ORDER BY p.created_at DESC ";
}

$sql .= " LIMIT ? OFFSET ? ";

$stmt = $conn->prepare($sql);

$types = "ii";
$queryParams = [$current_user_id, $current_user_id];

if ($post_destination) {
    $types .= "s";
    $queryParams[] = $post_destination;
}
if ($my_posts) {
    $types .= "i";
    $queryParams[] = $current_user_id;
}
if ($profile_id) {
    $types .= "i";
    $queryParams[] = $profile_id;
}
if ($search) {
    $types .= "ssss";
    $searchWildcard = "%" . $search . "%";
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
}
if ($category) {
    $types .= "s";
    $queryParams[] = $category;
}

$types .= "ii";
$queryParams[] = $limit;
$queryParams[] = $offset;

$stmt->bind_param($types, ...$queryParams);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

echo json_encode(['success' => true, 'posts' => $posts]);

$stmt->close();
$conn->close();
?>
<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$post_destination = isset($_GET['destination']) && !empty(trim($_GET['destination'])) ? trim($_GET['destination']) : null;
$my_posts = isset($_GET['my_posts']) && $_GET['my_posts'] == 1 && $current_user_id > 0;
$search = isset($_GET['search']) && !empty(trim($_GET['search'])) ? trim($_GET['search']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$category = isset($_GET['category']) && $_GET['category'] !== 'all' ? trim($_GET['category']) : null;
$profile_id = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : null;

// Fetch posts
$sql = "
    SELECT 
        p.id, p.caption, p.image_path, p.created_at, p.user_id, p.destination, p.location, p.tags,
        COALESCE(NULLIF(u.full_name, ''), u.username) as username,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
        EXISTS(SELECT 1 FROM likes hl WHERE hl.post_id = p.id AND hl.user_id = ?) as has_liked,
        EXISTS(SELECT 1 FROM saves hs WHERE hs.post_id = p.id AND hs.user_id = ?) as has_saved
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE 1=1
";

if ($post_destination) {
    $sql .= " AND p.destination = ? ";
}
if ($my_posts) {
    $sql .= " AND p.user_id = ? ";
}
if ($profile_id) {
    $sql .= " AND p.user_id = ? ";
}
if ($search) {
    $sql .= " AND (p.caption LIKE ? OR p.location LIKE ? OR p.destination LIKE ? OR u.username LIKE ?) ";
}
if ($category) {
    $sql .= " AND p.tags = ? ";
}

// Sorting logic
if ($sort === 'trending') {
    $sql .= " ORDER BY like_count DESC, p.created_at DESC ";
} elseif ($sort === 'most_discussed') {
    $sql .= " ORDER BY comment_count DESC, p.created_at DESC ";
} else {
    $sql .= " ORDER BY p.created_at DESC ";
}

$sql .= " LIMIT ? OFFSET ? ";

$stmt = $conn->prepare($sql);

$types = "ii";
$queryParams = [$current_user_id, $current_user_id];

if ($post_destination) {
    $types .= "s";
    $queryParams[] = $post_destination;
}
if ($my_posts) {
    $types .= "i";
    $queryParams[] = $current_user_id;
}
if ($profile_id) {
    $types .= "i";
    $queryParams[] = $profile_id;
}
if ($search) {
    $types .= "ssss";
    $searchWildcard = "%" . $search . "%";
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
    $queryParams[] = $searchWildcard;
}
if ($category) {
    $types .= "s";
    $queryParams[] = $category;
}

$types .= "ii";
$queryParams[] = $limit;
$queryParams[] = $offset;

$stmt->bind_param($types, ...$queryParams);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

echo json_encode(['success' => true, 'posts' => $posts]);

$stmt->close();
$conn->close();
?>
