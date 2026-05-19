<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/deals-data.php';

$category = isset($_GET['category']) ? strtoupper(trim($_GET['category'])) : '';
$q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

$filtered_deals = [];

foreach ($deals as $deal) {
    $match = true;
    
    if ($category !== '' && $category !== 'ALL') {
        if (strtoupper($deal['category_badge']) !== $category) {
            $match = false;
        }
    }
    
    if ($q !== '' && $match) {
        $title_match = strpos(strtolower($deal['title']), $q) !== false;
        $region_match = strpos(strtolower($deal['region']), $q) !== false;
        if (!$title_match && !$region_match) {
            $match = false;
        }
    }
    
    if ($match) {
        $filtered_deals[] = $deal;
    }
}

ob_start();
if (empty($filtered_deals)) {
    echo '<div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: #666; font-family: Montserrat, sans-serif;">';
    echo '<h3 style="font-size: 24px; font-weight: 800; color: #285da1;">No Deals Found</h3>';
    echo '<p>We couldn\'t find any packages matching your criteria. Try adjusting your search.</p>';
    echo '</div>';
} else {
    foreach($filtered_deals as $deal) {
        ?>
        <a href="deal.php?id=<?php echo htmlspecialchars($deal['id']); ?>" style="display: flex; flex-direction: column; background: white; text-decoration: none; position: relative; border: 1px solid #eee; transition: all 0.3s ease; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
            
            <div style="position: relative;">
                <img src="<?php echo htmlspecialchars($deal['image']); ?>" style="width: 100%; height: 260px; object-fit: cover; display: block;">
                <div style="position: absolute; top: 15px; right: 15px; display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                    <span style="background: <?php echo htmlspecialchars($deal['badge_color']); ?>; color: white; padding: 6px 14px; font-size: 11px; font-weight: 800; letter-spacing: 1px; border-radius: 4px; text-transform: uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.2);"><?php echo htmlspecialchars($deal['category_badge']); ?></span>
                    <span style="background: white; color: #333; padding: 4px 10px; font-size: 10px; font-weight: 800; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($deal['duration']); ?></span>
                </div>
            </div>

            <div style="padding: 25px 20px 20px; flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 12px; letter-spacing: 1px; text-transform: uppercase;"><?php echo htmlspecialchars($deal['region']); ?></span>
                    <div style="display: flex; color: #f5a623; font-size: 12px;">
                        <?php 
                        $rating = $deal['rating'] ?? 5;
                        for($i=0; $i<5; $i++) echo $i < $rating ? '★' : '☆'; 
                        ?>
                    </div>
                </div>
                <h3 style="color: #333; font-size: 20px; font-weight: 800; line-height: 1.3; margin: 0 0 25px 0;"><?php echo htmlspecialchars($deal['title']); ?></h3>
                
                <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                    <span style="font-weight: 800; font-size: 16px; color: #285da1;"><?php echo htmlspecialchars($deal['price']); ?></span>
                    <span style="display: inline-block; padding: 10px 20px; background: #285da1; color: white; font-size: 11px; font-weight: 800; border-radius: 30px; letter-spacing: 1px;">VIEW DETAILS</span>
                </div>
            </div>
        </a>
        <?php
    }
}
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html,
    'count' => count($filtered_deals)
]);
?>
