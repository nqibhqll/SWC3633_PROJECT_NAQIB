
# EventHub

## Event Ticketing and Venue Booking System

EventHub is a web-based application developed for the Web API Development course. The system allows users to manage events, venues, ticket bookings, and user accounts through a RESTful API.

## Technologies Used
- PHP
- MySQL
- HTML, CSS, JavaScript
- Tabler Framework
- RESTful API
- OpenStreetMap Nominatim
- XAMPP
- Postman

## User Roles

### Administrator
- Manage users
- Manage venues
- Manage events
- View and manage reviews

### Organiser
- Create and manage events
- View event bookings
- View venue locations
- View event reviews

### Customer
- Browse events
- Book event tickets
- View personal bookings
- Manage eligible event reviews

## RESTful API Endpoints
- api/login.php
- api/register.php
- api/logout.php
- api/users.php
- api/events.php
- api/venues.php
- api/bookings.php
- api/reviews.php
- api/venue_location.php

## Installation
1. Install XAMPP.
2. Copy the EventHub project folder into `C:\xampp\htdocs\`.
3. Start Apache and MySQL in XAMPP.
4. Import the provided SQL database using phpMyAdmin.
5. Configure the database connection in `config/database.php`.
6. Open `http://localhost/eventhub/frontend/login.php`.

## API Testing
Postman is used to test RESTful API requests, HTTP status codes, authentication, and error handling.

## Project Information
Course: Web API Development

Project Type: Group Assignment
