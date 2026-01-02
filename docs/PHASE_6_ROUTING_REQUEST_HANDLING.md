# PHASE 6: ROUTING & REQUEST HANDLING

## Table of Contents
1. [Route Architecture Overview](#route-architecture-overview)
2. [Web Routes Analysis](#web-routes-analysis)
3. [Admin Routes Analysis](#admin-routes-analysis)
4. [API Routes Analysis](#api-routes-analysis)
5. [Middleware Stack](#middleware-stack)
6. [Route Groups & Prefixes](#route-groups--prefixes)
7. [Livewire Component Routing](#livewire-component-routing)
8. [API Versioning & Authentication](#api-versioning--authentication)

---

## 1. Route Architecture Overview

### 1.1 Route File Structure

**Directory**: `routes/`

**Route Files**:
```
routes/
├── web.php           - Public & authenticated web routes (119 lines)
├── admin.php         - Admin panel routes (150+ lines)
├── api.php           - RESTful API routes (112 lines)
├── auth.php          - Laravel Breeze auth routes
├── channels.php      - Broadcasting channels
├── console.php       - Artisan commands
├── breadcrumbs.php   - Breadcrumb definitions
├── optionbuilder.php - Dynamic settings routes
└── pagebuilder.php   - Page builder routes
```

**Total Routes**: 150+ web routes, 60+ admin routes, 50+ API routes

### 1.2 Route Organization Strategy

**Separation by Concern**:
1. **Public Routes**: Landing pages, search, tutor profiles (no auth)
2. **Authenticated Routes**: User-specific features (auth required)
3. **Role-Specific Routes**: Tutor/Student/Admin sections (role middleware)
4. **API Routes**: Mobile app & SPA endpoints (Sanctum auth)
5. **Special Routes**: Payment callbacks, webhooks, OAuth

**Middleware Layering**:
```
Global Middleware (All Routes)
  ↓
Locale & Maintenance (Web Routes)
  ↓
Authentication (Authenticated Routes)
  ↓
Role Verification (Role-Specific Routes)
  ↓
Permission Check (Admin Routes)
  ↓
Controller/Livewire
```

### 1.3 Route Naming Convention

**Pattern**: `{role}.{section}.{action}`

**Examples**:
- `tutor.dashboard` - Tutor dashboard
- `tutor.bookings.subjects` - Manage subjects
- `student.bookings` - Student bookings list
- `admin.users` - Admin user management
- `api.tutors.index` - API tutor listing

**Benefits**:
- Easy to reference in code: `route('tutor.dashboard')`
- Grouped by role for quick discovery
- Blade helper: `{{ route('student.invoices') }}`
- Livewire navigation: `$this->redirect(route('tutor.payouts'))`

---

## 2. Web Routes Analysis

**File**: `routes/web.php` (119 lines)

### 2.1 Public Routes (No Auth Required)

**Social Authentication**:
```php
Route::get('auth/{provider}', [SocialController::class, 'redirect'])
    ->name('social.redirect');
Route::get('auth/{provider}/callback', [SocialController::class, 'callback'])
    ->name('social.callback');
```
- `{provider}` - google, facebook, twitter, etc.
- Handled by Laravel Socialite

**Language Translator**:
```php
Route::view('language-translator', 'language-translator');
```
- Admin tool for managing translations

### 2.2 Locale & Maintenance Group

**Middleware**: `['locale', 'maintenance']`

**Applies to**: All remaining web routes

**Public Search & Browse**:
```php
Route::get('find-tutors', [SearchController::class, 'findTutors'])
    ->name('find-tutors');
Route::get('/blogs', Blogs::class)->name('blogs');
Route::get('/blog/{slug}', BlogDetails::class)->name('blog-details');
Route::get('tutor/{slug}', [SearchController::class, 'tutorDetail'])
    ->name('tutor-detail');
```

**Purpose**:
- Accessible to guests (no login required)
- SEO-friendly URLs
- Public marketing/content pages

### 2.3 Authenticated User Group

**Middleware**: `['auth', 'verified', 'onlineUser']`

**Applies to**: All user-specific features

**Common Authenticated Routes**:
```php
// AI Features
Route::post('/openai/submit', [OpenAiController::class, 'submit'])
    ->name('openai.submit');

// Favorites
Route::post('favourite-tutor', [SearchController::class, 'favouriteTutor'])
    ->name('favourite-tutor');

// Account Management
Route::get('logout', [SiteController::class, 'logout'])->name('logout');
Route::post('switch-role', [SiteController::class, 'switchRole'])
    ->name('switch-role');

// Identity Verification
Route::get('user/identity-confirmation/{id}', 
    [PersonalDetails::class, 'confirmParentVerification'])
    ->name('confirm-identity');

// Google Calendar OAuth
Route::get('google/callback', [SiteController::class, 'getGoogleToken']);
```

**Shared Tutor/Student Routes**:
```php
Route::middleware('role:tutor|student')->group(function () {
    Route::get('checkout', Checkout::class)->name('checkout');
    Route::get('thank-you/{id}', ThankYou::class)->name('thank-you');
});
```

### 2.4 Tutor Routes Group

**Prefix**: `/tutor`

**Middleware**: `['auth', 'verified', 'onlineUser', 'role:tutor']`

**Name Prefix**: `tutor.`

**Route Structure**:
```php
Route::middleware('role:tutor')->prefix('tutor')->name('tutor.')->group(function () {
    // Dashboard
    Route::get('dashboard', ManageAccount::class)->name('dashboard');
    Route::get('payouts', Payouts::class)->name('payouts');
    
    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('personal-details', PersonalDetails::class)
            ->name('personal-details');
        Route::get('account-settings', AccountSettings::class)
            ->name('account-settings');
        
        // Resume Subsection
        Route::prefix('resume')->name('resume.')->group(function () {
            Route::get('education', Resume::class)->name('education');
            Route::get('experience', Resume::class)->name('experience');
            Route::get('certificate', Resume::class)->name('certificate');
        });
        
        Route::get('identification', IdentityVerification::class)
            ->name('identification');
    });
    
    // Booking Management
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('manage-subjects', ManageSubjects::class)
            ->name('subjects');
        Route::get('manage-sessions', MyCalendar::class)
            ->name('manage-sessions');
        Route::get('session-detail/{date}', SessionDetail::class)
            ->name('session-detail');
        Route::get('upcoming-bookings', UserBooking::class)
            ->name('upcoming-bookings');
    });
    
    // Financial
    Route::get('invoices', Invoices::class)->name('invoices');
    
    // Disputes
    Route::get('disputes', Dispute::class)->name('disputes');
    Route::get('manage-dispute/{id}', ManageDispute::class)
        ->name('manage-dispute');
});
```

**Generated URLs**:
- `/tutor/dashboard` → `tutor.dashboard`
- `/tutor/profile/personal-details` → `tutor.profile.personal-details`
- `/tutor/profile/resume/education` → `tutor.profile.resume.education`
- `/tutor/bookings/manage-subjects` → `tutor.bookings.subjects`
- `/tutor/bookings/session-detail/2026-01-15` → `tutor.bookings.session-detail`

**Total Tutor Routes**: 15 routes

### 2.5 Student Routes Group

**Prefix**: `/student`

**Middleware**: `['auth', 'verified', 'onlineUser', 'role:student']`

**Name Prefix**: `student.`

**Route Structure**:
```php
Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
    // Profile Routes (shares some with tutor)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('personal-details', PersonalDetails::class)
            ->name('personal-details');
        Route::get('account-settings', AccountSettings::class)
            ->name('account-settings');
        Route::get('identification', IdentityVerification::class)
            ->name('identification');
    });
    
    // Student-Specific Features
    Route::get('bookings', UserBooking::class)->name('bookings');
    Route::get('invoices', Invoices::class)->name('invoices');
    Route::get('billing-detail', BillingDetail::class)
        ->name('billing-detail');
    Route::get('favourites', Favourites::class)->name('favourites');
    
    // Session Management
    Route::get('reschedule-session/{id}', RescheduleSession::class)
        ->name('reschedule-session');
    Route::get('complete-booking/{id}', 
        [SiteController::class, 'completeBooking'])
        ->name('complete-booking');
    
    // Certificates
    Route::get('certificates', CertificateList::class)
        ->name('certificate-list');
    
    // Disputes
    Route::get('disputes', Dispute::class)->name('disputes');
    Route::get('manage-dispute/{id}', ManageDispute::class)
        ->name('manage-dispute');
});
```

**Generated URLs**:
- `/student/bookings` → `student.bookings`
- `/student/invoices` → `student.invoices`
- `/student/favourites` → `student.favourites`
- `/student/reschedule-session/123` → `student.reschedule-session`

**Total Student Routes**: 12 routes

### 2.6 Shared/Global Routes

**Payment Processing**:
```php
Route::post('/remove-cart', [SiteController::class, 'removeCart']);
Route::get('{gateway}/process/payment', 
    [SiteController::class, 'processPayment'])
    ->name('payment.process');
Route::get('checkout/cancel', fn() => redirect()->route('invoices'))
    ->name('checkout.cancel');
```

**Payment Callbacks & Webhooks**:
```php
Route::post('payfast/webhook', [SiteController::class, 'payfastWebhook'])
    ->name('payfast.webhook');
Route::post('payment/success', [SiteController::class, 'paymentSuccess'])
    ->name('post.success');
Route::get('payment/success', [SiteController::class, 'paymentSuccess'])
    ->name('get.success');
```

**Utility Routes**:
```php
Route::post('switch-lang', [SiteController::class, 'switchLang'])
    ->name('switch-lang');
Route::post('switch-currency', [SiteController::class, 'switchCurrency'])
    ->name('switch-currency');
Route::get('exit-impersonate', [Impersonate::class, 'exitImpersonate'])
    ->name('exit-impersonate');
```

**Direct Booking**:
```php
Route::get('pay/{id}', [SiteController::class, 'preparePayment'])
    ->name('pay');
Route::get('session/{id}', [SiteController::class, 'sessionDetail'])
    ->name('session-detail');
Route::post('book-session', [SiteController::class, 'bookSession'])
    ->name('book-session');
```

### 2.7 Included Route Files

**At End of web.php**:
```php
require __DIR__ . '/auth.php';           // Laravel Breeze auth routes
require __DIR__ . '/admin.php';          // Admin panel routes
require __DIR__ . '/optionbuilder.php';  // Dynamic settings
if (!request()->is('api/*')) {
    require __DIR__ . '/pagebuilder.php'; // Page builder (not for API)
}
```

**Purpose**: Modular organization, keeps web.php clean

---

## 3. Admin Routes Analysis

**File**: `routes/admin.php` (150+ lines)

### 3.1 Admin Route Group

**Prefix**: `/admin`

**Middleware**: `['auth', 'verified', 'role:admin|sub_admin']`

**Name Prefix**: `admin.`

**Structure**:
```php
Route::middleware(['auth', 'verified', 'role:admin|sub_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // All admin routes
    });
```

### 3.2 Dashboard & Analytics

```php
Route::get('/insights', Insights::class)
    ->name('insights')
    ->middleware('permit-of:can-manage-insights');
```

**Permission-Based**: Sub-admins may not have access

### 3.3 Profile & Settings

```php
Route::get('/profile', AdminProfile::class)->name('profile');
Route::get('/manage-menus', ManageMenu::class)
    ->name('manage-menus')
    ->middleware('permit-of:can-manage-menu');
```

### 3.4 Content Management

**Blogs**:
```php
Route::get('/blogs', Blogs::class)
    ->name('blog-listing')
    ->middleware('permit-of:can-manage-all-blogs');
Route::get('/blogs/create', CreateBlog::class)
    ->name('create-blog')
    ->middleware('permit-of:can-manage-create-blogs');
Route::get('/blogs/update/{id}', UpdateBlog::class)
    ->name('update-blog')
    ->middleware('permit-of:can-manage-update-blogs');
Route::get('/blog-categories', BlogCategories::class)
    ->name('blog-categories')
    ->middleware('permit-of:can-manage-blog-categories');
```

**Translation**:
```php
Route::get('language-translator', LanguageTranslator::class)
    ->name('language-translator')
    ->middleware('permit-of:can-manage-language-translations');
```

### 3.5 Taxonomy Management

**Prefix**: `/admin/taxonomies`

**Name Prefix**: `admin.taxonomy.`

```php
Route::prefix('taxonomies')->name('taxonomy.')->group(function () {
    Route::get('languages', Languages::class)
        ->name('languages')
        ->middleware('permit-of:can-manage-languages');
    Route::get('subjects', Subjects::class)
        ->name('subjects')
        ->middleware('permit-of:can-manage-subjects');
    Route::get('subject-groups', SubjectGroups::class)
        ->name('subject-groups')
        ->middleware('permit-of:can-manage-subject-groups');
});
```

**Generated URLs**:
- `/admin/taxonomies/languages` → `admin.taxonomy.languages`
- `/admin/taxonomies/subjects` → `admin.taxonomy.subjects`
- `/admin/taxonomies/subject-groups` → `admin.taxonomy.subject-groups`

### 3.6 Financial Management

**Commission & Payments**:
```php
Route::get('commission-settings', CommissionSettings::class)
    ->name('commission-settings')
    ->middleware('permit-of:can-manage-commission-settings');
Route::get('payment-methods', PaymentMethods::class)
    ->name('payment-methods')
    ->middleware('permit-of:can-manage-payment-methods');
Route::get('withdraw-requests', WithdrawRequest::class)
    ->name('withdraw-requests')
    ->middleware('permit-of:can-manage-withdraw-requests');
```

**Purpose**:
- Set platform commission percentage
- Enable/disable payment gateways
- Approve tutor payout requests

### 3.7 User Management

```php
Route::get('manage-admin-users', ManageAdminUsers::class)
    ->name('manage-admin-users')
    ->middleware('permit-of:can-manage-admin-users');
Route::get('users', Users::class)
    ->name('users')
    ->middleware('permit-of:can-manage-users');
Route::get('identity-verification', IdentityVerification::class)
    ->name('identity-verification')
    ->middleware('permit-of:can-manage-identity-verification');
```

**Features**:
- Create/edit/delete admin users
- Manage tutors and students
- Approve identity verification documents

### 3.8 Booking & Order Management

```php
Route::get('reviews', Reviews::class)
    ->name('reviews')
    ->middleware('permit-of:can-manage-reviews');
Route::get('bookings', Bookings::class)
    ->name('bookings')
    ->middleware('permit-of:can-manage-bookings');
Route::get('invoices', Invoices::class)
    ->name('invoices')
    ->middleware('permit-of:can-manage-invoices');
```

**Admin Views**:
- All bookings across platform
- Total revenue and commission
- Filter by tutor, student, subject, date

### 3.9 Notification Management

```php
Route::get('email-settings', EmailTemplates::class)
    ->name('email-settings')
    ->middleware('permit-of:can-manage-email-settings');
Route::get('notification-settings', NotificationTemplates::class)
    ->name('notification-settings')
    ->middleware('permit-of:can-manage-notification-settings');
```

**Purpose**: Customize email/notification templates

### 3.10 System Management

**Upgrade System**:
```php
Route::get('upgrade', Upgrade::class)
    ->name('upgrade')
    ->middleware('permit-of:can-manage-upgrade');
```

**SASS Styling**:
```php
Route::post('update-sass-style', 
    [GeneralController::class, 'updateSaas'])
    ->middleware('permit-of:can-manage-option-builder');
```

**Package Management**:
```php
Route::middleware('permit-of:can-manage-addons')
    ->prefix('packages')
    ->as('packages.')
    ->group(function () {
        Route::get('/', ManagePackages::class)->name('index');
        Route::get('installed', InstalledPackages::class)->name('installed');
        Route::post('upload', [GeneralController::class, 'uploadAddon'])
            ->name('upload');
    });
```

**Features**:
- Install/uninstall modules
- Upload custom addons
- View installed packages

### 3.11 Dispute Management

```php
Route::get('disputes', Dispute::class)
    ->name('disputes')
    ->middleware('permit-of:can-manage-disputes-list');
Route::get('manage-dispute/{id}', ManageDispute::class)
    ->name('manage-dispute')
    ->middleware('permit-of:can-manage-dispute');
```

**Admin Actions**:
- View all disputes
- Read conversation threads
- Resolve in favor of student or tutor

### 3.12 System Utilities

```php
Route::get('clear-cache', [GeneralController::class, 'clearCache'])
    ->name('clear-cache');
Route::get('check-queue', [GeneralController::class, 'checkQueue'])
    ->name('check-queue');
```

**Purpose**: Debug and maintenance tools

### 3.13 Configuration Updates (POST Routes)

```php
Route::post('update-smtp-settings', 
    [GeneralController::class, 'updateSMTPSettings'])
    ->name('update-smtp-settings')
    ->middleware('permit-of:can-manage-option-builder');
Route::post('update-broadcasting-settings', 
    [GeneralController::class, 'updateBroadcastingSettings'])
    ->name('update-broadcasting-settings')
    ->middleware('permit-of:can-manage-option-builder');
Route::post('update-pusher-settings', 
    [GeneralController::class, 'updatePusherSettings'])
    ->name('update-pusher-settings')
    ->middleware('permit-of:can-manage-option-builder');
Route::post('update-reverb-settings', 
    [GeneralController::class, 'updateReverbSettings'])
    ->name('update-reverb-settings')
    ->middleware('permit-of:can-manage-option-builder');
Route::post('update-social-login-settings', 
    [GeneralController::class, 'updateSocialLoginSettings'])
    ->name('update-social-login-settings')
    ->middleware('permit-of:can-manage-option-builder');
```

**Purpose**: Save configuration changes from admin panel

### 3.14 Public Admin Route

**Outside Admin Group**:
```php
Route::get('download-invoice/{id}', 
    [SiteController::class, 'downloadPDF'])
    ->name('download.invoice');
```

**Purpose**: Invoice PDF accessible to students/tutors

**Total Admin Routes**: 60+ routes

---

## 4. API Routes Analysis

**File**: `routes/api.php` (112 lines)

### 4.1 API Route Prefix

**Automatic Prefix**: `/api` (configured in `RouteServiceProvider`)

**Middleware**: `api` middleware group (throttle, json response)

**Example**: Route defined as `/login` becomes `/api/login`

### 4.2 Public API Routes (No Auth)

**Authentication Endpoints**:
```php
Route::post('login', [AuthController::class, 'login']);
Route::post('social-login', [AuthController::class, 'socialLogin']);
Route::post('social-profile', [AuthController::class, 'createSocialProfile']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forget-password', [AuthController::class, 'resetEmailPassword']);
```

**Public Search**:
```php
Route::get('recommended-tutors', 
    [TutorController::class, 'getRecommendedTutors']);
Route::get('find-tutors', [TutorController::class, 'findTutots']);
Route::get('tutor/{slug}', [TutorController::class, 'getTutorDetail']);
Route::get('student-reviews/{id}', 
    [StudentController::class, 'getStudentReviews']);
Route::get('tutor-available-slots', 
    [TutorController::class, 'getTutorAvailableSlots']);
Route::get('slot-detail/{id}', [TutorController::class, 'slotDetail']);
```

**Public Resources** (Education/Experience/Certificates):
```php
Route::apiResource('tutor-education', EducationController::class)
    ->only(['show', 'store', 'update', 'destroy']);
Route::apiResource('tutor-experience', ExperienceController::class)
    ->only(['show', 'store', 'update', 'destroy']);
Route::apiResource('tutor-certification', CertificationController::class)
    ->only(['show', 'store', 'destroy']);
```

**Note**: `apiResource()` generates RESTful routes:
- `show` - GET `/api/tutor-education/{id}`
- `store` - POST `/api/tutor-education`
- `update` - PUT/PATCH `/api/tutor-education/{id}`
- `destroy` - DELETE `/api/tutor-education/{id}`

**Taxonomies**:
```php
Route::get('countries', [TaxonomiesController::class, 'getCountries']);
Route::get('languages', [TaxonomiesController::class, 'getLanguages']);
Route::get('states', [TaxonomiesController::class, 'getStates']);
```

### 4.3 Authenticated API Routes

**Middleware**: `auth:sanctum`

**Group Structure**:
```php
Route::middleware('auth:sanctum')->group(function () {
    // All authenticated API routes
});
```

**Account Management**:
```php
Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('update-password/{id}', 
    [AccountSettingController::class, 'updatePassword']);
Route::get('resend-email', [AuthController::class, 'resendEmail']);
Route::post('logout', [AuthController::class, 'logout']);
```

**Timezone Settings**:
```php
Route::post('timezone/{id}', 
    [AccountSettingController::class, 'updateTimezone']);
Route::get('timezone/{id}', 
    [AccountSettingController::class, 'getTimezone']);
```

**Messaging**:
```php
Route::post('send-message/{recipientId}', 
    [StudentController::class, 'sendMessage']);
```

**Favorites**:
```php
Route::apiResource('favourite-tutors', FavouriteTutorController::class)
    ->only('index', 'update');
```

**Profile**:
```php
Route::post('profile-settings/{id}', 
    [ProfileController::class, 'updateProfile']);
Route::get('profile-settings/{id}', 
    [ProfileController::class, 'getProfile']);
```

**Identity Verification**:
```php
Route::apiResource('identity-verification', IdentityController::class)
    ->only(['show', 'destroy', 'store']);
```

**Invoices**:
```php
Route::get('invoices', [InvoiceController::class, 'getInvoices']);
```

**Billing Details**:
```php
Route::apiResource('billing-detail', BillingDetailController::class)
    ->only(['show', 'update', 'store']);
```

### 4.4 Tutor-Specific API Routes

**Payouts**:
```php
Route::get('tutor-payouts/{id}', 
    [PayoutController::class, 'getPayoutHistory']);
Route::get('my-earning/{id}', [PayoutController::class, 'getEarning']);
Route::get('earning-detail', [PayoutController::class, 'getEarningDetail']);
Route::post('user-withdrawal', [PayoutController::class, 'userWithdrawal']);
Route::get('payout-status', [PayoutController::class, 'getPayoutStatus']);
Route::post('payout-status', [PayoutController::class, 'updateStatus']);
Route::post('payout-method', [PayoutController::class, 'addPayoutMethod']);
Route::delete('payout-method', [PayoutController::class, 'removePayoutMethod']);
```

### 4.5 Student-Specific API Routes

**Cart & Checkout**:
```php
Route::apiResource('booking-cart', CartController::class);
Route::post('checkout', [CheckoutController::class, 'addCheckoutDetails']);
```

**Bookings**:
```php
Route::get('upcoming-bookings', 
    [BookingController::class, 'getUpComingBooking']);
Route::post('complete-booking/{id}', 
    [BookingController::class, 'completeBooking']);
Route::post('book-free-slot', [BookingController::class, 'bookFreeSlot']);
```

**Reviews**:
```php
Route::post('review/{id}', [BookingController::class, 'addReview']);
```

### 4.6 Dispute API Routes

```php
Route::post('dispute/{id}', [BookingController::class, 'createDispute']);
Route::get('dispute-listing', [BookingController::class, 'getDisputes']);
Route::get('dispute-detail/{id}', [BookingController::class, 'getDispute']);
Route::get('dispute-discussion/{id}', 
    [BookingController::class, 'getDisputeDiscussion']);
Route::post('dispute-reply/{id}', 
    [BookingController::class, 'addDisputeReply']);
```

### 4.7 Notifications API Routes

```php
Route::get('notifications', [NotificationController::class, 'index']);
Route::post('notifications/{id}/read', 
    [NotificationController::class, 'markAsRead']);
Route::post('notifications/read-all', 
    [NotificationController::class, 'markAllAsRead']);
```

### 4.8 API Resource Routes Summary

**Generated Routes via `apiResource()`**:

| Resource | Method | URI | Action | Name |
|----------|--------|-----|--------|------|
| tutor-education | GET | /api/tutor-education/{id} | show | tutor-education.show |
| tutor-education | POST | /api/tutor-education | store | tutor-education.store |
| tutor-education | PUT/PATCH | /api/tutor-education/{id} | update | tutor-education.update |
| tutor-education | DELETE | /api/tutor-education/{id} | destroy | tutor-education.destroy |

**Similar for**:
- `tutor-experience`
- `tutor-certification`
- `favourite-tutors`
- `identity-verification`
- `billing-detail`
- `booking-cart`

**Total API Routes**: 50+ routes

---

## 5. Middleware Stack

### 5.1 Global Middleware

**Applied to All Routes**:
1. **TrustProxies** - Handle proxy headers
2. **HandleCors** - CORS configuration
3. **PreventRequestsDuringMaintenance** - Maintenance mode check
4. **ValidatePostSize** - Check POST size limits
5. **TrimStrings** - Trim whitespace from inputs
6. **ConvertEmptyStringsToNull** - Convert "" to null

### 5.2 Web Middleware Group

**File**: `app/Http/Kernel.php`

**Applied to `web.php` routes**:
```php
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Purpose**:
- Session management
- CSRF protection
- Cookie encryption
- Error sharing with views

### 5.3 API Middleware Group

**Applied to `api.php` routes**:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Purpose**:
- Sanctum SPA authentication
- Rate limiting (60 requests/minute)
- Route model binding

### 5.4 Custom Middleware

**Route-Specific Middleware**:

**1. locale** - `App\Http\Middleware\CheckLocale`
```php
Route::middleware(['locale'])->group(function () {
    // Sets application locale from session/cookie
});
```

**2. maintenance** - `App\Http\Middleware\CheckMaintenanceMode`
```php
Route::middleware(['maintenance'])->group(function () {
    // Checks if site is in maintenance mode
});
```

**3. auth** - Laravel's `Authenticate`
```php
Route::middleware(['auth'])->group(function () {
    // Ensures user is logged in
});
```

**4. verified** - Laravel's `EnsureEmailIsVerified`
```php
Route::middleware(['verified'])->group(function () {
    // Ensures email is verified
});
```

**5. onlineUser** - `App\Http\Middleware\UserOnline`
```php
Route::middleware(['onlineUser'])->group(function () {
    // Updates last_seen_at timestamp
});
```

**6. role:{roles}** - `App\Http\Middleware\RoleMiddleware`
```php
Route::middleware('role:tutor|student')->group(function () {
    // Checks if user has required role
});
```

**7. permit-of:{permission}** - `App\Http\Middleware\PermitOfMiddleware`
```php
Route::middleware('permit-of:can-manage-users')->group(function () {
    // Checks if user has specific permission
});
```

**8. auth:sanctum** - Laravel Sanctum's `EnsureFrontendRequestsAreStateful`
```php
Route::middleware('auth:sanctum')->group(function () {
    // API token authentication
});
```

### 5.5 Middleware Execution Order

**For Admin Route Example** (`/admin/users`):
```
1. Global Middleware (TrustProxies, HandleCors, etc.)
2. Web Middleware Group (Session, CSRF, Cookies)
3. locale - Set application language
4. maintenance - Check if site accessible
5. auth - Verify user logged in
6. verified - Check email verified
7. role:admin|sub_admin - Verify user is admin/sub_admin
8. permit-of:can-manage-users - Check specific permission
9. Route handler (Livewire component or controller)
```

**For API Route Example** (`/api/tutor-payouts/123`):
```
1. Global Middleware
2. API Middleware Group (Sanctum, Throttle, Bindings)
3. auth:sanctum - Verify API token
4. Route handler (API controller)
```

---

## 6. Route Groups & Prefixes

### 6.1 Nested Prefixes Example

**Tutor Resume Routes**:
```php
Route::prefix('tutor')->name('tutor.')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::prefix('resume')->name('resume.')->group(function () {
            Route::get('education', Resume::class)->name('education');
            // URL: /tutor/profile/resume/education
            // Name: tutor.profile.resume.education
        });
    });
});
```

**Benefit**: Clean URL structure, logical organization

### 6.2 Shared Components Across Roles

**Common Profile Routes** (used by both tutor and student):
```php
// Tutor version
Route::prefix('tutor')->name('tutor.')->group(function () {
    Route::get('profile/personal-details', PersonalDetails::class);
});

// Student version
Route::prefix('student')->name('student.')->group(function () {
    Route::get('profile/personal-details', PersonalDetails::class);
});
```

**Same Component**: `PersonalDetails::class`

**Different Behavior**: Component detects role via `getUserRole()` and adjusts

### 6.3 Admin Taxonomy Group

**Structure**:
```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::prefix('taxonomies')->name('taxonomy.')->group(function () {
        Route::get('languages', Languages::class)->name('languages');
        Route::get('subjects', Subjects::class)->name('subjects');
        Route::get('subject-groups', SubjectGroups::class)->name('subject-groups');
    });
});
```

**URLs**:
- `/admin/taxonomies/languages` → `admin.taxonomy.languages`
- `/admin/taxonomies/subjects` → `admin.taxonomy.subjects`
- `/admin/taxonomies/subject-groups` → `admin.taxonomy.subject-groups`

### 6.4 API Versioning Preparation

**Current**: No versioning (all routes under `/api`)

**Future-Proof Structure**:
```php
Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    // /api/v1/login
});
```

**Best Practice**: Version API routes for backward compatibility

---

## 7. Livewire Component Routing

### 7.1 Livewire Route Registration

**Pattern**: Livewire components registered as route handlers

**Example**:
```php
Route::get('dashboard', ManageAccount::class)->name('tutor.dashboard');
```

**Equivalent to**:
```php
Route::get('dashboard', function () {
    return view('livewire.pages.tutor.manage-account.manage-account');
})->name('tutor.dashboard');
```

**Benefit**: Livewire handles rendering, lifecycle hooks, reactivity

### 7.2 Livewire vs Controller Routes

**Livewire Routes** (used for pages):
```php
Route::get('bookings', UserBooking::class)->name('student.bookings');
// Livewire component with full lifecycle
```

**Controller Routes** (used for actions):
```php
Route::post('complete-booking/{id}', [SiteController::class, 'completeBooking']);
// Controller method, returns redirect/response
```

**Decision Factors**:
- **Livewire**: Interactive pages, forms, real-time updates
- **Controller**: Simple actions, API endpoints, redirects

### 7.3 Livewire Component Discovery

**Auto-Discovery**: Livewire components in `app/Livewire/` auto-registered

**Manual Registration** (not needed):
```php
Livewire::component('tutor.manage-account', ManageAccount::class);
```

**Route Definition**:
```php
use App\Livewire\Pages\Tutor\ManageAccount\ManageAccount;

