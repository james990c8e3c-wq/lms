# AI AGENT CONTEXT - LERNEN LMS

## System Overview

**Project**: Lernen - Learning Management System (Tutor Booking Platform)
**Framework**: Laravel 11.x
**Architecture**: Modular Monolith with TALL Stack (Tailwind, Alpine.js, Laravel, Livewire)
**Purpose**: Connect students with tutors through 1-on-1 booking sessions

---

## Core Functionality

### Primary Use Cases
1. **Students** book tutoring sessions with available tutors
2. **Tutors** manage availability slots and conduct sessions
3. **Admins** oversee platform operations and settings
4. **Video Conferencing** via Zoom or Google Meet
5. **Payments** processed through Stripe, Razorpay, Paytm, or Iyzico
6. **Real-time Chat** between users via LaraGuppy + Reverb

### Key Entities
- **Users** (multi-role: admin, tutor, student)
- **Slots** (tutor availability blocks)
- **SlotBookings** (confirmed sessions)
- **Orders** (payment records)
- **Notifications** (email + database)
- **Messages** (chat threads)

---

## Architecture Patterns

### Technology Stack
```
Frontend:
- Livewire 3.5 (78+ components)
- Alpine.js 3.x (reactive UI)
- Tailwind CSS 3.1 + Bootstrap 5 (hybrid)
- Vite 5.0 (asset bundling)
- Blade templates (100+ components)

Backend:
- Laravel 11.x
- PHP 8.2+
- MySQL 8.0 / SQLite
- Redis (caching, queues)
- Laravel Reverb (WebSockets)

Integrations:
- Zoom API (video)
- Google Calendar/Meet (scheduling)
- Stripe + others (payments)
- OpenAI GPT-4 (AI features)
- AWS S3 (file storage)
```

### Design Patterns
1. **Service Layer**: Business logic isolated in `app/Services/`
2. **Repository**: Eloquent models act as repositories
3. **Observer**: Model events trigger side effects
4. **Facade**: Payment gateway abstraction
5. **Middleware**: Role/permission enforcement
6. **Job/Queue**: Async processing for emails, notifications
7. **Event/Listener**: Decoupled system events

---

## Directory Structure

```
app/
├── Console/Commands/       # Artisan commands
├── Http/
│   ├── Controllers/        # 80+ HTTP controllers
│   ├── Middleware/         # 7 custom (role, locale, maintenance)
│   └── Requests/          # 45+ form validation classes
├── Livewire/              # 78+ reactive components
│   ├── Pages/             # Full-page components
│   │   ├── Admin/         # Admin panel (35+)
│   │   ├── Common/        # Shared (12+)
│   │   ├── Student/       # Student dashboard (15+)
│   │   └── Tutor/         # Tutor dashboard (16+)
│   ├── Components/        # Reusable widgets (6)
│   ├── Forms/             # Form objects (5)
│   └── Frontend/          # Public pages (4)
├── Models/                # 50+ Eloquent models
├── Services/              # 35+ business logic services
├── Notifications/         # 27+ notification classes
├── Jobs/                  # 15+ queue jobs
├── Observers/             # 3 model observers
└── Helpers/               # Global helper functions

Modules/                   # Modular features
├── LaraPayease/          # Payment gateway module
└── MeetFusion/           # Google Meet integration

packages/                  # Custom packages
└── laraguppy/            # Chat system package
```

---

## Database Schema (Key Tables)

### Users & Authentication
- `users` - Core user accounts
- `profiles` - Extended user information
- `user_account_settings` - JSON settings storage
- `roles` - Spatie permission roles
- `permissions` - Granular permissions
- `model_has_roles` - User-role pivot

### Booking System
- `slots` - Tutor availability blocks
- `slot_bookings` - Confirmed booking sessions
- `slot_booking_items` - Booking line items
- `slot_booking_histories` - Status change log

### Financial
- `orders` - Payment transactions
- `wallets` - User wallet balances
- `wallet_histories` - Transaction ledger
- `payouts` - Tutor earnings withdrawals
- `payout_methods` - Payment account details

### Content
- `blogs` - Blog posts
- `blog_categories` - Blog taxonomy
- `pages` - Static pages (PageBuilder)
- `menus` - Navigation menus

### Communication
- `notifications` - Database notifications
- `notification_settings` - User preferences
- `laraguppy_messages` - Chat messages
- `laraguppy_threads` - Chat conversations

### System
- `optionbuilder__settings` - Dynamic settings (JSON)
- `jobs` - Queue jobs
- `failed_jobs` - Failed queue jobs
- `cache` - Database cache storage
- `sessions` - User sessions

---

## Authentication & Authorization

