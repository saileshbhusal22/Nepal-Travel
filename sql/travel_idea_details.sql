-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 09:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nepal_travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `travel_idea_details`
--

CREATE TABLE `travel_idea_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `idea_slug` varchar(255) NOT NULL,
  `intro` text DEFAULT NULL,
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights`)),
  `itinerary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`itinerary`)),
  `logistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`logistics`)),
  `hero_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_idea_details`
--

INSERT INTO `travel_idea_details` (`id`, `idea_slug`, `intro`, `highlights`, `itinerary`, `logistics`, `hero_image`, `created_at`) VALUES
(1, 'everest-base-camp', 'The Everest Base Camp Trek is more than just a hike; it is a pilgrimage to the highest point on Earth.', '[\"Iconic view of Mt. Everest from Kala Patthar\", \"Sherpa culture in Namche Bazaar\", \"Khumbu Glacier experience\", \"Sagarmatha National Park\"]', '{\"Day 1\": {\"title\": \"The Gateway: Namche Bazaar\", \"morning\": \"Fly to Lukla and trek to Phakding\", \"afternoon\": \"Suspension bridges and forest trails\", \"evening\": \"Overnight in tea house\"}, \"Day 5\": {\"title\": \"Acclimatization\", \"morning\": \"Explore Namche Bazaar\", \"afternoon\": \"Hike Everest View Hotel\", \"evening\": \"Sherpa dinner\"}, \"Day 10\": {\"title\": \"Base Camp\", \"morning\": \"Final climb to EBC\", \"afternoon\": \"Celebrate at Base Camp\", \"evening\": \"Return trek\"}}', '{\"transport\": \"Flight to Lukla\", \"accommodation\": \"Tea houses\", \"best_time\": \"Mar-May & Sep-Nov\", \"pro_tip\": \"Acclimatize properly\"}', '../images/everest_trek.png', '2026-05-16 19:27:09'),
(2, 'pokhara-lakeside', 'Pokhara is a peaceful lakeside city under the Annapurna range.', '[\"Phewa Lake boating\", \"Sarangkot sunrise\", \"Davis Falls\", \"Peace Stupa\"]', '{\"Day 1\": {\"title\": \"Arrival\", \"morning\": \"Check-in hotel\", \"afternoon\": \"Boating\", \"evening\": \"Sunset dinner\"}, \"Day 2\": {\"title\": \"Exploration\", \"morning\": \"Sarangkot sunrise\", \"afternoon\": \"Sightseeing\", \"evening\": \"Cultural show\"}}', '{\"transport\": \"Bus or flight\", \"accommodation\": \"Hotels\", \"best_time\": \"All year\", \"pro_tip\": \"Try paragliding\"}', '../images/phewa_sunset.png', '2026-05-16 19:27:09'),
(3, 'kathmandu-heritage', 'Kathmandu is a living museum of culture and temples.', '[\"Durbar Square\", \"Pashupatinath\", \"Boudhanath\", \"Swayambhunath\"]', '{\"Day 1\": {\"title\": \"Heritage Tour\", \"morning\": \"Durbar Square\", \"afternoon\": \"Swayambhunath\", \"evening\": \"Dinner\"}, \"Day 2\": {\"title\": \"Spiritual Tour\", \"morning\": \"Pashupatinath\", \"afternoon\": \"Boudhanath\", \"evening\": \"Cafe\"}}', '{\"transport\": \"Taxi\", \"accommodation\": \"Hotels\", \"best_time\": \"Sept-April\", \"pro_tip\": \"Morning visits recommended\"}', '../images/ktm_durbar.png', '2026-05-16 19:27:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `travel_idea_details`
--
ALTER TABLE `travel_idea_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idea_slug` (`idea_slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `travel_idea_details`
--
ALTER TABLE `travel_idea_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