Route::get('dashboard', ManageAccount::class);
```

**Component File**: `app/Livewire/Pages/Tutor/ManageAccount/ManageAccount.php`

**View File**: `resources/views/livewire/pages/tutor/manage-account/manage-account.blade.php`

### 7.4 Livewire Layout Attribute

**In Component**:
```php
use Livewire\Attributes\Layout;

class ManageAccount extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.tutor.manage-account.manage-account');
    }
}
```

**Result**: Component rendered inside `layouts.app` layout

**Alternative** (in view):
```blade
<div>
    @extends('layouts.app')
    @section('content')
        <!-- Component content -->
    @endsection
</div>
```

### 7.5 Livewire Route Parameters

**Route Definition**:
```php
Route::get('session-detail/{date}', SessionDetail::class)
    ->name('tutor.bookings.session-detail');
```

**Component Mount**:
```php
class SessionDetail extends Component
{
    public function mount($date)
    {
        $this->selectedDate = Carbon::parse($date);
    }
}
```

**Usage**: `route('tutor.bookings.session-detail', ['date' => '2026-01-15'])`

**URL**: `/tutor/bookings/session-detail/2026-01-15`

---

## 8. API Versioning & Authentication

### 8.1 API Authentication Methods

**1. Sanctum Token Authentication**:
```http
GET /api/tutor-payouts/123
Authorization: Bearer {token}
```

**Obtaining Token**:
```php
// POST /api/login
Route::post('login', [AuthController::class, 'login']);

