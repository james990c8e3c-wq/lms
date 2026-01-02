# PHASE 14: SECURITY & PERFORMANCE

## Table of Contents
1. [Security Overview](#security-overview)
2. [Authentication Security](#authentication-security)
3. [Authorization & Access Control](#authorization--access-control)
4. [Data Protection](#data-protection)
5. [Input Validation & Sanitization](#input-validation--sanitization)
6. [CSRF Protection](#csrf-protection)
7. [SQL Injection Prevention](#sql-injection-prevention)
8. [XSS Protection](#xss-protection)
9. [Security Headers](#security-headers)
10. [Performance Optimization](#performance-optimization)
11. [Caching Strategy](#caching-strategy)
12. [Database Optimization](#database-optimization)
13. [Queue Optimization](#queue-optimization)
14. [Asset Optimization](#asset-optimization)
15. [Monitoring & Logging](#monitoring--logging)

---

## 1. Security Overview

### 1.1 Security Layers

**Multi-Layer Security Approach**:

```
┌─────────────────────────────────────┐
│   Application Layer Security        │
│   - Input Validation                │
│   - Output Escaping                 │
│   - CSRF Tokens                     │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Authentication & Authorization    │
│   - Laravel Sanctum (API)           │
│   - Session-based (Web)             │
│   - Spatie Permissions              │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Framework Security                │
│   - Laravel Security Features       │
│   - Middleware Protection           │
│   - Rate Limiting                   │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│   Infrastructure Security           │
│   - HTTPS/SSL                       │
│   - Firewall Rules                  │
│   - Environment Variables           │
└─────────────────────────────────────┘
```

### 1.2 Security Checklist

**Production Security Checklist**:

- ✅ **Environment**:
  - [ ] `APP_DEBUG=false` in production
  - [ ] Strong `APP_KEY` generated
  - [ ] `.env` file not in version control
  - [ ] Proper file permissions (storage/ writable)

- ✅ **HTTPS**:
  - [ ] Force HTTPS in production
  - [ ] HSTS headers enabled
  - [ ] Secure cookies enabled

- ✅ **Database**:
  - [ ] Prepared statements (Eloquent)
  - [ ] Database user with minimal privileges
  - [ ] Regular backups configured

- ✅ **Authentication**:
  - [ ] Strong password requirements
  - [ ] Rate limiting on login
  - [ ] Two-factor authentication (optional)
  - [ ] Session timeout configured

- ✅ **Authorization**:
  - [ ] Role-based access control (RBAC)
  - [ ] Permission checks on all sensitive routes
  - [ ] API token authentication

- ✅ **File Uploads**:
  - [ ] File type validation
  - [ ] File size limits
  - [ ] Storage outside public directory
  - [ ] Virus scanning (optional)

---

## 2. Authentication Security

### 2.1 Password Security

**Password Hashing**:

```php
// Laravel uses bcrypt by default (secure)
use Illuminate\Support\Facades\Hash;

// Hash password
$hashedPassword = Hash::make($request->password);

// Verify password
if (Hash::check($plainPassword, $hashedPassword)) {
    // Password correct
}

// Check if rehashing needed (algorithm updated)
if (Hash::needsRehash($hashedPassword)) {
    $hashedPassword = Hash::make($plainPassword);
}
```

**Password Validation Rules**:

```php
// Strong password requirements
use Illuminate\Validation\Rules\Password;

$request->validate([
    'password' => [
        'required',
        'confirmed',
        Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(), // Check against data breaches
    ],
]);
```

**Configuration**: `config/hashing.php`

```php
return [
    'driver' => 'bcrypt',
    
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12), // Production: 12, Testing: 4
    ],
];
```

### 2.2 Session Security

**Session Configuration**: `config/session.php`

```php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    
    // Only send cookie over HTTPS
    'secure' => env('SESSION_SECURE_COOKIE', true),
    
    // Prevent JavaScript access to session cookie
    'http_only' => true,
    
    // Cookie only sent to same site
    'same_site' => 'lax',
    
    // Session lifetime (minutes)
    'lifetime' => 120,
    
    // Expire session on browser close
    'expire_on_close' => false,
];
```

**Force HTTPS** (Production):

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### 2.3 API Token Security

**Sanctum Token Authentication**:

```php
// Generate token with abilities
$token = $user->createToken('api-token', ['bookings:create', 'bookings:read']);

// Verify token abilities
if ($request->user()->tokenCan('bookings:create')) {
    // User has permission
}

// Revoke tokens
$request->user()->tokens()->delete(); // Revoke all
$request->user()->currentAccessToken()->delete(); // Revoke current
```

**Token Expiration** (Custom Implementation):

```php
// app/Models/PersonalAccessToken.php
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = static::find($id)) {
            // Check if token expired
            if ($instance->expires_at && $instance->expires_at->isPast()) {
                return null;
            }
            
            return hash_equals($instance->token, hash('sha256', $token)) 
                ? $instance 
                : null;
        }
    }
}
```

### 2.4 Rate Limiting

**Login Rate Limiting**:

```php
// bootstrap/app.php - Middleware configuration
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

$middleware->alias([
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
]);

// config/auth.php - Rate limiter
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->input('email'));
});
```

**API Rate Limiting**:

```php
// bootstrap/app.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Route Protection**:

```php
// routes/web.php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
});
```

---

## 3. Authorization & Access Control

### 3.1 Spatie Permission System

**Role-Based Access Control (RBAC)**:

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Create roles
$admin = Role::create(['name' => 'admin']);
$tutor = Role::create(['name' => 'tutor']);
$student = Role::create(['name' => 'student']);

// Create permissions
$manageUsers = Permission::create(['name' => 'manage users']);
$viewBookings = Permission::create(['name' => 'view bookings']);
$createBookings = Permission::create(['name' => 'create bookings']);

// Assign permissions to roles
$admin->givePermissionTo($manageUsers);
$tutor->givePermissionTo([$viewBookings, 'create bookings']);
$student->givePermissionTo('create bookings');

// Assign role to user
$user->assignRole('tutor');

// Check permissions
if ($user->hasRole('admin')) {
    // User is admin
}

if ($user->can('manage users')) {
    // User has permission
}
```

### 3.2 Custom Middleware

**Role Middleware**: `app/Http/Middleware/RoleMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        $user = $authGuard->user();

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (!method_exists($user, 'hasAnyRole')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        $roles = explode('|', $role);
        
        // Get user's active role
        $roleData = getUserRole();
        $activeRole = $roleData['roleName'];
        
        if (!in_array($activeRole, $roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}
```

**Permission Middleware**:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermitOfMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        $user = Auth::guard($guard)->user();

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        $permissions = explode('|', $permission);

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forPermissions($permissions);
    }
}
```

**Middleware Registration**: `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => RoleMiddleware::class,
        'permit-of' => PermitOfMiddleware::class,
    ]);
})
```

**Route Protection**:

```php
// Require specific role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
});

// Require multiple roles (OR)
Route::middleware(['auth', 'role:admin|tutor'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
});

// Require specific permission
Route::middleware(['auth', 'permit-of:manage users'])->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});
```

### 3.3 Gate Authorization

**Define Gates**:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Check if user can manage booking
    Gate::define('manage-booking', function (User $user, SlotBooking $booking) {
        return $user->id === $booking->tutor_id 
            || $user->id === $booking->booker_id;
    });
    
    // Admin can do everything
    Gate::before(function (User $user, string $ability) {
        if ($user->hasRole('admin')) {
            return true;
        }
    });
}
```

**Use Gates in Controllers**:

```php
public function update(Request $request, SlotBooking $booking)
{
    $this->authorize('manage-booking', $booking);
    
    // User is authorized, proceed
    $booking->update($request->validated());
}
```

**Use Gates in Blade**:

```blade
@can('manage-booking', $booking)
    <button>Edit Booking</button>
@endcan

@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole
```

---

## 4. Data Protection

### 4.1 Database Encryption

**Encrypted Casting**:

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\Casts\Encrypted;

protected $casts = [
    'ssn' => Encrypted::class, // Social Security Number
    'credit_card' => Encrypted::class,
    'api_secret' => Encrypted::class,
];
```

**Manual Encryption**:

```php
use Illuminate\Support\Facades\Crypt;

// Encrypt
$encrypted = Crypt::encryptString('secret data');

// Decrypt
$decrypted = Crypt::decryptString($encrypted);

// Encrypt with serialization (arrays/objects)
$encrypted = Crypt::encrypt(['key' => 'value']);
$decrypted = Crypt::decrypt($encrypted);
```

### 4.2 Environment Variables

**Sensitive Data in .env**:

```env
# Never commit .env file to version control
APP_KEY=base64:your-generated-key

# Database credentials
DB_USERNAME=db_user
DB_PASSWORD=strong_password_here

# API Keys
STRIPE_SECRET=sk_live_your_secret_key
ZOOM_CLIENT_SECRET=your_zoom_secret
GOOGLE_CLIENT_SECRET=your_google_secret

# Email credentials
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
```

**.env.example** (Safe to commit):

```env
APP_KEY=

DB_USERNAME=
DB_PASSWORD=

STRIPE_SECRET=
ZOOM_CLIENT_SECRET=
```

### 4.3 Sensitive Data Handling

**Hide Sensitive Attributes**:

```php
// app/Models/User.php
protected $hidden = [
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
];

// Prevent mass assignment
protected $guarded = ['id', 'password', 'remember_token'];
```

**API Resource Filtering**:

```php
// app/Http/Resources/UserResource.php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'email' => $this->email,
        'name' => $this->when($request->user()->can('view-names'), $this->name),
        // Never expose password or tokens
    ];
}
```

---

## 5. Input Validation & Sanitization

### 5.1 Form Request Validation

**Comprehensive Validation**:

```php
// app/Http/Requests/StoreBookingRequest.php
public function rules(): array
{
    return [
        'slot_id' => 'required|exists:slots,id',
        'tutor_id' => 'required|exists:users,id',
        'notes' => 'nullable|string|max:1000',
        'amount' => 'required|numeric|min:0|max:10000',
    ];
}

public function messages(): array
{
    return [
        'slot_id.required' => 'Please select a time slot.',
        'amount.max' => 'Amount cannot exceed $10,000.',
    ];
}
```

**Custom Validation Rules**:

```php
// app/Rules/ValidTimeSlot.php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Slot;

class ValidTimeSlot implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slot = Slot::find($value);
        
        if (!$slot || $slot->status !== 'available') {
            $fail('The selected time slot is not available.');
        }
        
        if ($slot->start_time < now()) {
            $fail('Cannot book past time slots.');
        }
    }
}

