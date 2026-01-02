# CODEBASE MAP - LERNEN LMS

## Quick Navigation Guide

This document provides a comprehensive map of the Lernen LMS codebase for rapid navigation and understanding.

---

## Core Directories

### `/app` - Application Logic

#### `/app/Console`
**Purpose**: Custom Artisan commands
```
Commands/
├── DeletePendingOrders.php          # Clean up unpaid orders
├── EnableAllModules.php             # Bulk module activation
├── SpecificDefaultSettingSeederCommand.php
└── UpgradeDatabaseCommand.php       # Database upgrade utility
```

#### `/app/Http/Controllers`
**Purpose**: HTTP request handling (80+ controllers)

**Structure**:
```
Controllers/
├── Admin/                           # Admin panel controllers (30+)
│   ├── BlogController.php          # Blog management
│   ├── BookingController.php       # Booking oversight
│   ├── CategoryController.php      # Category CRUD
│   ├── DashboardController.php     # Admin dashboard
│   ├── OrderController.php         # Order management
│   ├── PageController.php          # PageBuilder
│   ├── PayoutController.php        # Payout processing
│   ├── SettingController.php       # System settings
│   ├── UserController.php          # User management
│   └── ...
├── Api/                            # API endpoints
│   ├── NotificationController.php  # Notification API
│   └── ...
├── Auth/                           # Authentication
│   ├── AuthenticatedSessionController.php
│   ├── EmailVerificationNotificationController.php
│   ├── NewPasswordController.php
│   ├── PasswordResetLinkController.php
│   ├── RegisteredUserController.php
│   └── VerifyEmailController.php
├── Common/                         # Shared controllers
│   ├── BookingController.php      # Booking operations
│   ├── DashboardController.php    # User dashboard
│   ├── NotificationController.php # Notifications
│   └── ProfileController.php      # Profile management
├── Frontend/                       # Public-facing
│   ├── BlogController.php         # Blog display
│   ├── CheckoutController.php     # Checkout flow
│   ├── HomeController.php         # Homepage
│   └── TutorController.php        # Tutor listings
└── ...
```

**Key Controllers**:
- `Admin/DashboardController.php` - Admin analytics & overview
- `Common/BookingController.php` - Booking CRUD for tutors/students
- `Frontend/CheckoutController.php` - Payment processing
- `Auth/*` - Complete authentication flow

#### `/app/Http/Middleware`
**Purpose**: Request filtering (7 custom middleware)
```
Middleware/
├── CheckLocale.php                 # Set application language
├── CheckMaintenanceMode.php        # Maintenance mode check
├── CheckModuleEnabled.php          # Verify module is active
├── PermitOfMiddleware.php          # Permission-based access
├── RoleMiddleware.php              # Role-based access control
├── RedirectIfAuthenticated.php     # Guest-only routes
└── UserOnline.php                  # Track user online status
```

**Usage**:
- `role:admin` - Require admin role
- `role:admin|tutor` - Require admin OR tutor
- `permit-of:manage users` - Require specific permission
- `locale` - Set locale from user preferences
- `maintenance` - Block access in maintenance mode
- `enabled:ModuleName` - Require module enabled

#### `/app/Http/Requests`
**Purpose**: Form validation (45+ request classes)
```
Requests/
├── Admin/
│   ├── StoreBlogRequest.php
│   ├── StoreUserRequest.php
│   ├── UpdateSettingsRequest.php
│   └── ...
├── StoreBookingRequest.php
├── UpdateProfileRequest.php
└── ...
```

**Pattern**:
```php
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'slot_id' => 'required|exists:slots,id',
            'tutor_id' => 'required|exists:users,id',
        ];
    }
}
```

#### `/app/Livewire`
**Purpose**: Reactive UI components (78+ components)