// AuthController::login()
$user = User::where('email', $email)->first();
if (Hash::check($password, $user->password)) {
    $token = $user->createToken('mobile-app')->plainTextToken;
    return response()->json(['token' => $token]);
}
```

**Token Storage**:
- Mobile app stores token securely
- Sends in Authorization header for all requests

**2. Cookie-Based Authentication** (SPA):
```php
// CSRF cookie
GET /sanctum/csrf-cookie

// Login
POST /api/login

// Authenticated requests use session cookie
```

**Configuration**: `config/sanctum.php`

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000')),
```

### 8.2 API Rate Limiting

**Default**: 60 requests per minute per IP

**Configuration**: `app/Providers/RouteServiceProvider.php`

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Customize Per Route**:
```php
Route::middleware('throttle:100,1')->group(function () {
    // 100 requests per minute
});
```

### 8.3 API Response Format

**Success Response**:
```json
{
    "success": true,
    "data": {
        "tutor": { ... }
    },
    "message": "Tutor details retrieved successfully"
}
```

**Error Response**:
```json
{
    "success": false,
    "message": "Tutor not found",
    "errors": {
        "tutor_id": ["The tutor ID is invalid"]
    }
}
```

**Pagination**:
```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73
    }
}
```

### 8.4 API Versioning Strategy