// Usage
$request->validate([
    'slot_id' => ['required', new ValidTimeSlot],
]);
```

### 5.2 Data Sanitization

**Input Sanitization Helper**:

```php
// app/Helpers/helpers.php

/**
 * Sanitize user input
 */
function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // Remove HTML tags
    $input = strip_tags($input);
    
    // Convert special characters
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Trim whitespace
    $input = trim($input);
    
    return $input;
}
```

**HTML Purifier** (For Rich Text):

```php
// config/purifier.php
return [
    'default' => [
        'HTML.Allowed' => 'p,b,strong,i,em,u,a[href],ul,ol,li,br',
        'AutoFormat.RemoveEmpty' => true,
    ],
];

// Usage
use Mews\Purifier\Facades\Purifier;

$cleanHtml = Purifier::clean($dirtyHtml);
```

### 5.3 File Upload Security

**Secure File Upload**:

```php
public function store(Request $request)
{
    $request->validate([
        'avatar' => [
            'required',
            'file',
            'image', // Only images
            'max:2048', // 2MB max
            'mimes:jpg,jpeg,png', // Allowed types
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ],
    ]);
    
    // Store outside public directory
    $path = $request->file('avatar')->store('avatars', 'private');
    
    // Or use S3
    $path = $request->file('avatar')->store('avatars', 's3');
    
    return $path;
}
```

**Serve Protected Files**:

```php
public function download(Request $request, $fileId)
{
    $file = File::findOrFail($fileId);
    
    // Authorization check
    $this->authorize('download', $file);
    
    // Serve file from private storage
    return response()->download(
        storage_path('app/private/files/' . $file->path),
        $file->original_name
    );
}
```

---

## 6. CSRF Protection

### 6.1 CSRF Token Verification

**Automatic CSRF Protection** (enabled by default):

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'payfast/webhook',      // Payment webhooks
        'payment/success',      // Payment callbacks
        'api/*',               // API routes (use Sanctum)
    ]);
})
```

