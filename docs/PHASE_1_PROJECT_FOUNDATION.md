# Phase 1: Project Foundation Analysis

## 1. System Overview

### Project Information
- **Project Name**: Lernen LMS (Learning Management System)
- **Version**: 3.0.9
- **Type**: Tutoring/Learning Platform with Session Booking System
- **License**: MIT

### Technology Stack

#### Backend
- **Laravel Version**: 11.9+ (Latest Laravel 11.x)
- **PHP Version**: ^8.2 (PHP 8.2 or higher required)
- **Architecture Pattern**: MVC with Livewire Components
- **Database**: MySQL/MariaDB/PostgreSQL/SQLite (Multi-database support)
- **ORM**: Eloquent
- **Default Connection**: SQLite (configurable via `DB_CONNECTION`)

#### Frontend
- **Primary Framework**: Livewire 3.5 (Full-stack framework)
- **CSS Framework**: Tailwind CSS 3.1.0
- **Build Tool**: Vite 5.0
- **JavaScript**: Vanilla JS + jQuery 3.7.1
- **Template Engine**: Blade

#### Real-time & Broadcasting
- **WebSocket Server**: Laravel Reverb 1.2
- **Broadcasting**: Pusher Protocol compatible
- **Chat System**: LaraGuppy (Custom messaging system)

---

## 2. Dependencies Analysis

### Core Laravel Packages

#### Authentication & Authorization
```json
"laravel/sanctum": "^4.0"           // API authentication
"spatie/laravel-permission": "^6.9"  // Role & Permission management
"laravel/socialite": "^5.16"        // Social login (Google, etc.)
"socialiteproviders/google": "^4.1" // Google OAuth provider
```

#### Payment Gateways
```json
"stripe/stripe-php": "^10.21"       // Stripe integration
"razorpay/razorpay": "^2.9.0"       // Razorpay integration
"iyzico/iyzipay-php": "^2.0"        // Iyzico payment (Turkey)
"paytm/paytmchecksum": "*"          // PayTM integration (India)
```

#### File & Media Management
```json
"intervention/image-laravel": "^1.3"    // Image manipulation
"league/flysystem-aws-s3-v3": "^3.0"    // S3 storage support
"spatie/browsershot": "^5.0"            // PDF generation (uses Puppeteer)
"barryvdh/laravel-dompdf": "^3.1"       // PDF generation alternative
```

#### Third-party Integrations
```json
"google/apiclient": "^2.16"             // Google Calendar, Meet, Drive
"openai-php/laravel": "^0.10.2"         // OpenAI integration (AI features)
"stichoza/google-translate-php": "^5.2" // Translation API
```

#### Data Processing
```json
"maatwebsite/excel": "^3.1"         // Excel import/export
"phpoffice/phpspreadsheet": "^1.29" // Spreadsheet manipulation
"mews/purifier": "^3.4"             // HTML sanitization
```

#### Custom Packages (Local)
```json
"larabuild/optionbuilder": "^1.1"       // Settings/Options builder
"larabuild/pagebuilder": "^1.4"         // Page builder system
"amentotech/laraguppy": "^1.0"          // Chat system
"amentotech/laravel-installer": "^11.0" // Application installer
"amentotech/scssphp": "^1.11"           // SCSS compiler
```

#### Modules & Extensions
```json
"nwidart/laravel-modules": "^11.1"  // Modular architecture support
```

#### Development Tools
```json
"laravel/telescope": "^5.7"         // Debugging & monitoring
"laravel/breeze": "^2.1"            // Authentication scaffolding
"laravel/pint": "^1.13"             // Code style fixer
"phpunit/phpunit": "^11.0.1"        // Testing framework
```

### NPM Dependencies

#### Build & Development
```json
"vite": "^5.0"                      // Build tool
"laravel-vite-plugin": "^1.0"       // Laravel Vite integration
"autoprefixer": "^10.4.2"           // CSS autoprefixer
"postcss": "^8.4.31"                // CSS processor
"tailwindcss": "^3.1.0"             // Utility-first CSS
"@tailwindcss/forms": "^0.5.2"      // Form plugin for Tailwind
```

#### Frontend Libraries
```json
"axios": "^1.6.4"                   // HTTP client
"jquery": "^3.7.1"                  // DOM manipulation
"puppeteer": "^23.5.1"              // Headless browser (PDF generation)
"puppeteer-core": "^23.5.2"         // Puppeteer core
```

---

## 3. Directory Structure & Purpose

### Application Directory (`app/`)

