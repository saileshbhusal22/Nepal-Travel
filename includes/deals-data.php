<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Initialize saved deals session array if it doesn't exist
if (!isset($_SESSION['saved_deals'])) {
    $_SESSION['saved_deals'] = [];
}
    
$deals = [
    'everest' => [
        'id' => 'everest',
        'title' => 'Everest Base Camp - 14D13N Trekking Tour',
        'destination_value' => 'Everest Base Camp',
        'duration' => '14 DAYS 13 NIGHTS',
        'price' => 'FROM $1,299.00',
        'image' => 'images/everest_trek.png',
        'desc' => 'The ultimate trekking experience to the base of the world\'s highest peak. Journey through the Khumbu valley and experience sherpa culture.',
        'region' => 'SAGARMATHA ZONE',
        'category_badge' => 'TREKKING',
        'badge_color' => '#673ab7',
        'rating' => 5,
        'itinerary' => [
            'Day 1-2: Arrival in Kathmandu & Trek Preparation',
            'Day 3: Scenic Flight to Lukla, Trek to Phakding',
            'Day 4: Trek to Namche Bazaar (3440m)',
            'Day 5: Acclimatization Day at Namche',
            'Day 6-9: Trekking through Tengboche, Dingboche, and Lobuche',
            'Day 10: Reach Everest Base Camp & Kala Patthar',
            'Day 11-13: Descent back to Lukla',
            'Day 14: Flight back to Kathmandu & Departure'
        ]
    ],
    'pokhara' => [
        'id' => 'pokhara',
        'title' => 'Pokhara Resort - 4D3N Leisure Camping',
        'destination_value' => 'Pokhara & Annapurna',
        'duration' => '4 DAYS 3 NIGHTS',
        'price' => 'FROM $499.00',
        'image' => 'images/pokhara_lake.png',
        'desc' => 'Relax by Phewa Lake and enjoy breathtaking short hikes in the Himalayas. Perfect for families and rapid mountain getaways.',
        'region' => 'GANDAKI PROVINCE',
        'category_badge' => 'OUTDOOR',
        'badge_color' => '#6a1b9a',
        'rating' => 4,
        'itinerary' => [
            'Day 1: Arrival in Pokhara, Hotel check-in',
            'Day 2: Sunrise at Sarangkot & Boating on Phewa Lake',
            'Day 3: Short hike to the World Peace Pagoda',
            'Day 4: Explore Davis Falls and Gupteshwor Cave',
            'Day 5: Departure to Kathmandu'
        ]
    ],
    'lumbini' => [
        'id' => 'lumbini',
        'title' => '3 Days 2 Nights Lumbini Pilgrimage Escape',
        'destination_value' => 'Lumbini Pilgrimage',
        'duration' => '3 DAYS 2 NIGHTS',
        'price' => 'FROM $299.00',
        'image' => 'images/lumbini_temple.png',
        'desc' => 'A peaceful spiritual journey to the birthplace of Lord Buddha. Experience the serenity of ancient monasteries and monuments.',
        'region' => 'LUMBINI PROVINCE',
        'category_badge' => 'CULTURE',
        'badge_color' => '#0288d1',
        'rating' => 5,
        'itinerary' => [
            'Day 1: Flight from Kathmandu to Bhairahawa, transfer to Lumbini',
            'Day 2: Full day exploring Maya Devi Temple & International Monasteries',
            'Day 3: Morning meditation and flight back to Kathmandu'
        ]
    ],
    'chitwan' => [
        'id' => 'chitwan',
        'title' => 'Chitwan Jungle Safari - 3D2N Wildlife Tour',
        'destination_value' => 'Chitwan National Park',
        'duration' => '3 DAYS 2 NIGHTS',
        'price' => 'FROM $349.00',
        'image' => 'images/chitwan_rhino.png',
        'desc' => 'Dive deep into the humid jungles of the Terai. Encounter the rare one-horned rhinoceros and Bengal tigers in their natural habitat.',
        'region' => 'BAGMATI PROVINCE',
        'category_badge' => 'WILDLIFE',
        'badge_color' => '#388e3c',
        'rating' => 4,
        'itinerary' => [
            'Day 1: Arrival in Chitwan, Tharu Village Tour',
            'Day 2: Full day Jungle Safari (Elephant/Jeep) & Canoe Ride',
            'Day 3: Bird Watching & Departure'
        ]
    ],
    'kathmandu' => [
        'id' => 'kathmandu',
        'title' => 'Kathmandu Valley - 5D4N Heritage Exploration',
        'destination_value' => 'Kathmandu Valley',
        'duration' => '5 DAYS 4 NIGHTS',
        'price' => 'FROM $599.00',
        'image' => 'images/kathmandu_night_hero.png',
        'desc' => 'Explore the ancient capital of Nepal. A cultural immersion into centuries-old palaces, stupas, and bustling night markets.',
        'region' => 'KATHMANDU',
        'category_badge' => 'CULTURE',
        'badge_color' => '#fbc02d',
        'rating' => 5,
        'itinerary' => [
            'Day 1: Arrival & Night walk in Thamel',
            'Day 2: Pashupatinath & Boudhanath Stupa',
            'Day 3: Swayambhunath (Monkey Temple) & Patan Durbar',
            'Day 4: Day trip to Chandragiri Hills',
            'Day 5: Souvenir Shopping & Departure'
        ]
    ],
    'bhaktapur' => [
        'id' => 'bhaktapur',
        'title' => 'Medieval Bhaktapur - Ancient City Walk',
        'destination_value' => 'Bhaktapur',
        'duration' => '2 DAYS 1 NIGHT',
        'price' => 'FROM $149.00',
        'image' => 'images/bhaktapur_temple.png',
        'desc' => 'Step back into medieval times. Discover the exquisite wood carvings, ancient courtyards, and world-renowned pottery of Bhaktapur.',
        'region' => 'BHAKTAPUR ZONE',
        'category_badge' => 'HERITAGE',
        'badge_color' => '#d32f2f',
        'rating' => 4,
        'itinerary' => [
            'Day 1: Arrival, Durbar Square Tour & Pottery Square',
            'Day 2: Sunrise views, local Juju Dhau tasting & Departure'
        ]
    ],
    'himalayan-peak-retreat' => [
        'id' => 'himalayan-peak-retreat',
        'title' => 'Himalayan Peak Retreat - Luxury Discovery',
        'destination_value' => 'Annapurna & Pokhara',
        'duration' => '12 DAYS 11 NIGHTS',
        'price' => 'FROM $1,450.00',
        'image' => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?q=80&w=1200',
        'desc' => 'Experience the mountains in absolute luxury. This curated retreat combines soft trekking with the finest boutique stays and private helicopter tours.',
        'region' => 'GANDAKI PROVINCE',
        'category_badge' => 'LUXURY',
        'badge_color' => '#00bcd4',
        'rating' => 5,
        'itinerary' => [
            'Day 1-2: Luxury Stay in Kathmandu Valley',
            'Day 3: Private Heli to Annapurna Base Camp',
            'Day 4-6: Gentle Trekking through Gurung Villages',
            'Day 7-10: Lakeside Wellness & Yoga in Pokhara',
            'Day 11: Spa & Farewell Dinner',
            'Day 12: Departure'
        ]
    ],
    'wild-west-expedition' => [
        'id' => 'wild-west-expedition',
        'title' => 'Wild West Expedition - Remote Adventure',
        'destination_value' => 'Karnali & Far-West',
        'duration' => '18 DAYS 17 NIGHTS',
        'price' => 'FROM $2,100.00',
        'image' => 'https://images.unsplash.com/photo-1623492701902-47dc207df5dc?q=80&w=1200',
        'desc' => 'The ultimate expedition into Nepal\'s untouched wilderness. From the sapphire waters of Rara to the high passes of Dolpo.',
        'region' => 'KARNALI PROVINCE',
        'category_badge' => 'ADVENTURE',
        'badge_color' => '#ff5722',
        'rating' => 5,
        'itinerary' => [
            'Day 1-3: Heading to Nepalgunj and Talcha',
            'Day 4-7: Rara Lake Exploration & Camping',
            'Day 8-14: Remote Trekking through Mugu to Upper Dolpo',
            'Day 15-17: Phoksundo Lake & Ringmo Village',
            'Day 18: Exit via Juphal & Nepalgunj'
        ]
    ],
    'ancient-valley-wonders' => [
        'id' => 'ancient-valley-wonders',
        'title' => 'Ancient Valley Wonders - Cultural Heritage',
        'destination_value' => 'Kathmandu Valley',
        'duration' => '5 DAYS 4 NIGHTS',
        'price' => 'FROM $550.00',
        'image' => 'images/kathmandu_night_hero.png',
        'desc' => 'A deep dive into the three medieval kingdoms of the Kathmandu Valley. Expert-guided tours of UNESCO sites and living traditions.',
        'region' => 'KATHMANDU VALLEY',
        'category_badge' => 'CULTURE',
        'badge_color' => '#4caf50',
        'rating' => 5,
        'itinerary' => [
            'Day 1: Arrival & Evening at Boudhanath',
            'Day 2: Kathmandu Durbar Square & Swayambhunath',
            'Day 3: Medieval Patan & Traditional Metalwork',
            'Day 4: Ancient Bhaktapur & Pottery Experience',
            'Day 5: Souvenir Shopping in Thamel & Departure'
        ]
    ]
];
?>