**Blade Templates**:

```blade
<form method="POST" action="/bookings">
    @csrf
    <!-- CSRF token automatically included -->
    <input type="text" name="subject">
    <button type="submit">Submit</button>
</form>
```

**AJAX Requests**:

```javascript
// Set CSRF token in meta tag
<meta name="csrf-token" content="{{ csrf_token() }}">

// jQuery
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Fetch API
fetch('/api/bookings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(data)
});

// Axios (automatically includes CSRF token)
axios.post('/bookings', data);
```

### 6.2 CSRF for SPA

**Sanctum CSRF Cookie**:

```javascript
// Get CSRF cookie first
await axios.get('/sanctum/csrf-cookie');

// Then make authenticated requests
await axios.post('/api/bookings', data);
```

---

## 7. SQL Injection Prevention

### 7.1 Eloquent ORM (Safe by Default)

**✅ Safe: Eloquent Query Builder**:

```php
// Parameter binding (safe)
$users = User::where('email', $request->email)->get();

$bookings = SlotBooking::where('tutor_id', $tutorId)
    ->where('status', 'pending')
    ->get();

// Named bindings (safe)
DB::select('SELECT * FROM users WHERE email = :email', ['email' => $email]);

// Positional bindings (safe)
DB::select('SELECT * FROM users WHERE id = ?', [$userId]);
```

