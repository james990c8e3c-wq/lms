# PHASE 3: AUTHENTICATION & AUTHORIZATION ANALYSIS

## Table of Contents
1. [Authentication System](#authentication-system)
2. [Authorization & Permissions](#authorization--permissions)
3. [Role Hierarchy](#role-hierarchy)
4. [Middleware Stack](#middleware-stack)
5. [Session Management](#session-management)
6. [Security Features](#security-features)

---

## 1. Authentication System

### 1.1 Authentication Guards & Providers

**Configuration**: `config/auth.php`

```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'web'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],
],
```

**Key Components**:
- **Guard**: `web` - Session-based authentication
- **Provider**: Eloquent User model
- **Driver**: Session storage with cookies
- **Password Reset**: 60-minute token expiry, 60-second throttle
- **Password Confirmation**: 3-hour timeout (10800 seconds)

### 1.2 User Model Implementation

**File**: `app/Models/User.php`

**Authentication Traits**:
```php
use Authenticatable;
use MustVerifyEmail;
use HasRoles;           // Spatie Permission
use CanResetPassword;
use Chatable;           // LaraGuppy chat
```

**Key Fields**:
- `email` - Unique identifier
- `password` - Hashed password
- `provider` - OAuth provider (google, facebook, etc.)
- `provider_id` - OAuth provider ID
- `email_verified_at` - Email verification timestamp
- `default_role` - Default role: admin, sub_admin, tutor, student

**Authentication Features**:
1. Email/password authentication
2. OAuth social login (Google, Facebook via Laravel Socialite)
3. Email verification required
4. Password reset functionality
5. Remember me functionality

### 1.3 Social Authentication

**File**: `app/Http/Controllers/Auth/SocialController.php`

**Supported Providers**:
- Google
- Facebook
- Other Laravel Socialite providers

**Routes**:
```php
Route::get('auth/{provider}', [SocialController::class, 'redirect'])
Route::get('auth/{provider}/callback', [SocialController::class, 'callback'])
```

**Flow**:
1. User clicks social login button → redirect to provider
2. User authorizes → callback with provider token
3. System retrieves/creates user with `provider` and `provider_id`
4. Assigns default role based on user type
5. Logs in user automatically

### 1.4 Email Verification

**Implementation**: Laravel's `MustVerifyEmail` contract

**Controller**: `app/Http/Controllers/Auth/VerifyEmailController.php`

**Features**:
- Email sent upon registration
- Signed URL for security
- 60-minute expiry
- Middleware: `verified` blocks unverified users

---

## 2. Authorization & Permissions

### 2.1 Spatie Permission Package

**Package**: `spatie/laravel-permission` v6.9

**Configuration**: `config/permission.php`

```php
'table_names' => [
    'roles' => 'roles',
    'permissions' => 'permissions',
    'model_has_permissions' => 'model_has_permissions',
    'model_has_roles' => 'model_has_roles',
    'role_has_permissions' => 'role_has_permissions',
],

'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role' => Spatie\Permission\Models\Role::class,
],

'register_permission_check_method' => true, // Enables can() method
'teams' => false, // Multi-tenancy disabled
```

**Database Tables**:
1. `roles` - Role definitions (admin, sub_admin, tutor, student)
2. `permissions` - Permission definitions (granular access control)
3. `model_has_roles` - User-Role assignments (many-to-many)
4. `model_has_permissions` - Direct user permissions (bypass roles)
5. `role_has_permissions` - Role-Permission mappings

### 2.2 Role System

**Primary Roles**:
1. **admin** - Full system access
2. **sub_admin** - Limited administrative access
3. **tutor** - Tutor-specific features
4. **student** - Student-specific features

**Role Storage**:
- **Database**: `roles` table via Spatie
- **User Model**: `default_role` field (admin|sub_admin|tutor|student)
- **Session**: `active_role_id` - Current active role for multi-role users

**Role Switching**:
Users with multiple roles can switch via `getUserRole()` helper:
```php
function getUserRole() {
    $auth = Auth::user();
    $activeRoleId = Session::get('active_role_id' . $auth->id);
    $role = $activeRoleId 
        ? Role::find($activeRoleId) 
        : Role::where('name', $auth->default_role)->first();
    
    return [
        'userId' => $auth->id,
        'profileId' => $auth->profile?->id,
        'roleId' => $role?->id,
        'roleName' => $role?->name
    ];
}
```

### 2.3 Permission System

**Permission Check Methods**:
1. **Middleware**: `RoleMiddleware`, `PermitOfMiddleware`
2. **Blade Directives**: `@role()`, `@hasrole()`, `@can()`
3. **Model Methods**: `$user->can('permission')`, `$user->hasRole('admin')`
4. **Gate Checks**: `Gate::allows('permission')`

**Permission Scope**:
- No policy files found - system relies on direct permission checks
- Permissions managed through Spatie's database tables
- Custom permission seeding via `UpdateRoleSeeder`

### 2.4 Role-Based Access Control (RBAC) Matrix

| Feature Area | Admin | Sub Admin | Tutor | Student |
|-------------|-------|-----------|-------|---------|
| User Management | ✅ Full | ⚠️ Limited | ❌ | ❌ |
| Site Settings | ✅ | ❌ | ❌ | ❌ |
| Create Subjects | ✅ | ✅ | ✅ | ❌ |
| Create Sessions | ✅ | ❌ | ✅ | ❌ |
| Book Sessions | ❌ | ❌ | ✅ | ✅ |
| View Earnings | ✅ | ✅ | ✅ | ❌ |
| Wallet Withdrawals | ❌ | ❌ | ✅ | ❌ |
| Dispute Resolution | ✅ | ✅ | ✅ | ✅ |
| Platform Commission | ✅ | ⚠️ View Only | ❌ | ❌ |
| Module Management | ✅ | ❌ | ❌ | ❌ |

---

## 3. Role Hierarchy

### 3.1 Role Determination Logic

**Seeder**: `database/seeders/UpdateRoleSeeder.php`

```php
public function run(): void {
    $users = User::with('profile')->get();
    foreach ($users as $user) {
        if ($user->hasRole('admin')) {
            $user->default_role = 'admin';
        } else {
            // Tutor has tagline in profile, student doesn't
            $user->default_role = $user->profile?->tagline ? 'tutor' : 'student';
        }
        $user->save();
    }
}
```

**Role Priority** (Highest → Lowest):
1. **admin** - Superuser, system owner
2. **sub_admin** - Administrator with limited permissions
3. **tutor** - Service provider (creates sessions)
4. **student** - Service consumer (books sessions)

### 3.2 Role Assignment Rules

**New User Registration**:
1. User registers with email/password or OAuth
2. System assigns `default_role = 'student'` initially
3. User completes tutor profile (adds tagline) → becomes tutor
4. Admin manually assigns `admin` or `sub_admin` roles

**Multi-Role Users**:
- Users can have both `tutor` AND `student` roles simultaneously
- Session stores `active_role_id` to track current context
- Role switching via `SiteController::switchRole()`

### 3.3 Role-Specific Dashboards

**Dashboard Routes**:
```php
// Tutor Dashboard
Route::get('tutor/dashboard', ManageAccount::class)
    ->middleware('role:tutor')

// Student Dashboard  
Route::get('student/bookings', UserBooking::class)
    ->middleware('role:student')

// Admin Dashboard
Route::get('admin/dashboard', GeneralController::class)
    ->middleware('role:admin|sub_admin')
```

**Redirect Logic** (`User::redirectAfterLogin()`):
```php
public function redirectAfterLogin() {
    $role = $this->role; // From getUserRole() helper
    
    return match($role) {
        'admin', 'sub_admin' => route('admin.dashboard'),
        'tutor' => route('tutor.dashboard'),
        'student' => route('student.bookings'),
        default => route('home')
    };
}
```

---

## 4. Middleware Stack

### 4.1 Authentication Middleware

**Built-in Laravel Middleware**:
1. **Authenticate** (`auth`) - Ensures user is logged in
2. **RedirectIfAuthenticated** - Redirects authenticated users from guest pages
3. **EnsureEmailIsVerified** (`verified`) - Blocks unverified users

**Route Application**:
```php
Route::middleware(['auth', 'verified'])->group(function () {
    // All authenticated routes
});
```

### 4.2 Custom Authorization Middleware

#### RoleMiddleware

**File**: `app/Http/Middleware/RoleMiddleware.php`

**Purpose**: Check if user has required role(s)

**Implementation**:
```php
public function handle($request, Closure $next, ...$roles) {
    // Check bearer token for API clients
    if ($request->bearerToken()) {
        $user = User::where('personal_access_token', $request->bearerToken())->first();
        Auth::setUser($user);
    }
    
    // Validate HasRoles trait
    if (!Auth::user() || !method_exists(Auth::user(), 'hasRole')) {
        throw new UnauthorizedException(403, 'User does not have HasRoles trait');
    }
    
    // Get active role from session
    $userRole = getUserRole()['roleName'];
    
    // Parse allowed roles (pipe-separated string)
    $allowedRoles = $this->parseRolesToString($roles);
    
    // Check if user's active role matches any allowed role
    if (!in_array($userRole, explode('|', $allowedRoles))) {
        throw UnauthorizedException::forRoles($allowedRoles);
    }
    
    return $next($request);
}
```

**Usage**:
```php
Route::middleware('role:tutor|student')->get('/checkout')
Route::middleware('role:admin|sub_admin')->prefix('admin')
```

#### PermitOfMiddleware

**File**: `app/Http/Middleware/PermitOfMiddleware.php`

**Purpose**: Check if user has specific permission

**Implementation**:
```php
public function handle($request, Closure $next, $permission) {
    if (!Auth::user()->can($permission)) {
        abort(403, 'Unauthorized action.');
    }
    return $next($request);
}
```

**Usage**:
```php
Route::middleware('permit_of:edit-users')->get('/users/edit')
```

### 4.3 Additional Middleware

**File**: `app/Http/Middleware/`

1. **CheckLocale** - Sets application locale from session/cookie
2. **CheckModuleEnabled** - Validates required modules are active
3. **CheckMaintenanceMode** (`maintenance`) - Blocks access during maintenance
4. **UserOnline** (`onlineUser`) - Updates user's last activity timestamp

**Middleware Groups**:
```php
Route::middleware(['locale', 'maintenance'])->group(function () {
    // Public routes with locale and maintenance check
    
    Route::middleware(['auth', 'verified', 'onlineUser'])->group(function () {
        // Authenticated routes with activity tracking
    });
});
```

### 4.4 Middleware Priority

**Execution Order**:
1. `locale` - Set language
2. `maintenance` - Check if site accessible
3. `auth` - Verify authentication
4. `verified` - Check email verification
5. `onlineUser` - Update activity
6. `role:X` - Check role access
7. `permit_of:X` - Check specific permission

---

## 5. Session Management

### 5.1 Session Storage

**Configuration**: `config/session.php`

**Driver**: Database (recommended for multi-server setups)

**Session Data Stored**:
- User authentication state
- Active role ID: `active_role_id{userId}`
- User metadata: `userId`, `profileId`, `roleId`, `roleName`
- Cart contents
- Payment data (temporary)
- Flash messages (success/error)

### 5.2 getUserRole() Helper

**File**: `app/Helpers/helpers.php` (line 440)

**Purpose**: Central role resolution for authorization

**Logic**:
1. Check if user authenticated
2. Retrieve `active_role_id` from session
3. If set, fetch Role by ID
4. Otherwise, fetch Role by `default_role` name
5. Cache result in session variables
6. Return associative array with user/profile/role data

**Return Structure**:
```php
[
    'userId' => 123,
    'profileId' => 456,
    'roleId' => 3,
    'roleName' => 'tutor'
]
```

**Usage Throughout Application**:
- RoleMiddleware relies on this for authorization
- User model's `role` accessor calls this
- Dashboard redirection uses this
- Service classes use this for context-aware queries

### 5.3 Role Switching

**Route**: `POST /switch-role`

**Controller**: `SiteController::switchRole()`

**Process**:
1. User with multiple roles clicks "Switch to Tutor/Student"
2. Request sent with desired role name
3. System validates user has that role
4. Updates `active_role_id{userId}` in session
5. Redirects to role-specific dashboard
6. All subsequent requests use new role context

### 5.4 Session Security

**Features**:
- CSRF protection on all POST/PUT/DELETE routes
- HTTP-only cookies (prevents JavaScript access)
- Secure cookies in production (HTTPS only)
- SameSite=Lax (CSRF mitigation)
- Session regeneration on login (prevents fixation attacks)
- 120-minute lifetime (configurable)

---

## 6. Security Features

### 6.1 Password Security

**Hashing**: Bcrypt (Laravel default, cost factor 12)

**Requirements**:
- Minimum 8 characters (configurable)
- Password confirmation required
- Password reset via signed, expiring URLs

**Helper**: `generatePassword()` (random 8-char with special chars)

### 6.2 Input Sanitization

**Helpers**: `app/Helpers/helpers.php`

```php
// Sanitize single text field
sanitizeTextField($string, $keep_linebreak = false)
// - HTML entity decode
// - Strip script/style tags
// - Remove line breaks (optional)
// - Clean with HTMLPurifier

// Sanitize arrays recursively
SanitizeArray(&$arr)

// Strip all HTML except safe tags
stripAllTags($string, $remove_breaks = false)
// Allows: h1-h6, div, b, strong, i, em, a, ul, ol, li, p, br, span, etc.
```

**Application**:
- All user input passed through sanitizers
- Validation rules enforce data types
- XSS prevention via Laravel Blade auto-escaping

### 6.3 Authorization Checks

**Layers of Protection**:
1. **Route Middleware**: Block unauthorized route access
2. **Controller Guards**: Verify ownership before actions
3. **Eloquent Scopes**: Filter queries by user role
4. **Blade Directives**: Hide UI elements from unauthorized users

**Example - Booking Authorization**:
```php
// Middleware blocks non-students from student.bookings route
Route::middleware('role:student')->get('bookings')

// Controller verifies booking ownership
$booking = SlotBooking::where('student_id', Auth::id())->findOrFail($id);

// Service class filters by role
->when($this->user->role == 'student', fn($q) => $q->whereStudentId($this->user->id))

// Blade hides complete button unless student owns booking
@if(auth()->user()->id === $booking->student_id)
```

### 6.4 API Authentication

**Method**: Laravel Sanctum (token-based)

**Implementation**:
- Personal access tokens
- API tokens stored in `personal_access_tokens` table
- Stateless authentication for mobile/SPA
- Token abilities for granular permissions

**RoleMiddleware Support**:
```php
// Checks bearer token if present
if ($request->bearerToken()) {
    $user = User::where('personal_access_token', $request->bearerToken())->first();
    Auth::setUser($user);
}
```

### 6.5 Additional Security Measures

**User Activity Tracking**:
- `UserOnline` middleware updates `last_seen_at`
- `User::isOnline()` checks cache for recent activity
- IP Manager module logs IP addresses, user agents, locations

**Identity Verification**:
- Users can upload identity documents
- Admin approval required
- Stored in `user_identity_verifications` table
- Verified badge displayed on profile

**Dispute Protection**:
- Students can raise disputes on completed bookings
- Funds held in escrow during dispute resolution
- Admin mediates disputes

**Rate Limiting**:
- Password reset throttling (60 seconds)
- API rate limiting via Sanctum
- Laravel's built-in throttle middleware

---

## Summary

**Authentication**:
- Session-based `web` guard with Eloquent User provider
- Email/password and OAuth social login (Google, Facebook)
- Email verification required (`MustVerifyEmail`)
- Password reset with 60-minute token expiry

**Authorization**:
- Spatie Permission package for RBAC
- 4 primary roles: admin, sub_admin, tutor, student
- Role switching for multi-role users via session
- Custom `RoleMiddleware` and `PermitOfMiddleware`
- No policy files - direct permission checks

**Key Helper**:
- `getUserRole()` - Central role resolution from session/database
- Returns current active role for authorization checks
- Used throughout middleware, controllers, services

**Security**:
- CSRF protection, HTTP-only cookies, secure sessions
- Input sanitization via custom helpers
- Bcrypt password hashing
- Laravel Sanctum for API authentication
- Multi-layer authorization (middleware, controllers, queries)

**Session Management**:
- Database driver for distributed systems
- Active role stored per user ID
- Cart and payment data in session
- 120-minute lifetime with regeneration on login