### Authentication Methods
1. **Web**: Session-based (default Laravel)
2. **API**: Sanctum token-based
3. **Social**: Google OAuth (Socialite)

### Authorization Layers
1. **Roles** (Spatie): admin, tutor, student
2. **Permissions**: Granular (e.g., 'manage users', 'create bookings')
3. **Middleware**: `role:admin`, `permit-of:permission`
4. **Gates**: Policy-based (e.g., 'manage-booking')

### Key Middleware
- `role:admin|tutor` - Require specific role
- `permit-of:manage users` - Require permission
- `onlineUser` - Track user online status
- `locale` - Set application language
- `maintenance` - Maintenance mode check
- `enabled` - Module enabled check

---

## Business Logic Flow

### Booking Creation Flow
```
1. Student browses tutor profiles
   ↓
2. Views tutor's available slots
   ↓
3. Selects slot + adds to cart (CartService)
   ↓
4. Proceeds to checkout (Livewire Checkout component)
   ↓
5. Applies discount/wallet balance
   ↓
6. Selects payment method (PaymentDriver facade)
   ↓
7. Payment processed (Stripe/Razorpay/etc.)
   ↓
8. Order created (OrderService)
   ↓
9. SlotBooking created (BookingService)
   ↓
10. Zoom/Google Meet created (ZoomService/GoogleCalender)
   ↓
11. Notifications sent (SendNotificationJob)
    ├── Tutor: New booking email
    └── Student: Booking confirmation email
   ↓
12. Calendar event created (if Google connected)
   ↓
13. Database notification created
   ↓
14. WebSocket broadcast (Reverb)
```

### Payment Processing Flow
```
1. Checkout component prepares order
   ↓
2. PaymentDriver::driver($method) resolves gateway
   ↓
3. Gateway-specific charge initiated
   ├── Stripe: Checkout Session created
   ├── Razorpay: Order created
   └── Wallet: Balance deducted
   ↓
4. User redirected to payment page/gateway
   ↓
5. Payment callback received
   ↓
6. Transaction verified
   ↓
7. Order status updated (OrderService)
   ↓
8. Booking confirmed (BookingService)
   ↓
9. Tutor earnings calculated (EarningService)
   ↓
10. Wallet balance updated (WalletService)
```

---

## Key Services

### Core Services (app/Services/)
- **UserService** - User CRUD, profile management, favorites
- **BookingService** - Slot booking lifecycle, Zoom/Google Meet
- **SlotService** - Tutor availability management
- **OrderService** - Payment order handling
- **WalletService** - Balance management, transactions
- **PayoutService** - Tutor withdrawal requests
- **CartService** - Shopping cart operations
- **NotificationService** - Email dispatch coordination
- **SiteService** - Menu, pages, settings retrieval
- **OptionBuilderService** - Dynamic settings management
- **BillingService** - Billing details management
- **EarningService** - Tutor revenue calculations
- **ZoomService** - Zoom API integration
- **GoogleCalender** - Google Calendar sync

### Service Responsibilities
Services handle:
- Complex business logic
- Multi-model operations
- External API calls
- Transaction coordination
- Cache management
- Event triggering

Controllers should be thin, delegating to services.

---

## Frontend Architecture

### Livewire Components
**Full-Page Components** (`app/Livewire/Pages/`):
- Admin dashboard components (35+)
- User profile management
- Booking management
- Notification center
- Settings panels

**Reusable Components** (`app/Livewire/Components/`):
- SearchTutor (with filters, pagination)
- Courses (course listing)
- SimilarTutors (recommendations)
- StudentsReviews (rating display)
- TutorResume (profile card)

**Frontend Components** (`app/Livewire/Frontend/`):
- Checkout (payment flow)
- Blogs (blog listing)
- BlogDetails (single blog)
- ThankYou (post-payment)

### Livewire Patterns
```php
// Lazy loading for performance
#[Lazy]
class SearchTutor extends Component
{
    public function placeholder()
    {
        return view('skeletons.tutor-list');
    }
}

// Form objects for validation
class OrderForm extends Form
{
    #[Validate('required')]
    public $firstName = '';
    
    public function rules(): array
    {
        return [/* rules */];
    }
}

// Event communication
$this->dispatch('eventName', param: $value);

#[On('eventName')]
public function handleEvent($param) { }
```

### Alpine.js Patterns
```javascript
// Inline reactive components
x-data="{
    open: false,
    cartCount: @js($cartCount),
    toggle() { this.open = !this.open }
}"

// Event listeners
x-on:cart-updated.window="cartCount = $event.detail.count"

// Conditional rendering
x-show="open"
x-if="items.length > 0"

// Loops
x-for="item in items"
```