**❌ Dangerous: Raw SQL**:

```php
// NEVER do this - SQL injection vulnerability
$email = $request->email;
$users = DB::select("SELECT * FROM users WHERE email = '$email'");

// If you must use raw SQL, use bindings
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

### 7.2 Raw Queries with Bindings

**Safe Raw Queries**:

```php
// With named parameters
$results = DB::select('
    SELECT u.*, COUNT(b.id) as booking_count 
    FROM users u
    LEFT JOIN slot_bookings b ON u.id = b.tutor_id
    WHERE u.role = :role
    GROUP BY u.id
', ['role' => 'tutor']);

// With positional parameters
$results = DB::select('
    SELECT * FROM bookings 
    WHERE start_datetime BETWEEN ? AND ?
', [$startDate, $endDate]);
```

---

## 8. XSS Protection

### 8.1 Blade Template Escaping

**Automatic Escaping** (default):

```blade
{{-- Escaped output (safe) --}}
{{ $user->name }}
{{ $booking->notes }}

{{-- Unescaped output (dangerous - only for trusted HTML) --}}
{!! $trustedHtml !!}

{{-- Raw output example --}}
{!! Purifier::clean($userContent) !!}
```

**JavaScript Context**:

```blade
<script>
    // ❌ Dangerous
    var userName = "{{ $user->name }}";
    
    // ✅ Safe
    var userName = @json($user->name);
    
    // ✅ Safe for objects
    var user = @json($user);
</script>
```

### 8.2 Content Security Policy (CSP)

**CSP Headers** (Middleware):

```php
// app/Http/Middleware/ContentSecurityPolicy.php
<?php

namespace App\Http\Middleware;

use Closure;

class ContentSecurityPolicy
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com",
            "font-src 'self' fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://api.stripe.com",
        ]));
        
        return $response;
    }
}
```

### 8.3 Output Encoding

**Helper Functions**:

```php
// HTML entity encoding
echo e($userInput); // Same as {{ $userInput }}

// JavaScript encoding
<script>
    var data = @json($data);
</script>

// URL encoding
<a href="{{ url('profile/' . urlencode($username)) }}">Profile</a>