**Current**: Single version (no prefix)

**Recommended**: Version prefix for production

**Implementation**:
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('login', [V1\AuthController::class, 'login']);
    // /api/v1/login
});

Route::prefix('v2')->group(function () {
    Route::post('login', [V2\AuthController::class, 'login']);
    // /api/v2/login
});
```

**Controller Organization**:
```
app/Http/Controllers/Api/
├── V1/
│   ├── AuthController.php
│   ├── TutorController.php
├── V2/
│   ├── AuthController.php
│   ├── TutorController.php
```

**Benefit**: Maintain backward compatibility when API changes

### 8.5 CORS Configuration

**File**: `config/cors.php`

**Default**:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'], // Production: specify domains
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

**Production**: Restrict to specific domains

```php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://app.yourdomain.com'
],
```

---

## Summary

**Route Organization**:
- **Web Routes** (119 lines): Public pages, auth, tutor/student dashboards
- **Admin Routes** (150+ lines): Admin panel with 60+ routes, permission-gated
- **API Routes** (112 lines): RESTful API with 50+ endpoints

**Middleware Layers**:
1. Global (all routes)
2. Group (web/api specific)
3. Custom (locale, maintenance, auth, verified, online)
4. Authorization (role, permission)

**Livewire Integration**:
- 78+ Livewire components
- Registered as route handlers
- Replace traditional controller views
- Enable reactive, SPA-like experience without JavaScript

**Key Patterns**:
- **Nested prefixes**: `/tutor/profile/resume/education`
- **Named routes**: `tutor.bookings.subjects` → `route('tutor.bookings.subjects')`
- **Role-based groups**: Separate tutor/student/admin sections
- **Permission middleware**: Granular admin access control
- **API resources**: RESTful CRUD endpoints

**Security**:
- CSRF protection on web routes
- Sanctum token auth on API routes
- Rate limiting (60/min default)
- Role and permission middleware
- Email verification required

**Best Practices Implemented**:
✅ Clean route organization
✅ Consistent naming conventions
✅ Middleware layering
✅ Livewire component routing
✅ API authentication
✅ Permission-based admin routes
✅ Nested route groups

**Potential Improvements**:
- Add API versioning (`/api/v1`)
- Implement more granular rate limiting
- Add route caching for production
- Create API documentation (OpenAPI/Swagger)