```
app/
├── Casts/              → Custom Eloquent attribute casters
│                        (UserStatusCast, BookingStatus, OrderStatusCast, etc.)
├── Console/            → Artisan commands
├── Exceptions/         → Custom exception handlers
├── Exports/            → Excel export classes (Maatwebsite)
├── Facades/            → Custom facade classes
├── Helpers/            → Helper functions (helpers.php)
├── Http/               → HTTP layer
│   ├── Controllers/    → Request handlers
│   │   ├── Admin/      → Admin panel controllers
│   │   ├── Api/        → API endpoints (REST)
│   │   ├── Auth/       → Authentication controllers
│   │   └── Frontend/   → Public-facing controllers
│   ├── Middleware/     → Request/response filters
│   └── Requests/       → Form validation classes
├── Jobs/               → Queue jobs (async processing)
├── Listeners/          → Event listeners
├── Livewire/           → Livewire components
│   ├── Components/     → Reusable UI components
│   ├── Header/         → Header components
│   └── Pages/          → Full-page components
│       ├── Admin/      → Admin pages
│       ├── Common/     → Shared between roles
│       ├── Student/    → Student-specific pages
│       ├── Tutor/      → Tutor-specific pages
│       └── Auth/       → Authentication pages
├── Models/             → Eloquent models (47 models)
├── Notifications/      → Notification classes
├── Observers/          → Model observers
├── Optionbuilder/      → Settings/options management
├── Providers/          → Service providers
├── Services/           → Business logic layer (26 services)
├── Spotlight/          → Command palette integration
├── Traits/             → Reusable traits
└── View/               → View composers
```

### Key Model Entities (Core Business Logic)

**User Management**
- `User` - Core user model (students, tutors, admins)
- `Profile` - User profile data
- `Role` - User roles (via Spatie)
- `SocialProfile` - Social auth profiles

**Tutoring System**
- `Subject` - Available subjects for teaching
- `SubjectGroup` - Subject categories
- `UserSubjectGroup` - Tutor's subject assignments
- `UserSubjectGroupSubject` - Pivot for subjects within groups
- `UserSubjectSlot` - Tutor's availability slots

**Booking & Sessions**
- `SlotBooking` - Session bookings (core transaction)
- `BookingLog` - Booking history/changes
- `Order` - Payment orders
- `OrderItem` - Order line items (polymorphic)

**Payments & Wallet**
- `BillingDetail` - Student billing info
- `UserWallet` - Tutor earnings wallet
- `UserWalletDetail` - Wallet transaction log
- `UserPayoutMethod` - Tutor payout preferences
- `UserWithdrawal` - Withdrawal requests

**Reviews & Ratings**
- `Rating` - Session ratings (polymorphic)

**Content Management**
- `Blog` - Blog posts
- `BlogCategory` - Blog categories
- `BlogTag` - Blog tags
- `Menu` - Navigation menus
- `MenuItem` - Menu items

**Verification & Trust**
- `UserIdentityVerification` - ID verification
- `UserCertificate` - Tutor certificates
- `UserEducation` - Education history
- `UserExperience` - Work experience

**Disputes & Support**
- `Dispute` - Booking disputes
- `DisputeConversation` - Dispute messages

**Localization**
- `Language` - Supported languages
- `Country` - Countries
- `CountryState` - States/provinces
- `Address` - User addresses (polymorphic)

---

## 4. Configuration Files Analysis

### Core Configuration

#### `config/app.php`
- **App Name**: `Lernen`
- **Default Environment**: `production`
- **Debug Mode**: Controlled by `APP_DEBUG` (default: false)
- **Timezone**: `UTC` (default)
- **Locale**: `en` (default)

#### `config/auth.php`
- **Default Guard**: `web` (session-based)
- **User Provider**: Eloquent (`App\Models\User`)
- **Password Reset**:
  - Table: `password_reset_tokens`
  - Expiry: 60 minutes
  - Throttle: 60 seconds

#### `config/database.php`
- **Default Connection**: `sqlite` (can be changed to mysql, mariadb, pgsql)
- **Supported Databases**:
  - SQLite (development)
  - MySQL/MariaDB (production recommended)
  - PostgreSQL
  - SQL Server
- **Foreign Key Constraints**: Enabled by default

#### `config/filesystems.php`
- **Default Disk**: `local`
- **Supported Disks**:
  - `local` - Local storage
  - `public` - Publicly accessible storage
  - `s3` - AWS S3 bucket

#### `config/queue.php`
- **Default Queue**: `database` (can be redis, sync, etc.)
- **Queue Jobs**: Asynchronous processing (emails, notifications, heavy tasks)

#### `config/mail.php`
- **Default Mailer**: SMTP (configurable)
- **Supported Mailers**:
  - SMTP
  - Mailgun
  - SES
  - Postmark

#### Custom Configuration Files

**`config/permission.php`** - Spatie Permission
- Role and permission management
- Cache configuration
- Model paths

**`config/laraguppy.php`** - Chat System
- Chat configuration
- Real-time messaging settings

**`config/openai.php`** - AI Features
- OpenAI API configuration
- Model selection

**`config/zoom.php`** - Video Integration
- Zoom meeting integration