// Attribute encoding
<div data-name="{{ e($userName) }}"></div>
```

---

## 9. Security Headers

### 9.1 Security Headers Middleware

**Comprehensive Security Headers**:

```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS filtering
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // HTTPS enforcement (HSTS)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }
        
        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions policy
        $response->headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
        ]));
        
        return $response;
    }
}
```

**Register Middleware**:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

---

## 10. Performance Optimization

### 10.1 Performance Strategy

**Optimization Layers**:

```
1. Application Layer
   ├── Cache frequently accessed data
   ├── Optimize database queries
   ├── Use eager loading
   └── Queue heavy tasks

2. Database Layer
   ├── Index frequently queried columns
   ├── Optimize query structure
   ├── Use query caching
   └── Database connection pooling

3. Frontend Layer
   ├── Asset minification
   ├── Image optimization
   ├── Lazy loading
   └── CDN delivery

4. Infrastructure Layer
   ├── Redis caching
   ├── Load balancing
   ├── HTTP/2
   └── Gzip compression
```

### 10.2 Query Optimization

**N+1 Query Problem**:

```php
// ❌ Bad: N+1 queries
$bookings = SlotBooking::all(); // 1 query
foreach ($bookings as $booking) {
    echo $booking->tutor->name; // N queries
    echo $booking->student->name; // N queries
}

// ✅ Good: Eager loading (3 queries total)
$bookings = SlotBooking::with(['tutor', 'student'])->get();
foreach ($bookings as $booking) {
    echo $booking->tutor->name;
    echo $booking->student->name;
}
```

**Lazy Eager Loading**:

```php
// Load relationship only when needed
$bookings = SlotBooking::all();

if ($needTutorInfo) {
    $bookings->load('tutor');
}
```

**Select Specific Columns**:

```php
// ❌ Bad: Select all columns
$users = User::all();

// ✅ Good: Select only needed columns
$users = User::select('id', 'name', 'email')->get();
```

**Query Chunking** (Large Datasets):

```php
// Process large datasets in chunks
User::chunk(200, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// Or use lazy collections
User::lazy()->each(function ($user) {
    // Process user
});
```

---

## 11. Caching Strategy

### 11.1 Cache Configuration

**File**: `config/cache.php`

```php
return [
    'default' => env('CACHE_STORE', 'database'),
    
    'stores' => [
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        ],
        
        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
    ],
];
```

### 11.2 Cache Usage Patterns

**Cache Forever** (Static Data):

```php
use Illuminate\Support\Facades\Cache;

// Cache role by name (rarely changes)
$role = Cache::rememberForever('getRoleByName-' . $name, function () use ($name) {
    return Role::where('name', $name)->first();
});
```

**Time-Based Cache**:

```php
// Cache for 1 hour
$tutors = Cache::remember('featured-tutors', 3600, function () {
    return User::role('tutor')
        ->where('featured', true)
        ->with('profile')
        ->get();
});

// Cache until specific time
$settings = Cache::remember('site-settings', now()->addDay(), function () {
    return OptionBuilder::all();
});
```

**Cache Tags** (Redis/Memcached only):

```php
// Cache with tags for easy invalidation
Cache::tags(['users', 'tutors'])->put('tutor-list', $tutors, 3600);

// Invalidate all caches with tag
Cache::tags(['tutors'])->flush();
```

**Cache Invalidation**:

```php
// Forget specific cache
Cache::forget('featured-tutors');

// Forget multiple caches
Cache::forget('menu-header');
Cache::forget('menu-footer');

// Clear all cache
Cache::flush();

// Clear cache on model update
class User extends Model
{
    protected static function booted()
    {
        static::updated(function ($user) {
            Cache::forget('featured-tutors');
            Cache::forget('tutor-' . $user->id);
        });
    }
}
```

### 11.3 Cache Implementation Examples

**Settings Cache** (helpers.php):

```php
function setting($key = null, $default = null)
{
    $settings = Cache::rememberForever('optionbuilder__settings', function () {
        return OptionBuilder::getAllSettings();
    });
    
    if (is_null($key)) {
        return $settings;
    }
    
    return data_get($settings, $key, $default);
}