---

## API Endpoints

### Authentication
- `POST /api/login` - Login with credentials
- `POST /api/register` - Register new user
- `POST /api/logout` - Logout user

### Notifications (Authenticated)
- `GET /api/notifications` - List notifications
- `POST /api/notifications/mark-read` - Mark as read
- `DELETE /api/notifications/{id}` - Delete notification

### Bookings (Authenticated)
- `GET /api/bookings` - List user bookings
- `GET /api/bookings/{id}` - Booking details
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Cancel booking

### Profile (Authenticated)
- `GET /api/profile` - Get user profile
- `PUT /api/profile` - Update profile
- `POST /api/profile/avatar` - Upload avatar

**Authentication**: Bearer token (Sanctum)
```
Authorization: Bearer {token}
```

---

## Configuration & Settings

### Environment Variables (Critical)
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=lernen
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# Integrations
ZOOM_CLIENT_ID=
ZOOM_CLIENT_SECRET=
ZOOM_ACCOUNT_ID=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

STRIPE_KEY=
STRIPE_SECRET=

OPENAI_API_KEY=
```

### Dynamic Settings (optionbuilder__settings)
Stored in database, cached forever:
- Site name, logo, favicon
- Currency settings
- Payment gateway toggles
- Email templates
- Notification preferences
- Feature flags (modules enabled/disabled)

Access via: `setting('key.nested', 'default')`

---

## Common Coding Patterns

### Service Injection
```php
class BookingController extends Controller
{
    protected $bookingService;
    
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    
    public function store(Request $request)
    {
        $booking = $this->bookingService->createBooking($request->all());
        return redirect()->route('bookings.show', $booking);
    }
}
```

### Repository Pattern (via Eloquent)
```php
// Service acts as repository coordinator
class BookingService
{
    public function getBookingsForTutor($tutorId)
    {
        return SlotBooking::where('tutor_id', $tutorId)
            ->with(['student', 'slot'])
            ->orderBy('start_datetime', 'desc')
            ->paginate(20);
    }
}
```

### Caching Pattern
```php
// Cache forever for static data
$role = Cache::rememberForever('role-' . $name, function () use ($name) {
    return Role::where('name', $name)->first();
});

// Time-based cache
$tutors = Cache::remember('featured-tutors', 3600, function () {
    return User::role('tutor')->where('featured', true)->get();
});

// Clear cache on update
Cache::forget('featured-tutors');
```

### Event Dispatching
```php
// Dispatch event
Event::dispatch('settings.updated', [
    'section' => 'payment',
    'key' => 'stripe_enabled'
]);

// Listen to event
Event::listen('settings.updated', function ($data) {
    Cache::forget('optionbuilder__settings');
});
```

### Queue Jobs
```php
// Dispatch job
dispatch(new SendNotificationJob('bookingConfirmed', $user, $data));

// With delay
SendNotificationJob::dispatch($type, $user, $data)
    ->delay(now()->addMinutes(5));

// Priority queue
dispatch(new UrgentJob())->onQueue('high');
```

---

## Testing

### Test Structure
```
tests/
├── Feature/               # Integration tests
│   ├── Auth/             # Authentication (6 tests)
│   └── ProfileTest.php   # Profile management
└── Unit/                 # Isolated logic tests
    └── ExampleTest.php
```

### Running Tests
```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage --min=70

# Parallel
php artisan test --parallel
```

### Test Patterns
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_student_can_create_booking()
    {
        // Arrange
        $student = User::factory()->create();
        $slot = Slot::factory()->create();
        
        // Act
        $this->actingAs($student)
            ->post('/bookings', ['slot_id' => $slot->id]);
        
        // Assert
        $this->assertDatabaseHas('slot_bookings', [
            'slot_id' => $slot->id,
            'booker_id' => $student->id,
        ]);
    }
}
```

---

## Performance Considerations

### Database Optimization
- Indexed columns: user_id, email, status, created_at
- Eager loading to prevent N+1: `->with(['relation'])`
- Select only needed columns: `->select('id', 'name')`
- Chunk large datasets: `User::chunk(200, fn($users) => ...)`

### Caching Strategy
- **Database cache**: Default driver
- **Redis**: Recommended for production
- **Forever cache**: Roles, permissions, static settings
- **Time-based**: Menus (1 hour), featured tutors (1 hour)
- **Tags** (Redis): Group related caches for bulk invalidation

### Queue Usage
- Email notifications: Always queued
- PDF generation: Queued
- External API calls: Queued if non-critical
- Zoom/Google Meet creation: Queued

### Asset Optimization
- Vite for bundling (tree-shaking, code-splitting)
- Image optimization via Intervention Image
- Lazy loading images: `loading="lazy"`
- CDN for static assets

