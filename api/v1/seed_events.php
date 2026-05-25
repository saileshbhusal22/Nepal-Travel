<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
require_once __DIR__ . '/../../config/db.php';

$events = [
    [
        'title' => 'Everest Jazz Festival 2026',
        'description' => 'A world-class jazz experience at the foothills of the Himalayas. Featuring international artists and local fusion bands.',
        'image_path' => 'images/pokhara_lake.png',
        'category' => 'ARTS & CULTURE',
        'event_date' => '15-18 MAY',
        'month' => 'MAY',
        'location' => 'Namche Bazaar',
        'is_premium' => 1,
        'ticket_price' => '$150 - $500',
        'ticket_link' => 'https://example.com/tickets',
        'is_featured' => 1
    ],
    [
        'title' => 'Kathmandu K-Pop Night',
        'description' => 'The biggest K-Pop dance and music festival in Nepal. Join thousands of fans for a night of high-energy performances.',
        'image_path' => 'images/bhaktapur_temple.png',
        'category' => 'ARTS & CULTURE',
        'event_date' => '22 MAY',
        'month' => 'MAY',
        'location' => 'Dashrath Rangasala',
        'is_premium' => 0,
        'ticket_price' => 'NPR 1500',
        'ticket_link' => '',
        'is_featured' => 0
    ],
    [
        'title' => 'Annapurna Trail Race',
        'description' => 'A challenging marathon through the rugged trails of the Annapurna region. For professional and amateur runners.',
        'image_path' => 'images/annapurna_trek.png',
        'category' => 'SPORTS',
        'event_date' => '10 MAY',
        'month' => 'MAY',
        'location' => 'Ghorepani',
        'is_premium' => 1,
        'ticket_price' => '$50',
        'ticket_link' => 'https://example.com/register',
        'is_featured' => 1
    ],
    [
        'title' => 'Nepal Food & Wine Expo',
        'description' => 'Taste the flavors of Nepal! A gathering of the best chefs, local organic producers, and winery representatives.',
        'image_path' => 'images/food_drinks_nepal.png',
        'category' => 'FESTIVAL',
        'event_date' => '05-07 MAY',
        'month' => 'MAY',
        'location' => 'Brikutimandap',
        'is_premium' => 0,
        'ticket_price' => 'FREE ENTRY',
        'ticket_link' => '',
        'is_featured' => 0
    ],
    [
        'title' => 'White Water Kayak Championship',
        'description' => 'Watch the world\'s best kayakers tackle the rapids of the Trishuli River in this annual adrenaline-pumping event.',
        'image_path' => 'images/pokhara_lake.png',
        'category' => 'SPORTS',
        'event_date' => '28-30 MAY',
        'month' => 'MAY',
        'location' => 'Trishuli River',
        'is_premium' => 0,
        'ticket_price' => 'FREE FOR SPECTATORS',
        'ticket_link' => '',
        'is_featured' => 0
    ],
    [
        'title' => 'Lumbini Peace Concert',
        'description' => 'A spiritual musical evening at the birthplace of Lord Buddha, promoting global peace and harmony.',
        'image_path' => 'images/lumbini.png',
        'category' => 'ARTS & CULTURE',
        'event_date' => '01 MAY',
        'month' => 'MAY',
        'location' => 'Lumbini Garden',
        'is_premium' => 1,
        'ticket_price' => '$20',
        'ticket_link' => 'https://example.com/peace',
        'is_featured' => 1
    ],
    [
        'title' => 'Newari Cultural Showcase',
        'description' => 'A celebration of the rich Newari heritage with traditional masked dances, music, and authentic cuisine.',
        'image_path' => 'images/bhaktapur_temple.png',
        'category' => 'FESTIVAL',
        'event_date' => '12 MAY',
        'month' => 'MAY',
        'location' => 'Bhaktapur Durbar Square',
        'is_premium' => 0,
        'ticket_price' => 'NPR 500',
        'ticket_link' => '',
        'is_featured' => 0
    ],
    [
        'title' => 'Paragliding Grand Prix Pokhara',
        'description' => 'The skies of Pokhara will be filled with colorful gliders as pilots compete in this international cross-country race.',
        'image_path' => 'images/pokhara_lake.png',
        'category' => 'SPORTS',
        'event_date' => '19-21 MAY',
        'month' => 'MAY',
        'location' => 'Sarangkot',
        'is_premium' => 0,
        'ticket_price' => 'FREE FOR SPECTATORS',
        'ticket_link' => '',
        'is_featured' => 0
    ]
];

foreach ($events as $e) {
    $stmt = $conn->prepare("INSERT INTO events (title, description, image_path, category, event_date, month, location, is_premium, ticket_price, ticket_link, is_featured, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssssisii", $e['title'], $e['description'], $e['image_path'], $e['category'], $e['event_date'], $e['month'], $e['location'], $e['is_premium'], $e['ticket_price'], $e['ticket_link'], $e['is_featured']);
    $stmt->execute();
}

echo "Events inserted successfully!";
?>