**Structure**:
```
Livewire/
├── Actions/                        # Action handlers
│   └── Logout.php
├── Components/                     # Reusable widgets
│   ├── Courses.php                # Course listing
│   ├── SearchTutor.php            # Tutor search with filters
│   ├── SimilarTutors.php          # Recommendations
│   ├── StudentsReviews.php        # Review display
│   ├── TutorResume.php            # Tutor profile card
│   └── TutorSessions.php          # Session history
├── Forms/                          # Form objects
│   ├── LoginForm.php              # Login validation
│   ├── OrderForm.php              # Checkout form
│   └── ...
├── Frontend/                       # Public components
│   ├── BlogDetails.php            # Single blog view
│   ├── Blogs.php                  # Blog listing
│   ├── Checkout.php               # Payment flow
│   └── ThankYou.php               # Success page
├── Pages/                          # Full-page components
│   ├── Admin/                     # Admin panel (35+)
│   │   ├── Blogs/
│   │   ├── Bookings/
│   │   ├── Categories/
│   │   ├── Dashboard.php
│   │   ├── Notifications/
│   │   ├── Orders/
│   │   ├── Payouts/
│   │   ├── Settings/
│   │   ├── Users/
│   │   └── ...
│   ├── Common/                    # Shared pages (12+)
│   │   ├── Bookings/
│   │   ├── Dashboard.php
│   │   ├── Navigation.php
│   │   ├── Notifications/
│   │   └── Profile/
│   ├── Student/                   # Student pages (15+)
│   │   ├── Bookings/
│   │   ├── Dashboard.php
│   │   └── ...
│   └── Tutor/                     # Tutor pages (16+)
│       ├── Availability/
│       ├── Bookings/
│       ├── Dashboard.php
│       ├── Earnings/
│       └── ...
├── ExperiencedTutors.php
└── Payouts.php
```

**Key Components**:
- `Components/SearchTutor.php` - Main tutor search (filters, pagination, favorites)
- `Frontend/Checkout.php` - Complete checkout flow
- `Pages/Admin/Dashboard.php` - Admin analytics
- `Pages/Common/Navigation.php` - Sidebar navigation
- `Forms/OrderForm.php` - Checkout form with validation

#### `/app/Models`
**Purpose**: Eloquent ORM models (50+ models)

**Core Models**:
```
Models/
├── User.php                        # Core user model
├── Profile.php                     # Extended user info
├── UserAccountSetting.php          # JSON settings
├── Slot.php                        # Tutor availability
├── SlotBooking.php                 # Confirmed sessions
├── SlotBookingItem.php             # Booking line items
├── SlotBookingHistory.php          # Status changes
├── Order.php                       # Payment transactions
├── Wallet.php                      # User balances
├── WalletHistory.php               # Transaction log
├── Payout.php                      # Withdrawal requests
├── PayoutMethod.php                # Payment accounts
├── Blog.php                        # Blog posts
├── BlogCategory.php                # Blog taxonomy
├── Page.php                        # PageBuilder pages
├── Menu.php                        # Navigation menus
├── Category.php                    # General categories
├── Notification.php                # Database notifications
├── NotificationSetting.php         # User preferences
└── ...
```

**Key Relationships**:
- `User` → `hasOne(Profile)`, `hasMany(SlotBookings)`, `hasOne(Wallet)`
- `Slot` → `belongsTo(User)`, `hasMany(SlotBookings)`
- `SlotBooking` → `belongsTo(Slot)`, `belongsTo(User as tutor)`, `belongsTo(User as booker)`
- `Order` → `belongsTo(User)`, `hasMany(OrderItems)`

#### `/app/Services`
**Purpose**: Business logic layer (35+ services)

**Core Services**:
```
Services/
├── BookingService.php              # Booking lifecycle & video meeting
├── UserService.php                 # User CRUD & profile
├── SlotService.php                 # Availability management
├── OrderService.php                # Payment orders
├── WalletService.php               # Balance & transactions
├── PayoutService.php               # Withdrawals
├── CartService.php                 # Shopping cart
├── NotificationService.php         # Email dispatch
├── SiteService.php                 # Menus, pages, settings
├── OptionBuilderService.php        # Dynamic settings
├── BillingService.php              # Billing info
├── EarningService.php              # Revenue calculations
├── ZoomService.php                 # Zoom API integration
├── GoogleCalender.php              # Google Calendar sync
├── BlogService.php                 # Blog operations
├── CategoryService.php             # Category management
├── PageService.php                 # Page operations
├── PermissionService.php           # Role/permission logic
├── MediaService.php                # File uploads
└── ...
```

**Service Responsibilities**:
- Complex business logic
- Multi-model coordination
- External API calls
- Transaction management
- Cache operations
- Event dispatching

#### `/app/Notifications`
**Purpose**: Notification classes (27+ types)

**Email Notifications**:
```
Notifications/
├── BookingAcceptedEmail.php
├── BookingCompletedEmail.php
├── BookingRejectedEmail.php
├── BookingRequestEmail.php
├── NewMessageEmail.php
├── PayoutRequestEmail.php
├── PasswordResetEmail.php
├── WelcomeEmail.php
└── ...
```