---

## Security Measures

### Built-in Laravel Security
- ✅ CSRF protection (automatic)
- ✅ SQL injection prevention (Eloquent)
- ✅ XSS protection (Blade escaping)
- ✅ Password hashing (bcrypt, 12 rounds)
- ✅ Rate limiting (throttle middleware)
- ✅ Session security (httpOnly, secure cookies)

### Custom Security
- Role-based middleware (`role:admin`)
- Permission-based middleware (`permit-of:permission`)
- Input validation (Form Requests)
- File upload restrictions (type, size)
- API token authentication (Sanctum)
- Security headers (X-Frame-Options, CSP, HSTS)

### Sensitive Data
- Passwords: Never logged, bcrypt hashed
- API keys: In .env, never committed
- Tokens: Short-lived, revocable
- User data: Hidden in API responses
- Encryption: Available via `Crypt::encrypt()`

---

## Common Issues & Solutions

### Issue: Queue not processing
**Solution**: 
```bash
php artisan queue:work
# Or use Supervisor in production
```

### Issue: Livewire component not loading
**Solution**: 
```bash
php artisan livewire:discover
php artisan view:clear
```

### Issue: Cache not clearing
**Solution**: 
```bash
php artisan cache:clear
Cache::flush(); // In code
```

### Issue: Assets not loading (404)
**Solution**: 
```bash
npm run build
php artisan storage:link
```

### Issue: Permission denied
**Solution**: 
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data .
```

---

## Development Workflow

### Adding New Feature
1. Create migration: `php artisan make:migration create_table`
2. Create model: `php artisan make:model ModelName`
3. Create service: `app/Services/FeatureService.php`
4. Create controller: `php artisan make:controller FeatureController`
5. Add routes: `routes/web.php` or `routes/api.php`
6. Create views/Livewire: `php artisan make:livewire FeatureName`
7. Add validation: `php artisan make:request StoreFeatureRequest`
8. Write tests: `php artisan make:test FeatureTest`

### Code Standards
- Follow PSR-12 coding standards
- Use type hints for method parameters
- Document complex logic with comments
- Keep controllers thin (delegate to services)
- Use dependency injection over facades
- Write descriptive variable/method names
- Add PHPDoc blocks for public methods

---

## Deployment Checklist

### Pre-Production
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database backed up
- [ ] SSL certificate installed
- [ ] Force HTTPS enabled
- [ ] Cache configured (Redis recommended)
- [ ] Queue workers running (Supervisor)
- [ ] Cron jobs scheduled
- [ ] File permissions correct (775 storage)
- [ ] Environment variables secured

### Production Optimization
```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart
```

---

## Quick Reference

### Helper Functions
```php
setting($key, $default)           // Get dynamic setting
getUserRole()                     // Get active user role
formatCurrency($amount)           // Format money
generatePassword($length)         // Generate secure password
getCountries()                    // Get country list
getTranslatedLanguages()          // Get enabled languages
```

### Useful Artisan Commands
```bash
php artisan optimize:clear        # Clear all caches
php artisan queue:work            # Start queue worker
php artisan reverb:start          # Start WebSocket server
php artisan module:list           # List modules
php artisan telescope:install     # Install debugging tool
php artisan db:seed              # Seed database
```

### Key Directories
- Models: `app/Models/`
- Services: `app/Services/`
- Controllers: `app/Http/Controllers/`
- Livewire: `app/Livewire/`
- Views: `resources/views/`
- Routes: `routes/`
- Config: `config/`
- Migrations: `database/migrations/`

---

## For AI Agents

### When Modifying Code
1. **Understand context**: Read related phase documentation
2. **Follow patterns**: Use existing service/controller patterns
3. **Maintain security**: Never bypass validation or authorization
4. **Consider performance**: Use caching, eager loading, queues
5. **Write tests**: Add test coverage for new features
6. **Update docs**: Document significant changes

### When Debugging
1. Check logs: `storage/logs/laravel.log`
2. Enable query log: `DB::enableQueryLog()`
3. Use Telescope: `php artisan telescope:install`
4. Check queue: `php artisan queue:failed`
5. Verify cache: `php artisan cache:clear`

### Best Practices
- Always validate input (Form Requests)
- Always authorize actions (Gates/Policies)
- Always queue emails/heavy tasks
- Always use transactions for multi-step operations
- Always clear relevant caches after updates
- Always handle exceptions gracefully
- Always log errors for debugging

---

**Complete Documentation**: 62,850+ lines across 15 phases
**Last Updated**: January 2026
**Version**: Laravel 11.x, PHP 8.2+
