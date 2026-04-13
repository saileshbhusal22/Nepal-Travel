<?php
/**
 * Detailed itineraries and content for Travel Ideas
 */
$travel_idea_details = [
    'everest-base-camp' => [
        'slug' => 'everest-base-camp',
        'title' => 'Everest Base Camp Trek',
        'province' => 'Koshi Province',
        'duration' => '14D13N',
        'vibe' => 'Adventure / Mountaineering',
        'hero_image' => '../images/everest_trek.png',
        'intro' => 'The Everest Base Camp Trek is more than just a hike; it\'s a pilgrimage to the highest point on Earth. Walk in the footsteps of legends through Sherpa villages, ancient monasteries, and breathtaking alpine landscapes.',
        'highlights' => [
            'Iconic view of Mt. Everest from Kala Patthar',
            'Sherpa culture and hospitality in Namche Bazaar',
            'Khumbu Glacier and the vibrant Base Camp',
            'Sagarmatha National Park, a UNESCO World Heritage site'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'The Gateway: Namche Bazaar',
                'morning' => 'Fly to Lukla (2,860m) and start the trek to Phakding.',
                'afternoon' => 'Trek through lush green forests and crossed suspension bridges.',
                'evening' => 'Overnight in a traditional Sherpa tea house.',
                'img' => '../images/namche_bazaar.png'
            ],
            'Day 5' => [
                'title' => 'Acclimatization in the Clouds',
                'morning' => 'Explore the vibrant market of Namche Bazaar (3,440m).',
                'afternoon' => 'Hike to the Everest View Hotel for the first glimpse of the peak.',
                'evening' => 'Local Sherpa dinner and cultural briefing.',
                'img' => '../images/namche_bazaar.png'
            ],
            'Day 10' => [
                'title' => 'Reaching the Roof of the World',
                'morning' => 'Final ascent from Gorak Shep to EBC (5,364m).',
                'afternoon' => 'Celebrate the achievement amidst the prayer flags of Base Camp.',
                'evening' => 'Return to Gorak Shep for a well-deserved rest.',
                'img' => '../images/ebc_summit.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Twin Otter flight to Lukla, then purely trekking.',
            'accommodation' => 'Sherpa Tea Houses / Himalayan Lodges.',
            'best_time' => 'March-May and September-November.',
            'pro_tip' => 'Always pack a high-quality down jacket and stay hydrated to prevent altitude sickness.'
        ]
    ],
    'pokhara-lakeside' => [
        'slug' => 'pokhara-lakeside',
        'title' => 'Pokhara Lakeside Retreat',
        'province' => 'Gandaki Province',
        'duration' => '3D2N',
        'vibe' => 'Relaxation / Nature',
        'hero_image' => '../images/phewa_sunset.png',
        'intro' => 'Nestled under the shadow of the Machhapuchhre (Fishtail) peak, Pokhara is the ultimate destination for tranquility. From its serene lakes to its laid-back cafe culture, it is the heart of Nepali leisure.',
        'highlights' => [
            'Boating on the emerald waters of Phewa Lake',
            'Sunrise over the Annapurna range from Sarangkot',
            'Exploring the mysterious Gupteshwor Mahadev Cave',
            'The peaceful ambiance of World Peace Stupa'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'Arrival & Lake Wanderings',
                'morning' => 'Arrival in Pokhara and check-in to a lakeside hotel.',
                'afternoon' => 'Private boating session to Tal Barahi Temple.',
                'evening' => 'Sunset dinner at a premium lakeside lounge.',
                'img' => '../images/phewa_sunset.png'
            ],
            'Day 2' => [
                'title' => 'Sunrise & Exploration',
                'morning' => 'Early morning drive to Sarangkot for the gold-tinted sunrise.',
                'afternoon' => 'Visit Davis Falls and the International Mountain Museum.',
                'evening' => 'Cultural show and traditional Nepali Thakali dinner.',
                'img' => '../images/sarangkot_sunrise.png'
            ]
        ],
        'logistics' => [
            'transport' => '25-minute flight from KTM or 6-hour scenic tourist bus.',
            'accommodation' => 'Luxury Resorts or Boutique Lakeside Hotels.',
            'best_time' => 'All year round, though Spring and Autumn offer clearest peaks.',
            'pro_tip' => 'Try paragliding from Sarangkot for an unforgettable view of the valley.'
        ]
    ],
    'kathmandu-heritage' => [
        'slug' => 'kathmandu-heritage',
        'title' => 'Kathmandu Heritage Walk',
        'province' => 'Bagmati Province',
        'duration' => '2D1N',
        'vibe' => 'Culture / History',
        'hero_image' => '../images/ktm_durbar.png',
        'intro' => 'Kathmandu is a living museum. Every corner of the city reveals an ancient temple, a centuries-old courtyard, or a vibrant market that has remained unchanged for generations.',
        'highlights' => [
            'Awe-inspiring Hanuman Dhoka Durbar Square',
            'The sacred atmosphere of Pashupatinath Temple',
            'Walking through the narrow, spicy alleys of Asan Bazar',
            'Boudhanath Stupa, the center of Tibetan Buddhism'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'The Living Goddess & Ancient Squares',
                'morning' => 'Tour of Kathmandu Durbar Square and the Kumari Ghar.',
                'afternoon' => 'Hike up to Swayambhunath (Monkey Temple) for city views.',
                'evening' => 'Authentic Newari feast in a heritage restaurant.',
                'img' => '../images/ktm_durbar.png'
            ],
            'Day 2' => [
                'title' => 'Spiritual Circles',
                'morning' => 'Visit Pashupatinath and witness the morning rituals.',
                'afternoon' => 'Circumambulate the massive Boudhanath Stupa.',
                'evening' => 'Coffee at a rooftop cafe overlooking the stupa.',
                'img' => '../images/pashupatinath_aarti.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Best explored via local Taxis or guided walking tours.',
            'accommodation' => 'Heritage Hotels in Thamel or Boutique Guest Houses.',
            'best_time' => 'September to April.',
            'pro_tip' => 'Carry a camera for the incredible evening lights at the temple squares.'
        ]
    ],
    'lumbini-pilgrimage' => [
        'slug' => 'lumbini-pilgrimage',
        'title' => 'Lumbini Peace Pilgrimage',
        'province' => 'Lumbini Province',
        'duration' => '3D2N',
        'vibe' => 'Spirituality / Zen',
        'hero_image' => '../images/lumbini_temple.png',
        'intro' => 'Step into the birthplace of the Light of Asia. Lumbini is a place of profound peace, where various nations have built stunning monasteries to honor the legacy of Lord Buddha.',
        'highlights' => [
            'The sacred Mayadevi Temple and Ashoka Pillar',
            'Strolling through the international monastic zone',
            'The flaming World Peace Flame',
            'Peaceful meditation sessions by the canal'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'Sacred Grounds',
                'morning' => 'Visit the Mayadevi Temple, the exact birthplace of Buddha.',
                'afternoon' => 'Explore the Ashoka Pillar and the sacred pond.',
                'evening' => 'Meditation session at the German Monastery.',
                'img' => '../images/lumbini_temple.png'
            ],
            'Day 2' => [
                'title' => 'Global Monasteries',
                'morning' => 'Cycle through the Monastic Zone (Chinese, Thai, French temples).',
                'afternoon' => 'Visit the World Peace Pagoda on the northern end.',
                'evening' => 'Boat ride along the central canal.',
                'img' => '../images/hero_nepal.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Flight to Gautam Buddha International Airport (BWA).',
            'accommodation' => 'Peaceful spiritual retreats or Buddhist guest houses.',
            'best_time' => 'October to March (avoids the summer heat).',
            'pro_tip' => 'Rent a bicycle to explore the vast monastic zone efficiently.'
        ]
    ],
    'janaki-devotion' => [
        'slug' => 'janaki-devotion',
        'title' => 'Janaki Temple Devotion',
        'province' => 'Madhesh Province',
        'duration' => '2D1N',
        'vibe' => 'Religion / Art',
        'hero_image' => '../images/city_excitement_nepal.png',
        'intro' => 'Janakpur, the birthplace of Sita, is a city of ponds and temples. The Janaki Temple is its crown jewel—a majestic structure of marble and stone that blends Mughal and Hindu styles.',
        'highlights' => [
            'The glowing white Janaki Mandir at sunset',
            'Exploring the Vivah Mandap where Ram and Sita married',
            'Mithila art workshops in local villages',
            'The holy ponds (Sagar) of the city'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'City of Ponds',
                'morning' => 'Arrival and visit to the main Janaki Temple.',
                'afternoon' => 'Tour of Ram Mandir and the Vivah Mandap.',
                'evening' => 'Evening Aarti ritual, a spectacular spiritual light show.',
                'img' => '../images/city_excitement_nepal.png'
            ],
            'Day 2' => [
                'title' => 'Mithila Roots',
                'morning' => 'Visit a local Mithila Art center to see traditional painting.',
                'afternoon' => 'Walk around Dhanush Sagar and Ganga Sagar.',
                'evening' => 'Sample local sweets like the famous Janakpur Ladoo.',
                'img' => '../images/food_drinks_nepal.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Daily flights from KTM or scenic drive through Terai.',
            'accommodation' => 'Heritage Guest Houses or Traditional Bhajanalayas.',
            'best_time' => 'Winter is best; Janakpur can be very hot in summer.',
            'pro_tip' => 'Try to visit during Vivah Panchami for the grandest celebration.'
        ]
    ],
    'annapurna-sanctuary' => [
        'slug' => 'annapurna-sanctuary',
        'title' => 'Annapurna Sanctuary',
        'province' => 'Gandaki Province',
        'duration' => '10D9N',
        'vibe' => 'Treking / Nature',
        'hero_image' => '../images/annapurna_trek.png',
        'intro' => 'A trek into the heart of the Himalaya. The Annapurna Sanctuary is a high-altitude glacial basin surrounded by a ring of mountains, including the legendary Annapurna I.',
        'highlights' => [
            'The 360-degree mountain view from Machhapuchhre Base Camp',
            'Relaxing in the natural hot springs of Jhinu Danda',
            'Trekking through rhododendron and bamboo forests',
            'Experiencing Gurung culture in Himalayan villages'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'Into the Wild',
                'morning' => 'Drive from Pokhara to Nayapul, start trekking.',
                'afternoon' => 'Follow the Modi Khola river to Ghandruk.',
                'evening' => 'Cultural dance performance by the Gurung community.',
                'img' => '../images/annapurna_trek.png'
            ],
            'Day 7' => [
                'title' => 'The Sanctuary',
                'morning' => 'Final climb to ABC (4,130m).',
                'afternoon' => 'Stand in the center of the vertical Himalayan giants.',
                'evening' => 'Watching the stars over the peaks from the lodges.',
                'img' => '../images/pokhara_lake.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Local Jeeps to trailheads, then purely trekking.',
            'accommodation' => 'Village Guest Houses / Community Lodges.',
            'best_time' => 'March-May and October-November.',
            'pro_tip' => 'Don\'t miss the hot springs at Jhinu Danda on your descent!'
        ]
    ],
    'chitwan-wildlife' => [
        'slug' => 'chitwan-wildlife',
        'title' => 'Chitwan Wildlife Safari',
        'province' => 'Bagmati Province',
        'duration' => '3D2N',
        'vibe' => 'Wildlife / Safari',
        'hero_image' => '../images/chitwan_rhino.png',
        'intro' => 'Deep in the subtropical lowlands, Chitwan is a sanctuary for the Great One-horned Rhinoceros and the elusive Bengal Tiger. It’s a world away from the high peaks.',
        'highlights' => [
            'Jeep safari into the dense Sal forests',
            'Canoeing along the Rapti River at sunset',
            'Visiting the Elephant Breeding Center',
            'Tharu cultural stick dance performance'
        ],
        'itinerary' => [
            'Day 1' => [
                'title' => 'Riverside Arrival',
                'morning' => 'Arrival in Sauraha and check-in to a jungle lodge.',
                'afternoon' => 'Canoeing session to spot gharial crocodiles.',
                'evening' => 'Tharu cultural show and BBQ dinner.',
                'img' => '../images/chitwan_rhino.png'
            ],
            'Day 2' => [
                'title' => 'Into the Jungle',
                'morning' => 'Deep jungle safari via Jeep.',
                'afternoon' => 'Elephant bathing or bird watching tour.',
                'evening' => 'Birdwatching by the riverbank with drinks.',
                'img' => '../images/family_fun_nepal.png'
            ]
        ],
        'logistics' => [
            'transport' => 'Tourist buses from KTM/Pokhara or Bharatpur flight.',
            'accommodation' => 'Luxury Safari Tents or Eco-Lodges.',
            'best_time' => 'October to March (tall grass is lower, better visibility).',
            'pro_tip' => 'Wear neutral colors during safaris to blend into the environment.'
        ]
    ],
    'bhaktapur-medieval' => [
        'slug' => 'bhaktapur-medieval',
        'title' => 'Bhaktapur Medieval Tour',
        'province' => 'Bagmati Province',
        'duration' => '1D',
        'vibe' => 'Heritage / Potter',
        'hero_image' => '../images/bhaktapur_temple.png',
        'intro' => 'Known as the "City of Devotees," Bhaktapur is perhaps the most well-preserved of the three royal cities. Its pottery squares and intricate woodcarvings are world-renowned.',
        'highlights' => [
            'Nyatapola, the highest pagoda in Nepal',
            'Learning to spin a wheel in Pottery Square',
            'The 55-Window Palace and the Golden Gate',
            'Tasting the legendary Juju Dhau (King Curd)'
        ],
        'itinerary' => [
            'Morning' => [
                'title' => 'The Royal Court',
                'morning' => 'Explore the Durbar Square and the National Art Gallery.',
                'afternoon' => 'Visit Nyatapola Temple in Taumadhi Square.',
                'evening' => 'Tea at a cafe with a view of the square.',
                'img' => '../images/bhaktapur_temple.png'
            ],
            'Afternoon' => [
                'title' => 'City of Crafts',
                'morning' => 'Visit Pottery Square and try making a clay pot.',
                'afternoon' => 'Walk to Dattatreya Square for the Woodcarving Museum.',
                'evening' => 'Sample local sweets like Bara and Juju Dhau.',
                'img' => '../images/food_drinks_nepal.png'
            ]
        ],
        'logistics' => [
            'transport' => '30-minute taxi ride from central Kathmandu.',
            'accommodation' => 'Traditional Newari Guest Houses.',
            'best_time' => 'Any time; beautiful in the clear light of Autumn.',
            'pro_tip' => 'Bhaktapur is a pedestrian-only zone, wear comfortable shoes.'
        ]
    ]
];