// Clear settings cache
function clearSettingsCache()
{
    Cache::forget('optionbuilder__settings');
}
```

**Menu Cache** (SiteService):

```php
public function getMenu($location, $name)
{
    return Cache::rememberForever('menu-' . $location . '-' . $name, function () use ($location, $name) {
        return Menu::where('location', $location)
            ->where('name', $name)
            ->with('items')
            ->first();
    });
}
```

**User Timezone Cache**:

```php
$timezone = Cache::rememberForever('userTimeZone_' . $user->id, function () use ($user) {
    return $user->account_settings['timezone'] ?? config('app.timezone');
});
```

---

## 12. Database Optimization

### 12.1 Database Indexing

**Indexes in Migrations**:

```php
// database/migrations/2024_06_25_071342_create_permission_tables.php

Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('guard_name');
    $table->timestamps();
    
    // Composite unique index
    $table->unique(['name', 'guard_name']);
});

Schema::create('model_has_permissions', function (Blueprint $table) {
    $table->unsignedBigInteger('permission_id');
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    
    // Composite index for faster lookups
    $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
    
    // Primary key
    $table->primary(['permission_id', 'model_id', 'model_type']);
});
```

**Common Indexing Patterns**:

```php
Schema::create('slot_bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('slot_id')->constrained()->onDelete('cascade');
    $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('booker_id')->constrained('users')->onDelete('cascade');
    $table->timestamp('start_datetime');
    $table->timestamp('end_datetime');
    $table->string('status')->default('pending');
    $table->timestamps();
    
    // Index frequently queried columns
    $table->index('tutor_id');
    $table->index('booker_id');
    $table->index('status');
    $table->index('start_datetime');
    
    // Composite index for common query
    $table->index(['tutor_id', 'status']);
});
```

### 12.2 Query Performance

**Explain Queries** (Development):

```php
// Check query performance
DB::enableQueryLog();

$tutors = User::role('tutor')
    ->with('profile')
    ->where('status', 'active')
    ->get();

dd(DB::getQueryLog());
```

**Optimize Counts**:

```php
// ❌ Slow: Load all records
$count = User::all()->count();

// ✅ Fast: Database count
$count = User::count();

// ✅ Fast: Exists check
$hasBookings = SlotBooking::where('user_id', $userId)->exists();
```

**Pagination**:

```php
// ❌ Bad: Load all then paginate
$tutors = User::role('tutor')->get()->paginate(20);

// ✅ Good: Database pagination
$tutors = User::role('tutor')->paginate(20);

// ✅ Better: Cursor pagination (large datasets)
$tutors = User::role('tutor')->cursorPaginate(20);
```

### 12.3 Database Connection

**Connection Pooling** (Production):

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
],
```

---

## 13. Queue Optimization

### 13.1 Queue Configuration

**File**: `config/queue.php`

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),
    
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],
    ],
];
```

### 13.2 Job Optimization

**Job Priority**:

```php
// High priority queue
dispatch(new SendNotificationJob($user))->onQueue('high');

// Low priority queue
dispatch(new GenerateReportJob())->onQueue('low');

// Run workers by priority
php artisan queue:work --queue=high,default,low
```

**Job Batching**:

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new SendNotificationJob($user1),
    new SendNotificationJob($user2),
    new SendNotificationJob($user3),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure
})->finally(function (Batch $batch) {
    // Batch finished executing
})->dispatch();
```

**Job Rate Limiting**:

```php
use Illuminate\Support\Facades\RateLimiter;

class SendEmailJob implements ShouldQueue
{
    public function handle()
    {
        RateLimiter::attempt(
            'send-email:' . $this->user->id,
            $maxAttempts = 5,
            function () {
                // Send email
            },
            $decaySeconds = 60
        );
    }
}
```

### 13.3 Queue Monitoring

**Queue Health Check**:

```php
// app/Helpers/helpers.php
function isQueueWorking()
{
    $dispatched = Cache::get('queue_heartbeat_dispatched_at');
    $processed = Cache::get('queue_heartbeat_processed_at');
    
    if (!$dispatched || !$processed) {
        return false;
    }
    
    // Check if processed within 5 minutes
    return $processed->diffInMinutes(now()) < 5;
}
```

**Queue Heartbeat Job**:

