-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 10:29 PM
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
-- Database: `eventhub_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `booked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `event_id`, `quantity`, `booking_status`, `booked_at`) VALUES
(1, 4, 1, 2, 'confirmed', '2026-09-01 10:10:00'),
(2, 5, 2, 1, 'confirmed', '2026-09-02 14:25:00'),
(3, 6, 3, 4, 'pending', '2026-09-03 18:00:00'),
(4, 7, 4, 2, 'cancelled', '2026-09-04 12:30:00'),
(5, 8, 5, 3, 'confirmed', '2026-09-05 08:55:00'),
(6, 4, 6, 2, 'confirmed', '2026-09-06 19:15:00'),
(7, 5, 3, 3, 'confirmed', '2026-09-07 11:45:00'),
(8, 6, 1, 1, 'confirmed', '2026-09-08 16:20:00'),
(9, 10, 1, 1, 'confirmed', '2026-10-14 17:18:19'),
(10, 10, 2, 1, 'cancelled', '2026-10-14 17:46:30'),
(11, 9, 3, 2, 'pending', '2026-10-15 02:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `organiser_id` int(11) NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `ticket_price` decimal(10,2) NOT NULL,
  `total_tickets` int(11) NOT NULL,
  `tickets_available` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `venue_id`, `organiser_id`, `event_name`, `description`, `event_date`, `ticket_price`, `total_tickets`, `tickets_available`) VALUES
(1, 3, 2, 'KL Jazz Night', 'An evening of live jazz with local and international artists.', '2026-10-03 20:00:00', 80.00, 500, 496),
(2, 1, 2, 'Tech Innovators Summit', 'Talks and workshops on AI, cloud and cyber security.', '2026-10-10 09:00:00', 150.00, 300, 299),
(3, 4, 3, 'Malaysia Food Carnival', 'Over 100 food stalls featuring street food from every state.', '2026-10-11 11:00:00', 25.00, 1000, 991),
(4, 2, 3, 'Indie Music Fest', 'A full-day outdoor-style festival with 12 indie bands.', '2027-01-15 18:00:00', 120.00, 800, 800),
(5, 5, 2, 'Startup Pitch Day', 'Startups pitch their ideas to investors and mentors.', '2027-02-10 10:00:00', 60.00, 200, 197),
(6, 3, 3, 'Stand-up Comedy Night', 'Top comedians perform a live stand-up show.', '2026-10-09 20:30:00', 70.00, 400, 398),
(7, 5, 2, 'EventHub Test Concert', 'testtttt', '2026-11-07 14:00:00', 20.00, 100, 100);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `event_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 1, 5, 'Amazing event, well organized and the venue was perfect.', '2026-10-04 01:30:00'),
(2, 6, 1, 4, 'Great lineup, but the entrance queue was a bit long.', '2026-10-05 10:10:00'),
(3, 5, 2, 5, 'Worth every ringgit. Would attend again.', '2026-10-11 00:45:00'),
(4, 5, 3, 3, 'Good overall, though it got very crowded.', '2026-10-12 02:20:00'),
(5, 4, 6, 4, 'Great acts and good value for the ticket price.', '2026-10-10 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','organiser','customer') NOT NULL DEFAULT 'customer',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin EventHub', 'admin@eventhub.com', '$2y$10$8PPl3zvMHWdNcq0sGWHt/.vPUD4wx/9gvLIjUWWCgG3qGulr86Z.a', 'admin', '2026-08-01 09:00:00'),
(2, 'Aiman Hakim', 'aiman@eventhub.com', '$2y$10$x00WOKXPbtGAH5CYzMwhxetIQ7aUqJ8SHMFPK0Nq.gJzfcpbofOt2', 'organiser', '2026-08-02 10:00:00'),
(3, 'Sarah Lim', 'sarah@eventhub.com', '$2b$10$RPAdJ3ypMf9dX5qX1NEpBukON.eWNg0m7v8GdAUm27itIGG1zZQUK', 'organiser', '2026-08-03 11:30:00'),
(4, 'Daniel Tan', 'daniel@gmail.com', '$2b$10$vGjmJ138rpBT1VtVEyBqlOj4Fo7.ZwhLkAuvh0F2uHv02Ro8fsTGa', 'customer', '2026-08-10 14:20:00'),
(5, 'Nur Aisyah', 'aisyah@gmail.com', '$2b$10$YPQOMW3kdYwYbZCXJNaar.diada4VbBC7ZhzLplueeqX8x18i1vem', 'customer', '2026-08-12 16:45:00'),
(6, 'Kumar Rajan', 'kumar@gmail.com', '$2b$10$8CUxCz2Sh3ygoE8oG.gAEur6.89C/h2vxn1VTCw98yUDqgfA.jpry', 'customer', '2026-08-15 09:10:00'),
(7, 'Mei Ling Wong', 'meiling@gmail.com', '$2b$10$bZqbTvnR/SaiaAqJXAI73O78Gm7.DbBrtZdgmogaO6EgD9b4QXq7q', 'customer', '2026-08-18 20:05:00'),
(8, 'Hafiz Ismail', 'hafiz@gmail.com', '$2b$10$1tW7Zu70y5NkQLvGUvBFS.gEn3cJjx60iH.p8wcViHNOJiqbrU.qu', 'customer', '2026-08-20 13:40:00'),
(9, 'joykilla', 'joykilla@example.com', '$2y$10$a5FkwxIxkfUy61.07o9V7uo8BPRV.0wnCrV90Fpmz2aOcMvw/MBXG', 'customer', '2026-10-14 16:35:06'),
(10, 'naim', 'naim@example.com', '$2y$10$uySoWe0KUao25gCuejKkW.JwfAx1lRe0SeXRnDnQq6P0.pdw52DaG', 'customer', '2026-10-14 17:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL,
  `venue_name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `venue_name`, `address`, `capacity`) VALUES
(1, 'Kuala Lumpur Convention Centre', 'Jalan Pinang, 50088 Kuala Lumpur', 5000),
(2, 'Axiata Arena', 'Bukit Jalil, 57000 Kuala Lumpur', 16000),
(3, 'Dewan Filharmonik Petronas', 'Jalan Ampang, 50088 Kuala Lumpur', 800),
(4, 'Mid Valley Exhibition Centre', 'Mid Valley City, 59200 Kuala Lumpur', 3000),
(5, 'Putrajaya International Convention Centre', 'Presint 5, 62000 Putrajaya', 4000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_bookings_customer` (`customer_id`),
  ADD KEY `fk_bookings_event` (`event_id`),
  ADD KEY `idx_bookings_status` (`booking_status`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_events_venue` (`venue_id`),
  ADD KEY `fk_events_organiser` (`organiser_id`),
  ADD KEY `idx_events_date` (`event_date`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_user_event` (`user_id`,`event_id`),
  ADD KEY `fk_reviews_event` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_organiser` FOREIGN KEY (`organiser_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