**Database Notifications**:
```
Notifications/
├── BookingAccepted.php
├── BookingCompleted.php
├── BookingRejected.php
├── NewBooking.php
├── NewMessage.php
├── PayoutProcessed.php
└── ...
```

**Pattern**:
```php
// Send notification
$user->notify(new BookingAcceptedEmail($booking));

// Queue notification
$user->notify((new NewMessageEmail($message))->delay(now()->addMinutes(5)));
```

#### `/app/Jobs`
**Purpose**: Queue jobs (15+ jobs)
```
Jobs/
├── SendNotificationJob.php         # Email dispatch
├── SendDbNotificationJob.php       # DB notification
├── CreateGoogleCalendarEventJob.php
├── TranslateLangFilesJob.php
├── QueueHeartbeatJob.php           # Queue health check
└── ...
```

#### `/app/Observers`
**Purpose**: Model event handlers (3 observers)
```
Observers/
├── CategoryObserver.php            # Clear cache on category change
├── PageObserver.php                # Clear cache on page change
└── SlotBookingObserver.php         # Booking lifecycle events
```

#### `/app/Helpers`
**Purpose**: Global helper functions
```
Helpers/
└── helpers.php                     # 100+ utility functions
```

**Key Functions**:
- `setting($key, $default)` - Get dynamic setting
- `getUserRole()` - Get active user role
- `formatCurrency($amount)` - Money formatting
- `generatePassword($length)` - Secure password
- `getCountries()` - Country list
- `getTranslatedLanguages()` - Enabled languages

---

### `/Modules` - Modular Features

#### Module Structure
```
Modules/
├── LaraPayease/                    # Payment gateway module
│   ├── Config/
│   ├── Database/
│   ├── Drivers/
│   │   ├── Stripe.php
│   │   ├── Razorpay.php
│   │   ├── Paytm.php
│   │   └── Iyzico.php
│   ├── Facades/
│   │   └── PaymentDriver.php
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Resources/
│   ├── Routes/
│   └── module.json
└── MeetFusion/                     # Google Meet integration
    ├── Config/
    ├── Http/Controllers/
    ├── Models/
    ├── Resources/
    ├── Routes/
    └── module.json
```

**Module Usage**:
```bash
# List modules
php artisan module:list

# Enable/disable
php artisan module:enable ModuleName
php artisan module:disable ModuleName
```

---

### `/packages` - Custom Packages

```
packages/
├── laraguppy/                      # Chat system
│   ├── src/
│   ├── routes/
│   ├── config/
│   └── composer.json
├── larabuild/
│   ├── optionbuilder/             # Dynamic settings
│   └── pagebuilder/               # Page builder
└── laravel-installer/             # Installation wizard
```

---

### `/resources` - Frontend Assets

#### `/resources/views`
**Purpose**: Blade templates

**Structure**:
```
views/
├── components/                     # 100+ Blade components
│   ├── admin/                     # Admin UI components
│   ├── common/                    # Shared components
│   ├── forms/                     # Form elements
│   ├── frontend/                  # Public components
│   └── ...
├── emails/                        # Email templates
│   ├── booking-accepted.blade.php
│   ├── booking-request.blade.php
│   └── ...
├── layouts/                       # Layout templates
│   ├── app.blade.php              # Main dashboard layout
│   ├── admin-app.blade.php        # Admin panel layout
│   ├── frontend-app.blade.php     # Public site layout
│   └── guest.blade.php            # Auth pages layout
├── livewire/                      # Livewire views
├── pagebuilder/                   # PageBuilder templates
└── ...
```

**Layouts**:
- `app.blade.php` - Authenticated users (student/tutor)
- `admin-app.blade.php` - Admin panel
- `frontend-app.blade.php` - Public pages (homepage, blog)
- `guest.blade.php` - Login, register, password reset

#### `/resources/css`
```
css/
└── app.css                        # Tailwind entry point
```

#### `/resources/js`
```
js/
├── app.js                         # Main JS entry
├── bootstrap.js                   # Axios, Echo setup
└── laraguppy/
    └── app.js                     # Chat JS
```

---

### `/routes` - Route Definitions

```
routes/
├── web.php                        # Main web routes
├── api.php                        # API routes (/api/*)
├── admin.php                      # Admin routes (/admin/*)
├── auth.php                       # Auth routes (login, register)
├── breadcrumbs.php                # Breadcrumb definitions
├── channels.php                   # Broadcasting channels
├── console.php                    # Artisan commands
├── optionbuilder.php              # Settings routes
└── pagebuilder.php                # PageBuilder routes
```

