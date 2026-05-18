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
-- Table structure for table `travel_ideas`
--

CREATE TABLE `travel_ideas` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `province` varchar(150) DEFAULT NULL,
  `province_slug` varchar(150) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `vibe` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `difficulty` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_ideas`
--

INSERT INTO `travel_ideas` (`id`, `slug`, `title`, `province`, `province_slug`, `image_path`, `duration`, `vibe`, `type`, `difficulty`, `created_at`) VALUES
(1, 'everest-base-camp', 'Everest Base Camp Trek', 'Koshi Province', 'koshi', '../images/everest_trek.png', '14D13N', 'Adventure / Mountaineering', 'Trekking', 'Challenging', '2026-05-16 19:29:31'),
(2, 'pokhara-lakeside', 'Pokhara Lakeside Retreat', 'Gandaki Province', 'gandaki', '../images/pokhara_lake.png', '3D2N', 'Relaxation / Nature', 'Relaxation', 'Easy', '2026-05-16 19:29:31'),
(3, 'kathmandu-heritage', 'Kathmandu Heritage Walk', 'Bagmati Province', 'bagmati', '../images/kathmandu_night_hero.png', '2D1N', 'Culture / History', 'Culture', 'Easy', '2026-05-16 19:29:31'),
(4, 'lumbini-pilgrimage', 'Lumbini Peace Pilgrimage', 'Lumbini Province', 'lumbini', '../images/lumbini_temple.png', '3D2N', 'Spirituality / Zen', 'Pilgrimage', 'Easy', '2026-05-16 19:29:31'),
(5, 'janaki-devotion', 'Janaki Temple Devotion', 'Madhesh Province', 'madhesh', '../images/city_excitement_nepal.png', '2D1N', 'Religion / Art', 'Pilgrimage', 'Easy', '2026-05-16 19:29:31'),
(6, 'annapurna-sanctuary', 'Annapurna Sanctuary', 'Gandaki Province', 'gandaki', '../images/annapurna_trek.png', '10D9N', 'Trekking / Nature', 'Trekking', 'Challenging', '2026-05-16 19:29:31'),
(7, 'chitwan-wildlife', 'Chitwan Wildlife Safari', 'Bagmati Province', 'bagmati', '../images/chitwan_rhino.png', '3D2N', 'Wildlife / Safari', 'Wildlife', 'Moderate', '2026-05-16 19:29:31'),
(8, 'bhaktapur-medieval', 'Bhaktapur Medieval Tour', 'Bagmati Province', 'bagmati', '../images/bhaktapur_temple.png', '1D', 'Heritage / Culture', 'Heritage', 'Easy', '2026-05-16 19:29:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `travel_ideas`
--
ALTER TABLE `travel_ideas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `travel_ideas`
--
ALTER TABLE `travel_ideas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
