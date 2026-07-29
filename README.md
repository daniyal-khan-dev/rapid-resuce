<p align="center">
  <img src="public/assets/user/img/logo/logo.png" alt="Rapid Rescue Logo" width="180"/>
</p>

<h1 align="center">🚑 Rapid Rescue</h1>

<p align="center">
  <strong>A production-quality Laravel 12 Ambulance Dispatch & Ride Management System</strong><br/>
  Real-time ride booking, live driver tracking, and multi-panel dispatch management — powered by Laravel Reverb.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/Reverb-Real--Time-6C63FF?style=flat-square"/>
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square"/>
</p>

---


## 📖 Table of Contents

- [🌐 Live Link](#-live-link)
- [✨ Overview](#-overview)
- [🚀 Features](#-features)
- [⚡ Real-Time Features](#-real-time-features)
- [🛠 Tech Stack](#-tech-stack)
- [📋 Requirements](#-requirements)
- [📥 Installation](#-installation)
- [⚙️ Environment Variables](#️-environment-variables)
- [▶️ Running the Project](#️-running-the-project)
- [📡 Laravel Reverb (Local Development)](#-laravel-reverb-local-development)
- [📂 Project Structure](#-project-structure)
- [🛰 Broadcasting Architecture](#-broadcasting-architecture)
- [🗺 OpenStreetMap](#-openstreetmap)
- [💬 Ride Chat](#-ride-chat)
- [📢 Notifications](#-notifications)
- [❗ Troubleshooting](#-troubleshooting)
- [🔒 Security](#-security)
- [🤝 Contributing](#-contributing)
- [📝 License](#-license)
- [👨‍💻 Author](#-author)
- [⭐ Acknowledgements](#-acknowledgements)

---

## 🌐 Live Link

| Environment | URL |
|---|---|
| **Production** | [https://rapid-rescue.daniyal-khan.com/](https://rapid-rescue.daniyal-khan.com/) |

### 🔐 Login URLs

| Panel | URL |
|---|---|
| 👤 **User** | [https://rapid-rescue.daniyal-khan.com/login](https://rapid-rescue.daniyal-khan.com/login) |
| 🛡️ **Admin** | [https://rapid-rescue.daniyal-khan.com/admin/login](https://rapid-rescue.daniyal-khan.com/admin/login) |
| 🚑 **Driver** | [https://rapid-rescue.daniyal-khan.com/driver/login](https://rapid-rescue.daniyal-khan.com/driver/login) |

---

## ✨ Overview

**Rapid Rescue** is a comprehensive, full-stack ambulance dispatch and ride management platform built with **Laravel 12**. It connects users in need of emergency transport with nearby drivers, dispatched and monitored by admins — all in real time.

> 🎯 **Main Objectives**
> - Provide instant, reliable ambulance booking for users
> - Give drivers a streamlined interface to accept and manage rides
> - Empower admins with full dispatch control, live tracking, and analytics
> - Deliver a seamless real-time experience via Laravel Reverb WebSockets

**Key highlights:**
- Three separate authenticated panels: **User**, **Driver**, and **Admin**
- Full real-time stack: live location, ride chat, status updates, and notifications
- OpenStreetMap integration for pickup/drop-off and live driver tracking
- Event-driven broadcasting with private and public channels
- Email verification, password reset, and medical card support

---

## 🚀 Features

### 👤 User Features

| Feature | Description |
|---|---|
| Authentication | Register, login, email verification, password reset |
| Ride Booking | Submit emergency requests with pickup location and type |
| Live Driver Tracking | Watch driver location update in real time on OpenStreetMap |
| Ride Chat | Message the driver or admin during an active ride |
| Typing Indicator | See when the other party is typing |
| Unread Badge | Visual badge for unread messages |
| Ride History | View past emergency requests and their outcomes |
| Real-Time Status Update | Instant alerts for assignment, status changes, and messages |
| Medical Card | Store personal medical information for emergencies |
| Responsive Interface | Fully mobile-friendly layout |

### 🚗 Driver Features

| Feature | Description |
|---|---|
| Authentication | Secure driver login and profile management |
| Assigned Ride Alerts | Real-time notification when a ride is dispatched |
| Accept / Manage Ride | Accept assignment and progress through ride statuses |
| Live Location Sharing | Broadcast current GPS position to user and admin |
| Ride Chat | Communicate with user or admin mid-ride |
| Typing Indicator | Real-time typing status |
| Ride History | Review completed and past rides |
| Dashboard | Summary of active and historical activity |

### 🛡 Admin Features

| Feature | Description |
|---|---|
| Dashboard | Overview of active rides, drivers, and system stats |
| Ride Management | View, assign, and manage all emergency requests |
| User Management | Browse, monitor, and manage registered users |
| Driver Management | Register, activate, and monitor driver accounts |
| Ambulance Management | Track fleet, assign units to drivers |
| Branch Management | Manage service branches and coverage areas |
| Real-Time Ride Tracking | Live map view of all active dispatch locations |
| Ride Chat | Monitor or participate in any active ride conversation |
| Contact Message | View, manage, and reply to enquiries submitted by users and visitors |
| Live Notifications | Instant alerts across all admin activity |
| Typing Indicators & Badges | See active conversations and unread counts |
| Reports & Analytics | Ride logs, feedback, contact messages, and visitor tracking |
| Services, Testimonials, FAQs | Manage public-facing content |

---

## ⚡ Real-Time Features

Rapid Rescue uses **[Laravel Reverb](https://reverb.laravel.com/)** — Laravel's first-party WebSocket server — to power all real-time functionality natively, without third-party dependencies like Pusher.

### What's powered by Reverb

- **Ride Chat** — instant message delivery between user, driver, and admin
- **Live Driver Location** — GPS coordinates broadcast continuously during active rides
- **Ride Status Changes** — UI updates the moment a driver accepts, starts, or completes a ride
- **Ride Assignment Updates** — users and drivers are notified the instant a ride is dispatched
- **Typing Indicators** — real-time "is typing…" signals across all chat participants
- **Read Receipts** — message read state synced across panels
- **Unread Badge Counts** — navbar badges update live without page refresh
- **Admin Dashboard Activity** — new ride requests, content changes, and user updates surface instantly (`AdminContentUpdate` → `admin-dashboard` channel)
- **Emergency / Ride Events** — core emergency request lifecycle broadcast over private, presence, and public channels (`Emergency` event)
- **Driver Status Updates** — driver availability and assignment changes pushed to all relevant panels (`DriverUpdated` → `drivers-update` channel)
- **User Profile Changes** — registration, verification, and profile updates reflected live on the admin dashboard (`UserUpdated` → `admin-dashboard` channel)
- **Live Visitor Tracking** — new site visitors appear in real time on the admin dashboard (`VisitorLogCreated` → `admin-dashboard` channel)
- **Contact Form Notifications** — admin is notified instantly when a user submits a contact message (`ContactRealtime` event)
- **Ride & Driver Log History** — log entries broadcast as they are created for live activity feeds (`LogHistoryCreated` event)
- **Channel Authorization** — private and presence channels enforce guard-based access control

### Why Reverb?

> Reverb runs as a native Laravel WebSocket server — no external service, no per-message billing, no vendor lock-in. It integrates directly with Laravel's broadcasting system, supports private and presence channels, and scales with your existing PHP infrastructure.

---

## 🛠 Tech Stack

### Backend
- **[Laravel 12](https://laravel.com/)** — application framework
- **PHP 8.2+** — runtime
- **MySQL** — primary database

### Frontend
- **Blade** — server-side templating
- **JavaScript (Vanilla + Laravel Echo)** — client-side interactivity
- **Bootstrap** — UI components and layout
- **Vite** — frontend asset bundling

### Real-Time
- **[Laravel Reverb](https://reverb.laravel.com/)** — WebSocket server
- **[Laravel Echo](https://github.com/laravel/echo)** — client-side event listener
- **Pusher JS** — Echo transport layer (used with Reverb)

### Maps
- **[OpenStreetMap](https://www.openstreetmap.org/)** — free, open map tiles for driver tracking, markers, and route display

### Development Tools
- **Composer** — PHP dependency management
- **Git** — version control

---

## 📋 Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Git | Any recent version |

---

## 📥 Installation

### 1. Clone the repository

```bash
git clone https://github.com/daniyal-khan-dev/rapid-rescue.git
cd rapid-rescue
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Copy the environment file

```bash
cp .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure your database

Open `.env` and set your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapidrescue
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 6. Run database migrations

```bash
php artisan migrate
```

### 7. Seed the database *(optional)*

```bash
php artisan db:seed
```

### 8. Create the storage symlink

```bash
php artisan storage:link
```

---

## ⚙️ Environment Variables

Copy `.env.example` to `.env` and fill in the values below. **Never commit real secrets to version control.**

### Application

| Variable | Example | Description |
|---|---|---|
| `APP_NAME` | `Rapid Rescue` | Application name |
| `APP_ENV` | `local` | Environment (`local`, `production`) |
| `APP_KEY` | `base64:...` | Generated by `php artisan key:generate` |
| `APP_DEBUG` | `true` | Show debug errors (disable in production) |
| `APP_URL` | `http://localhost` | Base URL of your application |

### Database

| Variable | Example |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `rapidrescue` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | `your_password` |

### Mail

| Variable | Example |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `465` |
| `MAIL_USERNAME` | `you@example.com` |
| `MAIL_PASSWORD` | `your_password` |
| `MAIL_ENCRYPTION` | `ssl` |
| `MAIL_FROM_ADDRESS` | `no-reply@example.com` |
| `MAIL_FROM_NAME` | `Rapid Rescue` |

### Broadcasting & Queue

| Variable | Value |
|---|---|
| `BROADCAST_CONNECTION` | `reverb` |
| `QUEUE_CONNECTION` | `database` |

### Laravel Reverb

| Variable | Example |
|---|---|
| `REVERB_APP_ID` | `970154` |
| `REVERB_APP_KEY` | `afomzfjvsez6s2dzhruj` |
| `REVERB_APP_SECRET` | `9fs5mkpfcmftagwgjgny` |
| `REVERB_HOST` | `localhost` |
| `REVERB_PORT` | `8080` |
| `REVERB_SCHEME` | `http` |

### Vite / Frontend

| Variable | Value |
|---|---|
| `VITE_REVERB_APP_KEY` | `${REVERB_APP_KEY}` |
| `VITE_REVERB_HOST` | `${REVERB_HOST}` |
| `VITE_REVERB_PORT` | `${REVERB_PORT}` |
| `VITE_REVERB_SCHEME` | `${REVERB_SCHEME}` |

### RECAPTCHA

| Variable | Example |
|---|---|
| `RECAPTCHA_SITE_KEY` | `AIza...your-key` |
| `RECAPTCHA_SECRET_KEY` | `AIza...your-key` |

---

## ▶️ Running the Project

### Start the Laravel development server

```bash
php artisan serve
```

> This starts the application at `http://127.0.0.1:8000`. The server handles all HTTP routes for the User, Driver, and Admin panels.

---

## 📡 Laravel Reverb (Local Development)

For real-time features (chat, live tracking, notifications) you must run the Reverb WebSocket server alongside the application.

### Start the Reverb server

```bash
php artisan reverb:start
```

> Run this in a **separate terminal**. It starts the WebSocket server on the host and port configured in your `.env` (`REVERB_HOST` / `REVERB_PORT`). Keep it running for the duration of your development session.

**Summary of terminals needed for full local development:**

| Terminal | Command | Purpose |
|---|---|---|
| 1 | `php artisan serve` | HTTP application server |
| 2 | `php artisan reverb:start` | WebSocket server |

---

## 📂 Project Structure

```
rapid-rescue/
├── app/
│   ├── Events/          # Laravel broadcast events (AdminContentUpdate, DriverUpdated, Emergency, …)
│   ├── Http/
│   │   ├── Controllers/ # Admin/, Driver/, User/ panel controllers
│   │   └── Middleware/  # Auth guards, cache, visitor tracking
│   ├── Mail/            # Mailable classes (verification, password reset, contact replies)
│   ├── Models/          # Eloquent models (User, Driver, EmergencyRequest, RideChatMessage, …)
│   ├── Providers/       # Service providers
│   └── Services/        # Business logic services
├── bootstrap/           # Application bootstrap and middleware configuration
├── config/              # Laravel configuration files (reverb, services, broadcasting, …)
├── database/
│   ├── migrations/      # All database schema migrations
│   └── seeders/         # Database seeders
├── public/
│   └── assets/          # Compiled CSS, JS, and images (admin, driver, user)
├── resources/
│   └── views/           # Blade templates (Admin/, Driver/, User/ layouts and pages)
├── routes/
│   ├── web.php          # All HTTP routes (public, user, driver, admin panels)
│   ├── channels.php     # Broadcasting channel authorization
│   └── console.php      # Artisan console commands
├── storage/             # Logs, cache, compiled views, uploaded files
└── tests/               # Feature and unit tests
```

---

## 🛰 Broadcasting Architecture

Rapid Rescue uses Laravel's **event broadcasting** system with Reverb as the WebSocket driver and Laravel Echo on the client side.

### How it works

1. **Events** — PHP event classes (e.g. `AdminContentUpdate`, `DriverUpdated`, `Emergency`) implement `ShouldBroadcast`. When fired, Laravel serializes the event payload and pushes it to the configured broadcast driver (Reverb).

2. **Channels** — defined in `routes/channels.php`, channels control who can subscribe:
   - **Public channels** — open to any connected client (e.g. `drivers-update`, `emergency.{requestId}`)
   - **Private channels** — require authentication (e.g. `user.bookings.{id}`, `user.{userId}`, `driver.{driverId}`)
   - **Admin channels** — gate-checked against the `admin` guard (e.g. `contact.admin`, `admin-dashboard`)

3. **Reverb** — receives the broadcast from Laravel and pushes it over WebSocket to all subscribed clients.

4. **Laravel Echo** — the JavaScript client listens on named channels and reacts to incoming events, updating the UI in real time without polling.

### Broadcast events in this project

| Event | Channel | Who listens |
|---|---|---|
| `AdminContentUpdate` | `admin-dashboard` | Admin panel |
| `ContactRealtime` | `contact.user.{id}`, `contact.admin` | User, Admin |
| `DriverUpdated` | `drivers-update` | Admin, User tracking |
| `Emergency` | `emergency.{requestId}` | User, Admin |
| `UserUpdated` | `user.bookings.{id}` | User panel |
| `LogHistoryCreated` | Admin panel log history |
| `VisitorLogCreated` | Admin panel Visitor Log |

---

## 🗺 OpenStreetMap

OpenStreetMap is integrated throughout the application to provide location-aware dispatch and tracking — no API key or billing account required.

- **Pickup & Destination Markers** — users set their pickup point on an interactive map when booking
- **Live Driver Location** — driver GPS coordinates are broadcast via Reverb and rendered as a moving marker on the user's and admin's map
- **Route Visualization** — the expected route between pickup and destination is drawn on the map
- **Admin Dispatch View** — admins see all active ride locations on a single map for centralized monitoring
- **Reverse Geocoding** — coordinates are translated into human-readable addresses throughout the UI using the [Nominatim](https://nominatim.org/) geocoding API

> OpenStreetMap tiles and Nominatim are free and open — no API key is needed for standard usage. For production deployments with high traffic, consider self-hosting tiles or using a hosted OSM tile provider.

---

## 💬 Ride Chat

Every active emergency ride includes a full real-time chat system connecting all parties.

- **User ↔ Driver** — direct messaging during an active ride
- **User ↔ Admin** — support channel for the user
- **Driver ↔ Admin** — dispatch coordination channel
- **Typing Indicators** — live "is typing…" feedback visible to all participants
- **Read Receipts** — messages marked as read when the recipient views them
- **Unread Badge Counts** — navbar badges update instantly as new messages arrive
- **Chat History** — full message history persisted and accessible after ride completion
- **Status-Gated Access** — chat is only available while the ride is in an active state

Chat messages are stored in the `ride_chat_messages` table and broadcast via the `Emergency` event to private per-user and per-driver channels.

---

## 📢 Notifications

Real-time notifications are delivered instantly across all three panels via Reverb broadcasting.

| Trigger | Who is notified |
|---|---|
| New emergency request submitted | Admin panel (dashboard badge + alert) |
| Ride assigned to driver | Driver (assignment alert) + User (confirmation) |
| Driver accepts ride | User (live update) + Admin |
| Ride status changed | User + Admin (status badge update) |
| New chat message received | Recipient (navbar badge + in-chat update) |
| Driver location updated | User tracking map + Admin dispatch map |
| Contact message received | Admin panel notification |

Notifications update the UI — including navbar badges and status indicators — without requiring a page refresh.

---

## ❗ Troubleshooting

### Reverb not connecting
- Ensure `php artisan reverb:start` is running in a separate terminal
- Confirm `REVERB_HOST`, `REVERB_PORT`, and `REVERB_SCHEME` in `.env` match your setup

### Echo not receiving events
- Verify `BROADCAST_CONNECTION=reverb` in `.env`
- Run `php artisan config:clear` after changing `.env`
- Check browser console for WebSocket connection errors

### Queue not processing
- Start `php artisan queue:work` in a separate terminal
- Ensure `QUEUE_CONNECTION=database` and the `jobs` table exists (run migrations)

### APP_KEY missing or invalid
- Run `php artisan key:generate` to generate a fresh key
- Never share or commit your `APP_KEY`

### Broadcasting not working
- Confirm `config/broadcasting.php` has `reverb` as the default driver
- Check that the `REVERB_APP_KEY` and `REVERB_APP_SECRET` are consistent between `.env` and the Reverb server config

### Map not loading
- Check browser console for tile loading errors (network or CORS issues)
- Ensure the Leaflet (or equivalent OSM library) JS/CSS is correctly loaded
- Verify Nominatim geocoding requests are not being rate-limited (max 1 req/sec for the public API)

---

## 🔒 Security

- **Never commit `.env`** — it contains your app key, database credentials, and API keys
- **Protect your `APP_KEY`** — it is used to encrypt sessions and signed URLs; rotate it if exposed
- **Respect OSM usage policy** — for high-traffic production use, self-host tiles or use a commercial OSM tile provider instead of the public tile servers
- **Validate all inputs** — the application uses Laravel's built-in form request validation
- **Use HTTPS in production** — required for secure WebSocket (`wss://`) connections and cookie security
- **Keep dependencies updated** — run `composer update` regularly to patch vulnerabilities

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork** the repository
2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Commit your changes** with a clear message
   ```bash
   git commit -m "feat: add your feature description"
   ```
4. **Push** to your branch
   ```bash
   git push origin feature/your-feature-name
   ```
5. **Open a Pull Request** against the `main` branch and describe your changes

Please ensure your code follows PSR-12 coding standards and includes relevant tests where applicable.

---

## 👨‍💻 Author

**Daniyal Khan**

- 🐙 GitHub: [@daniyal-khan-dev](https://github.com/daniyal-khan-dev)
- 💼 LinkedIn: [linkedin.com/in/m-daniyal-khan](https://linkedin.com/in/m-daniyal-khan)
- 📧 Email: support@daniyal-khan.com

---

## ⭐ Acknowledgements

- [Laravel](https://laravel.com/) — for the elegant PHP framework that makes all of this possible
- [Laravel Reverb](https://reverb.laravel.com/) — for native, first-party WebSocket broadcasting
- [OpenStreetMap](https://www.openstreetmap.org/) — for powering real-time location features with free, open map data
- The open source community — for the countless packages and tools this project builds upon

<div align="center">
  <h3>🌟 If you found this project helpful, please give it a star! 🌟</h3>
  
  [![Live Demo](https://img.shields.io/badge/View%20Live%20Demo-Rapid%20Rescue-red?style=for-the-badge)](https://rapid-rescue.daniyal-khan.com/)
  
  <img src="public/assets/user/img/website-ss/rapid-rescue.png" alt="Rapid Rescue" width="400px" style="border-radius: 10px; margin-top: 20px;">
</div>
