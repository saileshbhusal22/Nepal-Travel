<?php
/**
 * Author: Sanskar Shah
 * Group: L5CG6
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once '../config/db.php';
$userId = (int)$_SESSION['user_id'];

// Get user query from request
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$query = trim(strtolower($input['query'] ?? ''));

if (empty($query)) {
    echo json_encode(['success' => true, 'recommendations' => []]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// INTENT DETECTION: Classify query type
// ═══════════════════════════════════════════════════════════════════════════
$intents = [
    'budget'    => ['budget', 'cheap', 'affordable', 'under', 'price', 'cost', 'rupees', 'npr'],
    'trek'      => ['trek', 'hike', 'hiking', 'mountain', 'trail', 'climbing', 'altitude'],
    'package'   => ['package', 'tour', 'itinerary', 'days', 'guided', 'arranged', 'all-inclusive'],
    'event'     => ['event', 'festival', 'celebration', 'happening', 'concert', 'fair', 'carnival'],
    'nature'    => ['nature', 'natural', 'wildlife', 'national park', 'lake', 'river', 'forest'],
    'culture'   => ['culture', 'cultural', 'temple', 'historical', 'heritage', 'artisan', 'traditional'],
    'food'      => ['food', 'momo', 'cuisine', 'dining', 'restaurant', 'culinary', 'taste'],
    'adventure' => ['adventure', 'extreme', 'adrenaline', 'sport', 'paragliding', 'bungee', 'rafting'],
    'family'    => ['family', 'kids', 'children', 'group', 'honeymoon', 'couple', 'romantic'],
];

$detected_intents = [];
foreach ($intents as $intent_type => $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($query, $keyword) !== false) {
            $detected_intents[$intent_type] = true;
            break;
        }
    }
}

// Extract location keywords
$locations = ['kathmandu', 'pokhara', 'chitwan', 'everest', 'annapurna', 'bhaktapur', 'thamel', 'valleys', 'patan', 'dharan'];
$detected_locations = [];
foreach ($locations as $loc) {
    if (strpos($query, $loc) !== false) {
        $detected_locations[] = $loc;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// BUILD SEARCH QUERIES
// ═══════════════════════════════════════════════════════════════════════════
$recommendations = [];

// 1. ADMIN DEALS (from deals table)
if (!empty($detected_intents) || !empty($detected_locations)) {
    $where_clauses = [];
    
    // Match by intent-inferred category
    if (isset($detected_intents['trek'])) {
        $where_clauses[] = "category LIKE '%trek%' OR category LIKE '%hike%'";
    }
    if (isset($detected_intents['culture'])) {
        $where_clauses[] = "category LIKE '%culture%' OR category LIKE '%heritage%'";
    }
    if (isset($detected_intents['nature'])) {
        $where_clauses[] = "category LIKE '%nature%' OR category LIKE '%wildlife%'";
    }
    if (isset($detected_intents['adventure'])) {
        $where_clauses[] = "category LIKE '%adventure%' OR category LIKE '%sport%'";
    }
    if (isset($detected_intents['family'])) {
        $where_clauses[] = "category LIKE '%family%' OR category LIKE '%group%'";
    }
    if (isset($detected_intents['food'])) {
        $where_clauses[] = "category LIKE '%food%' OR category LIKE '%culinary%'";
    }
    
    // Match by location
    if (!empty($detected_locations)) {
        $loc_filter = "(" . implode(" OR ", array_map(function($loc) {
            return "location LIKE '%$loc%'";
        }, array_map(function($l) use ($conn) { return $conn->real_escape_string($l); }, $detected_locations))) . ")";
        $where_clauses[] = $loc_filter;
    }
    
    // Match by price range keywords
    if (isset($detected_intents['budget'])) {
        preg_match('/\d+/', $query, $matches);
        if (!empty($matches)) {
            $max_price = (int)$matches[0] * 1.5; // 50% buffer
            $where_clauses[] = "price <= $max_price";
        }
    }
    
    // Match by query keywords in title/description
    $keywords_escaped = $conn->real_escape_string(implode(' ', array_slice(preg_split('/\s+/', $query), 0, 3)));
    $where_clauses[] = "(title LIKE '%$keywords_escaped%' OR description LIKE '%$keywords_escaped%')";
    
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" OR ", $where_clauses) : "";
    
    $deals_query = "
        SELECT 
            d.id, 
            'deal' AS type,
            d.title, 
            SUBSTRING(d.description, 1, 120) AS description,
            d.price, 
            d.category,
            d.location,
            d.image_url,
            d.rating,
            d.reviews_count,
            COALESCE(dr.avg_rating, d.rating) AS avg_rating,
            COALESCE(dr.reviews_count, d.reviews_count) AS review_count,
            'deal-details.php?id=' AS detail_link
        FROM deals d
        LEFT JOIN (
            SELECT deal_id,
                   ROUND(AVG(rating), 1) AS avg_rating,
                   COUNT(*) AS reviews_count
            FROM deal_reviews
            GROUP BY deal_id
        ) dr ON dr.deal_id = d.id
        $where_sql
        ORDER BY COALESCE(dr.avg_rating, d.rating) DESC, COALESCE(dr.reviews_count, d.reviews_count) DESC
        LIMIT 4
    ";
    
    if ($result = $conn->query($deals_query)) {
        while ($row = $result->fetch_assoc()) {
            $row['detail_link'] = '/Nepal-Travel/Public/deal-details.php?id=' . (int)$row['id'];
            $recommendations[] = $row;
        }
    }
}

// 2. USER-SUBMITTED DEALS (approved and visible)
$user_deals_query = "
    SELECT 
        ud.id,
        'user_deal' AS type,
        ud.title,
        SUBSTRING(ud.description, 1, 120) AS description,
        ud.price,
        ud.category,
        ud.location,
        ud.image_url,
        COALESCE(udr.avg_rating, 0) AS avg_rating,
        COALESCE(udr.reviews_count, 0) AS review_count,
        'Ud_deal_details.php?id=' AS detail_link
    FROM user_deals ud
    LEFT JOIN (
        SELECT ud_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*) AS reviews_count
        FROM user_deal_reviews
        GROUP BY ud_id
    ) udr ON udr.ud_id = ud.id
    WHERE ud.status = 'approved'
      AND ud.visible_from <= NOW()
      AND ud.visible_until > NOW()
      AND (
        ud.title LIKE '%$keywords_escaped%'
        OR ud.description LIKE '%$keywords_escaped%'
        OR ud.category LIKE '%$keywords_escaped%'
      )
    ORDER BY COALESCE(udr.avg_rating, 0) DESC, COALESCE(udr.reviews_count, 0) DESC
    LIMIT 3
";

if ($result = $conn->query($user_deals_query)) {
    while ($row = $result->fetch_assoc()) {
        $row['detail_link'] = '/Nepal-Travel/Public/Ud_deal_details.php?id=' . (int)$row['id'];
        $recommendations[] = $row;
    }
}

// 3. EVENTS (hardcoded but we can enhance with keyword matching)
// For now, return related hardcoded events based on intent
$all_events = [
    ['id' => 1, 'type' => 'event', 'title' => 'Buddha Jayanti Celebration', 'description' => 'Join the grand spiritual celebration at the birthplace of Lord Buddha.', 'date' => '12 MAY 2026', 'category' => 'Festival', 'detail_link' => '/Nepal-Travel/Public/events.php#buddha-jayanti'],
    ['id' => 2, 'type' => 'event', 'title' => 'Phewa Lake Regatta', 'description' => 'An exciting week of watersports and competitive boating over the pristine lake.', 'date' => '14 - 18 MAY 2026', 'category' => 'Sports', 'detail_link' => '/Nepal-Travel/Public/events.php#phewa-regatta'],
    ['id' => 3, 'type' => 'event', 'title' => 'Annapurna Spring Marathon', 'description' => 'High-altitude endurance race trails crossing massive suspension bridges.', 'date' => '25 MAY 2026', 'category' => 'Nature', 'detail_link' => '/Nepal-Travel/Public/events.php#annapurna-marathon'],
    ['id' => 4, 'type' => 'event', 'title' => 'Kathmandu Momo Fiesta', 'description' => 'Taste over 50 varieties of traditional dumplings across Thamel.', 'date' => '28 - 30 MAY 2026', 'category' => 'Food', 'detail_link' => '/Nepal-Travel/Public/events.php#momo-fiesta'],
];

if ((isset($detected_intents['event']) || isset($detected_intents['food']) || isset($detected_intents['adventure'])) && count($recommendations) < 8) {
    foreach ($all_events as $event) {
        if (count($recommendations) >= 8) break;
        
        // Match events by intent
        $event_matched = false;
        if (isset($detected_intents['event']) && stripos($event['category'], 'Festival') !== false) $event_matched = true;
        if (isset($detected_intents['food']) && stripos($event['category'], 'Food') !== false) $event_matched = true;
        if (isset($detected_intents['adventure']) && stripos($event['category'], 'Sports') !== false) $event_matched = true;
        if (isset($detected_intents['adventure']) && stripos($event['category'], 'Nature') !== false) $event_matched = true;
        
        if ($event_matched) {
            $recommendations[] = $event;
        }
    }
}

// Remove duplicates and limit to 6 recommendations
$seen_ids = [];
$deduped = [];
foreach ($recommendations as $rec) {
    $key = $rec['type'] . '_' . $rec['id'];
    if (!isset($seen_ids[$key])) {
        $seen_ids[$key] = true;
        $deduped[] = $rec;
        if (count($deduped) >= 6) break;
    }
}

echo json_encode([
    'success' => true,
    'recommendations' => $deduped,
    'detected_intents' => array_keys($detected_intents),
    'query_length' => strlen($query)
]);
?>