```php
// app/Jobs/QueueHeartbeatJob.php
class QueueHeartbeatJob implements ShouldQueue
{
    public function handle()
    {
        Cache::put('queue_heartbeat_processed_at', now());
    }
}

// Schedule heartbeat
// bootstrap/app.php
$schedule->job(new QueueHeartbeatJob())->everyMinute();
```

---

## 14. Asset Optimization

### 14.1 Vite Asset Building

**Production Build**:

```bash
# Build optimized assets
npm run build

# Assets are automatically:
# - Minified
# - Tree-shaken
# - Code-split
# - Hashed for cache-busting
```

**Vite Configuration**: `vite.config.js`

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['jquery', 'bootstrap'],
                    livewire: ['livewire'],
                },
            },
        },
    },
});
```

### 14.2 Image Optimization

**Image Intervention**:

```php
use Intervention\Image\Facades\Image;

public function uploadAvatar(Request $request)
{
    $image = $request->file('avatar');
    
    // Resize and optimize
    $optimized = Image::make($image)
        ->fit(300, 300) // Crop to square
        ->encode('jpg', 85); // Quality 85%
    
    // Save optimized image
    Storage::put('avatars/' . $filename, $optimized);
}
```

**Lazy Loading**:

```blade
{{-- Lazy load images --}}
<img src="{{ $placeholder }}" 
     data-src="{{ $image }}" 
     loading="lazy"
     alt="{{ $alt }}">
```

### 14.3 CDN Integration

**Serve Assets from CDN**:

```php
// .env
ASSET_URL=https://cdn.yourdomain.com

// Automatic CDN URL
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
// Outputs: https://cdn.yourdomain.com/css/app.css
```

---

## 15. Monitoring & Logging

### 15.1 Laravel Telescope

**Development Monitoring**:

```bash
# Install Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Access**: `http://yourdomain.com/telescope`

**Features**:
- Request monitoring
- Exception tracking
- Database queries
- Cache operations
- Queue jobs
- Mail preview

### 15.2 Logging Configuration

**File**: `config/logging.php`

```php
return [
    'default' => env('LOG_CHANNEL', 'stack'),
    
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'slack'],
            'ignore_exceptions' => false,
        ],
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'level' => 'critical',
        ],
    ],
];
```

**Usage**:

```php
use Illuminate\Support\Facades\Log;

// Different log levels
Log::emergency('System down');
Log::alert('Action required');
Log::critical('Critical error');
Log::error('Error occurred');
Log::warning('Warning message');
Log::notice('Notice message');
Log::info('Info message');
Log::debug('Debug message');

// With context
Log::error('Payment failed', [
    'user_id' => $user->id,
    'amount' => $amount,
    'error' => $exception->getMessage(),
]);
```

### 15.3 Error Tracking

**Production Error Handling**:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        // Log to external service (Sentry, Bugsnag, etc.)
        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    });
})
```

---

## Summary

**Security Measures**:
- ✅ CSRF protection enabled
- ✅ SQL injection prevention (Eloquent)
- ✅ XSS protection (Blade escaping)
- ✅ Password hashing (bcrypt)
- ✅ Role-based access control (Spatie)
- ✅ Rate limiting
- ✅ Session security
- ✅ API token authentication
- ✅ Input validation
- ✅ File upload security
- ✅ Security headers

**Performance Optimizations**:
- ✅ Database caching
- ✅ Query optimization
- ✅ Eager loading
- ✅ Queue system
- ✅ Asset minification (Vite)
- ✅ Database indexing
- ✅ Redis caching (optional)
- ✅ CDN support

**Monitoring**:
- ✅ Laravel Telescope (dev)
- ✅ Log aggregation
- ✅ Queue health checks
- ✅ Error tracking

**Production Checklist**:
- [ ] `APP_DEBUG=false`
- [ ] Force HTTPS
- [ ] Secure cookies enabled
- [ ] Strong passwords enforced
- [ ] Rate limiting active
- [ ] Database backups configured
- [ ] Log rotation enabled
- [ ] Cache configured (Redis recommended)
- [ ] Queue workers running
- [ ] Security headers enabled