**Route Organization**:
- **Web Routes**: Public + authenticated user routes
- **Admin Routes**: Admin-only (middleware: `role:admin`)
- **API Routes**: REST API (middleware: `auth:sanctum`)
- **Auth Routes**: Login, register, password reset

---

### `/database` - Database Layer

#### `/database/migrations`
**Purpose**: Database schema definitions (100+ migrations)

**Key Migrations**:
- `create_users_table.php` - Core users
- `create_profiles_table.php` - User profiles
- `create_slots_table.php` - Tutor availability
- `create_slot_bookings_table.php` - Bookings
- `create_orders_table.php` - Payments
- `create_wallets_table.php` - Balances
- `create_notifications_table.php` - DB notifications
- `create_permission_tables.php` - Spatie permissions

#### `/database/factories`
**Purpose**: Test data generation
```
factories/
├── UserFactory.php
├── BlogFactory.php
└── ...
```

#### `/database/seeders`
**Purpose**: Database seeding
```
seeders/
├── DatabaseSeeder.php
├── RoleSeeder.php
├── PermissionSeeder.php
└── ...
```

---

### `/config` - Configuration Files

**Key Configs**:
```
config/
├── app.php                        # Application config
├── auth.php                       # Authentication
├── cache.php                      # Cache drivers
├── database.php                   # DB connections
├── filesystems.php                # Storage config
├── livewire.php                   # Livewire settings
├── mail.php                       # Email config
├── permission.php                 # Spatie permissions
├── queue.php                      # Queue drivers
├── reverb.php                     # WebSocket server
├── services.php                   # Third-party APIs
├── session.php                    # Session handling
├── zoom.php                       # Zoom API
└── ...
```

---

### `/public` - Web Root

```
public/
├── index.php                      # Application entry point
├── .htaccess                      # Apache rewrite rules
├── build/                         # Compiled Vite assets
│   ├── assets/
│   └── manifest.json
├── addons/                        # Addon assets
├── admin/                         # Admin panel assets
├── css/                           # Legacy CSS
├── fonts/                         # Web fonts
├── images/                        # Static images
├── js/                            # Legacy JS
└── modules/                       # Module assets
```

---

### `/storage` - File Storage

```
storage/
├── app/                           # Application files
│   ├── private/                   # Protected files
│   └── public/                    # Public files (symlinked)
├── framework/                     # Framework cache
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/                          # Application logs
    └── laravel.log
```

---

### `/tests` - Test Suite

```
tests/
├── Feature/                       # Integration tests
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── PasswordResetTest.php
│   │   └── RegistrationTest.php
│   ├── ExampleTest.php
│   └── ProfileTest.php
├── Unit/                          # Unit tests
│   └── ExampleTest.php
└── TestCase.php                   # Base test class
```

---

## File Naming Conventions

### Controllers
- **Singular**: `UserController.php`, `BookingController.php`
- **Namespaced**: `Admin\UserController.php`
- **Resource**: CRUD operations (index, create, store, show, edit, update, destroy)

### Models
- **Singular**: `User.php`, `SlotBooking.php`
- **PascalCase**: `UserAccountSetting.php`

### Migrations
- **Snake_case**: `create_users_table.php`
- **Timestamp prefix**: `2024_06_25_071342_`
- **Descriptive**: `add_featured_to_tutors_table.php`

### Livewire Components
- **PascalCase**: `SearchTutor.php`
- **Namespaced**: `Pages/Admin/Dashboard.php`
- **Views**: `resources/views/livewire/search-tutor.blade.php` (kebab-case)

### Services
- **Singular**: `BookingService.php`
- **Suffix**: Always ends with `Service`

### Requests
- **Action prefix**: `StoreBookingRequest.php`, `UpdateProfileRequest.php`

### Jobs
- **Action name**: `SendNotificationJob.php`
- **Suffix**: Always ends with `Job`

### Notifications
- **Action/Event**: `BookingAcceptedEmail.php`
- **Suffix**: Email notifications end with `Email`

---

## Quick File Lookup

### Need to...

**Modify user authentication?**
→ `app/Http/Controllers/Auth/*`
→ `app/Livewire/Forms/LoginForm.php`

**Change booking logic?**
→ `app/Services/BookingService.php`
→ `app/Http/Controllers/Common/BookingController.php`

**Add payment gateway?**
→ `Modules/LaraPayease/Drivers/YourGateway.php`

