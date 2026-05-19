<?php
require_once __DIR__ . '/../../config/db.php';

$columns_needed = [
    'tags' => 'TEXT',
    'language' => 'VARCHAR(50) DEFAULT "Both"',
    'age_group' => 'VARCHAR(50) DEFAULT "All Ages"',
    'what_to_expect' => 'TEXT',
    'gallery_images' => 'TEXT',
    'region' => 'VARCHAR(100)',
    'start_date' => 'DATE',
    'end_date' => 'DATE',
    'start_time' => 'TIME',
    'end_time' => 'TIME',
    'is_recurring' => 'TINYINT(1) DEFAULT 0',
    'recurring_frequency' => 'VARCHAR(50)',
    'venue_name' => 'VARCHAR(255)',
    'google_maps_link' => 'TEXT',
    'is_paid' => 'TINYINT(1) DEFAULT 0',
    'price_npr' => 'DECIMAL(10,2) DEFAULT 0',
    'seats' => 'INT DEFAULT 0',
    'unlimited_seats' => 'TINYINT(1) DEFAULT 0',
    'registration_url' => 'TEXT',
    'selling_fast_threshold' => 'INT DEFAULT 80',
    'organizer_name' => 'VARCHAR(255)',
    'organizer_contact' => 'VARCHAR(100)',
    'organizer_email' => 'VARCHAR(255)',
    'organizer_website' => 'VARCHAR(255)',
    'organizer_facebook' => 'VARCHAR(255)',
    'organizer_instagram' => 'VARCHAR(255)',
    'is_premium' => 'TINYINT(1) DEFAULT 0',
    'is_featured' => 'TINYINT(1) DEFAULT 0',
    'is_private' => 'TINYINT(1) DEFAULT 0',
    'early_bird_text' => 'VARCHAR(255)',
    'featured_badge_text' => 'VARCHAR(50) DEFAULT "FEATURED"',
    'ticket_price' => 'VARCHAR(100)',
    'ticket_link' => 'TEXT',
    'ticket_status' => 'VARCHAR(50) DEFAULT "Available"',
    'homepage_spotlight' => 'TINYINT(1) DEFAULT 0',
    'raffle_enabled' => 'TINYINT(1) DEFAULT 0',
    'raffle_draw_time' => 'VARCHAR(100)',
    'raffle_prize_1' => 'VARCHAR(255)',
    'raffle_prize_2' => 'VARCHAR(255)',
    'raffle_entry_fee' => 'DECIMAL(10,2) DEFAULT 0',
    'free_parking' => 'TINYINT(1) DEFAULT 0',
    'is_international' => 'TINYINT(1) DEFAULT 0',
    'user_id' => 'INT'
];

foreach ($columns_needed as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM events LIKE '$col'");
    if ($res->num_rows == 0) {
        echo "Adding column $col...\n";
        $conn->query("ALTER TABLE events ADD COLUMN $col $type");
    }
}
echo "Database schema update complete.\n";
?>