**`config/optionbuilder.php`** - Settings Management
- Dynamic settings system

**`config/pagebuilder.php`** - Page Builder
- Page builder configuration

**`config/translations.php`** - Multi-language
- Translation management

**`config/reverb.php`** - WebSocket Server
- Real-time broadcasting configuration

---

## 5. Namespace Conventions

### PSR-4 Autoloading
```php
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\": "Modules/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    },
    "files": [
        "app/Helpers/helpers.php"  // Global helper functions
    ]
}
```

### Naming Conventions

#### Models
- Singular, PascalCase
- Example: `User`, `SlotBooking`, `UserSubjectGroup`

#### Controllers
- PascalCase with `Controller` suffix
- Example: `SearchController`, `SiteController`

#### Livewire Components
- PascalCase, nested namespaces
- Example: `App\Livewire\Pages\Tutor\ManageAccount`

#### Database Tables
- Plural, snake_case
- Example: `users`, `slot_bookings`, `user_subject_groups`

#### Migrations
- Format: `YYYY_MM_DD_HHMMSS_description`
- Example: `2024_07_18_112410_create_slot_bookings_table.php`

---

## 6. Environment Variables Structure

### Required Variables (Critical)

```env
# Application
APP_NAME=Lernen
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lernen
DB_USERNAME=root
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database

# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=http

# Filesystem
FILESYSTEM_DISK=local
```

### Optional Variables (Third-party Services)

```env
# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# Payment Gateways
STRIPE_KEY=
STRIPE_SECRET=
RAZORPAY_KEY=
RAZORPAY_SECRET=

# OpenAI
OPENAI_API_KEY=
OPENAI_ORGANIZATION=

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Zoom
ZOOM_CLIENT_ID=
ZOOM_CLIENT_SECRET=
```

---

## 7. Module System (Modular Architecture)

### Modules Directory
```
Modules/
├── LaraPayease/    → Payment processing module
├── MeetFusion/     → Video meeting integration
└── [Future modules can be added]
```

### Module Activation
- Modules are tracked in `modules_statuses.json`
- Enabled/disabled via admin panel
- Support hot-swapping without code changes

---

## 8. Custom Scripts (Composer)

### Development Script
```bash
composer dev
```
**Runs concurrently**:
1. `php artisan serve` - Development server (port 8000)
2. `php artisan queue:listen` - Queue worker
3. `php artisan reverb:start` - WebSocket server (port 8080)
4. `npm run dev` - Vite dev server (HMR)

---

## 9. Key Features Identified

### User Roles
1. **Admin** - System administration
2. **Sub Admin** - Limited admin access
3. **Tutor** - Provides tutoring sessions
4. **Student** - Books and attends sessions

### Core Functionality
- **Session Booking System** - Students book tutors by time slots
- **Multi-subject Support** - Tutors can teach multiple subjects
- **Payment Processing** - Multiple payment gateways
- **Wallet System** - Tutor earnings management
- **Rating & Reviews** - Session feedback system
- **Dispute Management** - Resolve booking conflicts
- **Identity Verification** - Trust & safety
- **Multi-language** - Translation support
- **Real-time Chat** - LaraGuppy messaging
- **Video Meetings** - Google Meet / Zoom integration
- **AI Integration** - OpenAI features
- **Blog System** - Content management
- **Certificate Generation** - Completion certificates
- **Invoice System** - Automated billing

---

## 10. PSR Standards Compliance

- **PSR-4**: Autoloading (fully compliant)
- **PSR-12**: Extended coding style (enforced via Laravel Pint)
- **PSR-7**: HTTP messages (via Symfony HttpFoundation)
- **PSR-18**: HTTP client (via Guzzle/Symfony)

---

## 11. Package Repositories (Local Development)

The system uses local package repositories for custom packages:

```json
"repositories": {
    "optionbuilder": { "type": "path", "url": "packages/larabuild/optionbuilder" },
    "pagebuilder": { "type": "path", "url": "packages/larabuild/pagebuilder" },
    "scssphp": { "type": "path", "url": "packages/scssphp" },
    "laraguppy": { "type": "path", "url": "packages/laraguppy" },
    "laravel-installer": { "type": "path", "url": "packages/laravel-installer" }
}
```

These packages are developed in-house and symlinked during development.

---

## Summary

**Lernen LMS** is a comprehensive tutoring platform built on Laravel 11 with a modular architecture. It supports multiple payment gateways, real-time features, multi-language, and role-based access control. The system is designed for scalability with queue-based processing, WebSocket support, and cloud storage integration.

**Statistics**:
- 49 Models
- 27 Controllers
- 26 Services
- 67+ Database Migrations
- 47 Eloquent Models
- Multi-role system (Admin, Sub Admin, Tutor, Student)
- Modular package system
- Real-time capabilities (Reverb, LaraGuppy)

---

**Next**: Phase 2 - Database Architecture Deep-Dive