**Customize emails?**
→ `app/Notifications/*Email.php`
→ `resources/views/emails/*.blade.php`

**Modify dashboard?**
→ `app/Livewire/Pages/*/Dashboard.php`
→ `resources/views/livewire/pages/*/dashboard.blade.php`

**Add/modify routes?**
→ `routes/web.php` (public/user routes)
→ `routes/admin.php` (admin routes)
→ `routes/api.php` (API routes)

**Change database structure?**
→ Create new migration: `php artisan make:migration`
→ Modify model: `app/Models/*.php`

**Add settings?**
→ Use OptionBuilder (stored in `optionbuilder__settings`)
→ Admin panel: Settings section

**Customize frontend?**
→ Livewire: `app/Livewire/*`
→ Blade: `resources/views/*`
→ CSS: `resources/css/app.css`
→ JS: `resources/js/app.js`

**Add new role/permission?**
→ `database/seeders/RoleSeeder.php`
→ Or via admin panel

**Configure integrations?**
→ `.env` file (credentials)
→ `config/services.php` (Google, Stripe)
→ `config/zoom.php` (Zoom)

**Debug issues?**
→ `storage/logs/laravel.log`
→ Enable `APP_DEBUG=true` (dev only)
→ Install Telescope: `composer require laravel/telescope --dev`

---

## Code Organization Patterns

### Controller → Service → Model
```
BookingController.php (HTTP)
    ↓
BookingService.php (Business Logic)
    ↓
SlotBooking.php (Data Model)
    ↓
Database
```

### Livewire Component Flow
```
Component Class (app/Livewire/*)
    ↓
Component View (resources/views/livewire/*)
    ↓
User Interaction (Alpine.js events)
    ↓
Component Method (handle event)
    ↓
Service Layer (business logic)
    ↓
Database
```

### API Request Flow
```
Route (routes/api.php)
    ↓
Controller (app/Http/Controllers/Api/*)
    ↓
Form Request (validation)
    ↓
Service Layer
    ↓
Model
    ↓
JSON Response
```

---

## Environment-Specific Files

### Development
- `.env` - Local configuration
- `storage/logs/laravel.log` - Debug logs
- `public/build/` - Development assets (hot reload)

### Production
- `.env.production` - Production config
- Cached configs: `bootstrap/cache/config.php`
- Optimized assets: `public/build/` (hashed)

### Testing
- `phpunit.xml` - Test configuration
- `.env.testing` - Test environment
- Database: SQLite in-memory or separate test DB

---

## Module System

### Module Locations
```
Modules/ModuleName/
├── Config/config.php
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
├── Providers/
│   └── ModuleNameServiceProvider.php
├── Resources/
│   ├── assets/
│   └── views/
├── Routes/
│   ├── web.php
│   └── api.php
└── module.json
```

### Module Activation
- Enabled modules tracked in: `modules_statuses.json`
- Module assets auto-loaded by Vite
- Module routes auto-registered

---

## Asset Pipeline

### Vite Configuration
**File**: `vite.config.js`

**Input Files**:
- `resources/css/app.css`
- `resources/js/app.js`
- Module assets (auto-discovered)

**Output**:
- Development: Hot reload via `npm run dev`
- Production: Optimized bundles in `public/build/`

### Asset Loading
```blade
{{-- In layouts --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Livewire assets --}}
@livewireStyles
@livewireScripts
```

---

## Cache Locations

### Application Cache
- **Database**: `cache` table
- **File**: `storage/framework/cache/data/`
- **Redis**: External Redis server

### Framework Cache
- **Config**: `bootstrap/cache/config.php`
- **Routes**: `bootstrap/cache/routes-v7.php`
- **Views**: `storage/framework/views/`

---

## Summary

**Total Files**: 500+ PHP files, 200+ views, 100+ migrations
**Code Organization**: Service-oriented architecture with modular features
**Key Entry Points**:
- Web: `public/index.php` → `routes/web.php`
- API: `public/index.php` → `routes/api.php`
- Console: `artisan` → `routes/console.php`

**Most Active Directories**:
- `app/Livewire/` - 78+ reactive components
- `app/Services/` - 35+ business logic services
- `app/Models/` - 50+ data models
- `resources/views/` - 100+ Blade templates

**For Quick Edits**:
- Settings: Database (`optionbuilder__settings`)
- Translations: `lang/` directory
- Assets: `resources/css/` and `resources/js/`
- Public files: `public/` directory
