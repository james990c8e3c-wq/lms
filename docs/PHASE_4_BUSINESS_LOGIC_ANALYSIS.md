# PHASE 4: BUSINESS LOGIC ANALYSIS

## Table of Contents
1. [Service Layer Architecture](#service-layer-architecture)
2. [Core Business Services](#core-business-services)
3. [Controller Layer](#controller-layer)
4. [Business Flow Diagrams](#business-flow-diagrams)
5. [Domain Logic Rules](#domain-logic-rules)

---

## 1. Service Layer Architecture

### 1.1 Service Pattern Overview

**Directory**: `app/Services/`

**Total Services**: 26 service classes

**Design Pattern**: Service Repository Pattern
- Controllers delegate business logic to services
- Services interact with models and repositories
- Services encapsulate complex operations
- Reusable across controllers, jobs, and commands

**Common Service Structure**:
```php
class ServiceName {
    protected $user; // Optional context user
    
    public function __construct($userId = null) {
        if ($userId) {
            $this->user = User::find($userId);
        }
    }
    
    // CRUD operations
    public function getEntity($id) { }
    public function createEntity(array $data) { }
    public function updateEntity($entity, array $data) { }
    public function deleteEntity($id) { }
    
    // Business logic methods
    public function complexOperation() { }
}
```

### 1.2 Service Layer Benefits

**Separation of Concerns**:
- Controllers handle HTTP requests/responses
- Services handle business logic
- Models handle data relationships
- Jobs handle asynchronous operations

**Reusability**:
- Same service methods used by controllers, Livewire components, jobs
- Example: `BookingService` used in `SiteController`, `CompleteBookingJob`, API controllers

**Testability**:
- Services can be unit tested independently
- Mock dependencies for isolated testing
- PHPUnit tests in `tests/Unit/`

**Maintainability**:
- Centralized business rules
- Changes to logic affect all callers
- Easier debugging with clear responsibility

### 1.3 All Services Inventory

| Service | File | Purpose | Key Methods |
|---------|------|---------|-------------|
| **BookingService** | BookingService.php | Session booking logic | `getAvailableSlots`, `getUserBookings`, `addUserSubjectGroupSessions` |
| **OrderService** | OrderService.php | Order/invoice management | `createOrder`, `getUserOrderDetail`, `getOrders` |
| **WalletService** | WalletService.php | Tutor earnings wallet | `addFunds`, `deductFunds`, `makePendingFundsAvailable` |
| **ProfileService** | ProfileService.php | User profile management | `setUserProfile`, `setUserAddress`, `storeUserLanguages` |
| **SubjectService** | SubjectService.php | Subject CRUD | `createSubject`, `updateSubject`, `getSubjects` |
| **UserService** | UserService.php | User management | `createUser`, `updateUser`, `setAccountSetting` |
| **DisputeService** | DisputeService.php | Dispute resolution | `createDispute`, `resolveDispute` |
| **PayoutService** | PayoutService.php | Tutor payout processing | `requestPayout`, `processPayout` |
| **CartService** | CartService.php | Shopping cart | `addToCart`, `removeFromCart`, `calculateTotal` |
| **NotificationService** | NotificationService.php | Notification dispatch | `sendNotification`, `markAsRead` |
| **RatingService** | RatingService.php | Reviews/ratings | `addRating`, `calculateAverageRating` |
| **IdentityService** | IdentityService.php | KYC verification | `submitVerification`, `approveIdentity` |
| **AddressService** | AddressService.php | Address management | `saveAddress`, `getCountries` |
| **BillingService** | BillingService.php | Billing details | `saveBillingDetail`, `getBillingDetail` |
| **GoogleCalender** | GoogleCalender.php | Google Calendar sync | `createEvent`, `deleteEvent`, `getAccessToken` |
| **ZoomService** | ZoomService.php | Zoom meeting integration | `createMeeting`, `getMeetingDetails` |
| **SiteService** | SiteService.php | Site settings | `getSetting`, `updateSetting` |
| **OptionBuilderService** | OptionBuilderService.php | Dynamic settings | `getOption`, `setOption` |
| **PageBuilderService** | PageBuilderService.php | Page builder | `savePage`, `getPageContent` |
| **TranslationService** | TranslationService.php | Translations | `translate`, `getTranslations` |
| **TaxonomyService** | TaxonomyService.php | Taxonomy management | `createCategory`, `getTaxonomies` |
| **EducationService** | EducationService.php | Tutor education | `addEducation`, `updateEducation` |
| **ExperienceService** | ExperienceService.php | Tutor experience | `addExperience`, `updateExperience` |
| **CertificateService** | CertificateService.php | Certificates | `generateCertificate`, `getCertificates` |
| **DbNotificationService** | DbNotificationService.php | DB notifications | `create`, `markAsRead` |
| **InsightsService** | InsightsService.php | Analytics/insights | `getEarnings`, `getBookingStats` |
| **RegisterService** | RegisterService.php | User registration | `register`, `verifyEmail` |

---

## 2. Core Business Services

### 2.1 BookingService

**File**: `app/Services/BookingService.php` (1208 lines)

**Purpose**: Manages all session booking operations

**Context User**: Optional `$user` (tutor or student)

#### Key Methods:

##### 1. getAvailableSlots($subjectGroupIds, $date)
**Purpose**: Get tutor's available/booked slots for calendar view

**Logic**:
- Query `user_subject_slots` by subject group IDs
- Filter by date if provided
- Count total slots and bookings per day
- Group by subject names
- Returns array: `['2024-01-15' => ['slots' => 5, 'booked' => 2, 'subjects' => ['Math' => 3]]]`

##### 2. getAvailableSubjectSlots($subjectGroupSubjects, $dateFormat, $timeFormat, $status)
**Purpose**: Get formatted slot options for dropdowns

**Logic**:
- Filter slots by status (active/inactive)
- Convert UTC times to user timezone via `parseToUserTz()`
- Format dates and times per user preferences
- Returns array of formatted slot data for select inputs

##### 3. getTutorAvailableSlots($userId, $userTimeZone, $date, $filter)
**Purpose**: Get bookable slots for student search (complex filtering)

**Parameters**:
- `$userId` - Tutor ID
- `$userTimeZone` - Student's timezone for conversion
- `$date` - Date range array ['start_date', 'end_date']
- `$filter` - Filters array (subject groups, session type, coupon)

**Logic**:
1. Query `user_subject_slots` for tutor
2. Eager load relationships: `subjectGroupSubjects`, `subject`, `group`
3. Filter by date range
4. Check available spaces: `spaces > total_booked`
5. Filter by session type: one-on-one (`spaces = 1`) or group (`spaces > 1`)
6. Apply subject group filters if provided
7. Check for coupon discount if `KuponDeal` module enabled
8. Convert all times to student's timezone
9. Calculate booking availability
10. Return slots with all metadata

**Timezone Handling**: Critical feature - all times stored in UTC, converted to user's timezone for display

##### 4. getUserSubjectSlots($date = null)
**Purpose**: Get tutor's own slots with bookings

**Logic**:
- Query slots for authenticated tutor (`$this->user`)
- Eager load: bookings count, student profiles (limit 5), subject details
- Group results by: Group Name → Subject Name → Slots array
- Returns nested array structure for calendar rendering

##### 5. getUserBookings($date, $showBy = 'daily', $filters = [])
**Purpose**: Get bookings for calendar view (tutor or student context)

**Parameters**:
- `$date` - Date range for query
- `$showBy` - Grouping: 'daily' (hourly slots) or 'weekly' (by date)
- `$filters` - Keyword search, subject groups, session type

**Logic**:
1. Query `slot_bookings` table
2. Eager load: tutor profile, slot details, subject info, students, dispute, rating
3. **Role-based filtering**:
   - Tutor: Show their tutoring sessions, active status only
   - Student: Show their bookings, all statuses (active, rescheduled, completed, disputed)
4. Apply keyword search across: student names, subject names, group names
5. Filter by session type (one-on-one vs group)
6. Filter by subject group IDs
7. Skip past sessions for tutors
8. Group by hour or date based on `$showBy`
9. Return associative array: `['10:00 am' => [booking1, booking2]]`

**Complex Query Example**:
```php
->withWhereHas('slot', function ($slot) use ($date, $filters) {
    $slot->withCount('bookings')
        ->with('students', fn($q) => $q->limit(5))
        ->withWhereHas('subjectGroupSubjects', function ($query) use ($filters) {
            if (!empty($filters['subject_group_ids'])) {
                $query->whereIn('id', $filters['subject_group_ids']);
            }
        });
    
    if (!empty($filters['type']) && $filters['type'] == 'one') {
        $slot->where('spaces', 1);
    }
})
```

##### 6. addUserSubjectGroupSessions($slots = array())
**Purpose**: Bulk create recurring slots for tutor

**Parameters** (array):
- `date_range` - "YYYY-MM-DD to YYYY-MM-DD"
- `start_time`, `end_time` - Daily time range
- `recurring_days` - Array of day names ['Monday', 'Wednesday']
- `duration` - Session length in minutes
- `break` - Break between sessions in minutes
- `spaces` - Max students per session
- `session_fee` - Price per session
- `subject_group_id` - Which subject this applies to

**Logic**:
1. Parse date range into start/end dates
2. Create `CarbonPeriod` between dates
3. Loop through each date
4. Skip dates not in `recurring_days`
5. Call `addTimeSlots()` for that date

##### 7. addTimeSlots($date, $slots, $dbSlots)
**Purpose**: Create individual time slots for a day (avoids overlaps)

**Logic**:
1. Calculate total slot duration: `end_time - start_time`
2. Calculate number of slots: `total_minutes / (duration + break)`
3. Loop through slot count:
   - Calculate start time (add duration + break from previous)
   - Calculate end time (start + duration)
   - **Check for overlaps** in database:
     ```php
     $slotExists = $dbSlots->where(function ($query) use ($startTime, $endTime) {
         $query->where('start_time', '<=', $startTime)->where('end_time', '>=', $startTime)
             ->orWhere('start_time', '<=', $endTime)->where('end_time', '>=', $endTime)
             ->orWhere('start_time', '>=', $startTime)->where('end_time', '<=', $endTime);
     })->exists();
     ```
   - If no overlap, add to `$newSlots` array
4. Bulk insert all new slots via `createMany()`

**Metadata Support**:
- Template ID (if using slot templates)
- Quiz/certificate assignment (if UpCertify module active)
- Subscription eligibility (if Subscriptions module active)

##### 8. getBookingDetail($id)
**Purpose**: Get single booking with full details

**Logic**:
- Query by booking ID
- Eager load: tutor, slot, subject, group, bookings count
- **Role-based filter**: Only show if user is tutor or student in booking
- Returns booking model or null

##### 9. addBookingReview($bookingId, $ratingData)
**Purpose**: Student adds rating after completed session

**Validation**:
- Booking must be completed status
- Student must own the booking
- No existing rating (prevent duplicates via `whereDoesntHave('rating')`)

**Logic**:
- Create rating record with: student_id, tutor_id, rating (1-5), comment
- Returns rating model or false

##### 10. removeReservedBooking($id)
**Purpose**: Remove temporary "reserved" booking when removed from cart

**Logic**:
- Find booking by ID with status = 4 (Reserved)
- Delete booking record
- Frees up slot space

---

### 2.2 OrderService

**File**: `app/Services/OrderService.php` (295 lines)

**Purpose**: Manage orders, invoices, and payment processing

#### Key Methods:

##### 1. createOrder($billingDetail)
**Purpose**: Create new order record

**Fields**:
- `user_id` - Customer
- `payment_method` - Gateway name
- `order_total` - Total amount
- `status` - 2 (Pending) initially
- `unique_payment_id` - UUID for payment tracking

**Returns**: Order model

##### 2. storeOrderItems($orderId, $items)
**Purpose**: Create order items (polymorphic relationship)

**Logic**:
- Loop through items array
- For each item, `updateOrCreate` with:
  - `orderable_id` - ID of booked entity
  - `orderable_type` - Model class (SlotBooking, Course, Bundle, Subscription)
  - `order_id` - Parent order
  - `item_price` - Individual price
  - `platform_fee` - Commission for platform

**Polymorphic Support**:
```php
OrderItem::morphTo('orderable')
// Can relate to:
// - App\Models\SlotBooking
// - Modules\Courses\Models\Course (if module enabled)
// - Modules\CourseBundles\Models\Bundle
// - Modules\Subscriptions\Models\Subscription
```

##### 3. getUserOrderDetail()
**Purpose**: Get user's completed orders with items

**Logic**:
- Query orders where `user_id = Auth::id()`
- Filter status = 1 (Complete)
- Eager load `items.orderable`
- Check enabled modules to constrain orderable types:
  ```php
  $orderableTypes = [SlotBooking::class];
  if (Module::isEnabled('Courses')) {
      $orderableTypes[] = Course::class;
  }
  // ... check other modules
  
  ->whereHasMorph('orderable', $orderableTypes)
  ```
- Returns collection of orders

##### 4. getOrders($status, $search, $sortby, $selectedSubject, $selectedSubGroup, $userId)
**Purpose**: Get filtered orders for student/tutor views

**Parameters**:
- `$status` - Order status filter
- `$search` - Search by subject name
- `$sortby` - Sort direction (asc/desc)
- `$selectedSubject`, `$selectedSubGroup` - Filter by subject taxonomy
- `$userId` - Optional user ID filter

**Logic**:
1. Start with Order query + `items.orderable` relationship
2. **Role-based filtering**:
   - **Student**: `where('user_id', Auth::id())` - Only their purchases
   - **Tutor**: Complex query to find orders containing their sessions:
     ```php
     ->whereHas('items', function ($q) use ($userId) {
         $q->whereHasMorph('orderable', [SlotBooking::class], function ($q2) use ($userId) {
             $q2->where('tutor_id', $userId);
         });
     })
     ```
3. Filter by status if provided
4. **JSON search on options field**:
   ```php
   ->when($search, function ($query) use ($search) {
       $query->whereHas('items.orderable', function ($q) use ($search) {
           $q->where('meta_data->subject_name', 'like', "%{$search}%");
       });
   })
   ```
5. Subject/group filtering via JSON query on `options` column
6. Sort by ID
7. Paginate results

##### 5. getOrdersList($status, $search, $sortby)
**Purpose**: Admin view of all orders with aggregations

**Logic**:
- Query all orders (no user filter)
- Eager load: `items.orderable`, `orderBy.profile`
- **Aggregations**:
  ```php
  ->withSum('items as admin_commission', 'platform_fee')
  ->withCount(['items as slot_booking_count' => function ($q) {
      $q->where('orderable_type', SlotBooking::class);
  }])
  // ... similar counts for Course, Bundle, Subscription if modules enabled
  ```
- Filter by status, search, sortby
- Returns orders with: total commission, counts by orderable type

##### 6. updateOrder($order, $newDetails)
**Purpose**: Update order (usually status change)

**Common Updates**:
- `status` - 1 (Complete), 2 (Pending), 3 (Failed), 4 (Refunded)
- `transaction_id` - Payment gateway transaction ID
- `payment_id` - Gateway-specific payment ID

**Usage**: Called after successful payment

##### 7. deleteOrderItem($orderableId, $orderableType)
**Purpose**: Remove item from cart/order

**Logic**:
1. Find OrderItem by `orderable_id`, `orderable_type`, and user's order
2. Delete OrderItem
3. Check if order has remaining items
4. If no items left, delete parent Order
5. Returns true if successful

---

### 2.3 WalletService

**File**: `app/Services/WalletService.php` (Complete 195 lines)

**Purpose**: Manage tutor earnings wallet (escrow system)

**Wallet States**:
1. **Pending Available** - Funds held during session (refund window)
2. **Available** - Funds ready for withdrawal
3. **Withdrawn** - Funds paid out to tutor

#### Wallet Tables:
- `user_wallets` - Wallet balance per user
  - `user_id` (unique)
  - `amount` - Available balance
- `user_wallet_details` - Transaction ledger
  - `user_wallet_id`
  - `order_id` - Related order
  - `amount` - Transaction amount
  - `type` - Transaction type (1=Add, 2=Deduct, 3=Withdrawn, 4=Pending Available, 5=Deduct Refund)

#### Key Methods:

##### 1. getUserWallet($userId)
**Purpose**: Get or create wallet for user

```php
return UserWallet::firstOrCreate(['user_id' => $userId]);
```

##### 2. addFunds($userId, $amount, $orderId = null)
**Purpose**: Add available funds to wallet (after session completion)

**Logic** (DB Transaction):
1. Get user's wallet
2. Increment wallet amount: `amount += $amount`
3. Create wallet detail record: type='add'
4. Return updated wallet

**Used When**: Session marked complete, admin approves payout

##### 3. pendingAvailableFunds($userId, $amount, $orderId = null)
**Purpose**: Hold funds in escrow (session booked, not yet completed)

**Logic**:
1. Get wallet
2. Create wallet detail: type='pending_available'
3. **Does NOT increment wallet amount** - funds held separately
4. Return wallet

**Used When**: Student completes payment, session booking created

##### 4. makePendingFundsAvailable($userId, $amount, $orderId = null)
**Purpose**: Release escrowed funds to available balance

**Logic** (DB Transaction):
1. Get wallet
2. Find `pending_available` detail for this order
3. Reduce pending amount: `pending_amount -= $amount`
4. Create `add` detail for released amount
5. Increment wallet balance: `wallet->amount += $amount`
6. Return wallet

**Used When**: 
- Student marks session complete (7+ days after session end)
- System auto-completes sessions
- Admin resolves dispute in tutor's favor

##### 5. refundFromPendingFunds($userId, $amount, $orderId = null)
**Purpose**: Refund from pending funds (before adding to wallet)

**Logic** (DB Transaction):
1. Get wallet
2. Find `pending_available` detail
3. Reduce pending amount
4. Create `deduct_refund` detail
5. **Does NOT change wallet balance** - funds never reached wallet
6. Return wallet

**Used When**:
- Session refunded before completion
- Dispute resolved in student's favor
- Tutor cancels session

##### 6. deductFunds($userId, $amount, $type = 'add', $orderId = null)
**Purpose**: Deduct funds from available balance (payouts, penalties)

**Logic** (DB Transaction):
1. Get wallet
2. **Check sufficient funds**: If `wallet->amount < $amount`, log error
3. Decrement balance: `wallet->amount -= $amount`
4. Create wallet detail with specified type
5. Return wallet

**Safety**: Logs error if insufficient funds, but doesn't throw exception

##### 7. getEarnedIncome($userId)
**Purpose**: Get total earnings (sum of all 'add' transactions)

```php
return UserWallet::where('user_id', $userId)
    ->withSum(['walletDetail as earned_amount' => function($query) {
        $query->where('type', 1); // Type 1 = Add
    }], 'amount')
    ->first()?->earned_amount ?? 0;
```

##### 8. getPendingAvailableFunds($userId)
**Purpose**: Get funds held in escrow

```php
return UserWallet::where('user_id', $userId)
    ->withSum(['walletDetail as pending_available_amount' => function($query) {
        $query->where('type', 4); // Type 4 = Pending Available
    }], 'amount')
    ->first()?->pending_available_amount ?? 0;
```

##### 9. getUserEarnings($userId, $selectedDate)
**Purpose**: Get daily earnings for charts (analytics)

**Logic**:
1. Query `user_wallet_details` for user's wallet
2. Filter type = 'add' (actual earnings)
3. Filter by month from `$selectedDate`
4. Group by day: `GROUP BY DAY(created_at)`
5. Sum amounts per day
6. Create array for all days in month (fill missing days with 0)
7. Return: `['earnings' => [1 => 150, 2 => 200, ...], 'days' => [1, 2, ..., 31]]`

**Used For**: Dashboard earnings charts, analytics

---

### 2.4 ProfileService

**File**: `app/Services/ProfileService.php` (Complete 87 lines)

**Purpose**: Manage user profiles and related data

**Constructor**: Accepts `$userId`, sets `$this->user`

#### Key Methods:

##### 1. getUserProfile()
**Returns**: Profile model for user or null

```php
return $this->user->profile()->first();
```

##### 2. setUserProfile(array $profileData)
**Purpose**: Create or update profile

**Fields** (common in `$profileData`):
- `first_name`, `last_name`
- `tagline` - Tutor headline
- `description` - About me
- `image` - Profile photo path
- `hourly_rate` - Default hourly rate
- `video` - Intro video URL

```php
$this->user->profile()->updateOrCreate(
    ['user_id' => $this->user->id], 
    $profileData
);
```

##### 3. storeUserLanguages($userLanguages)
**Purpose**: Sync user's spoken languages

**Logic**:
1. Detach all existing languages: `$this->user->languages()->detach()`
2. Loop through new language IDs
3. Attach each: `$this->user->languages()->attach(['language_id' => $langId])`

**Relationship**: Many-to-many via `user_languages` pivot table

##### 4. setUserAddress(array $address)
**Purpose**: Create or update address (polymorphic)

**Fields**:
- `country`, `state`, `city`
- `address_line_1`, `address_line_2`
- `postal_code`
- `latitude`, `longitude`

```php
$this->user->address()->updateOrCreate(
    ['addressable_id' => $this->user->id], 
    $address
);
```

##### 5. getUserAddress()
**Returns**: Polymorphic address model

```php
return $this->user->address;
```

##### 6. getSocialProfiles()
**Returns**: Array of social media links

```php
return $this->user->socialProfiles()->get()->toArray();
```

##### 7. setSocialProfiles(array $socialProfiles)
**Purpose**: Update social links

**Logic**:
1. Delete existing: `$this->user->socialProfiles()->delete()`
2. Create new: `$this->user->socialProfiles()->createMany($socialProfiles)`

**Fields per profile**:
- `platform` - facebook, twitter, linkedin, instagram
- `url` - Profile URL

##### 8. countryStates($country)
**Purpose**: Get states for country dropdown

```php
return CountryState::where('country_id', $country)->get(['id', 'name']);
```

---

## 3. Controller Layer

### 3.1 Controller Overview

**Directory**: `app/Http/Controllers/`

**Total Controllers**: 4 standard controllers (+ Admin controllers, Livewire components)

**Responsibility**: Handle HTTP requests, validate input, call services, return responses

### 3.2 SiteController

**File**: `app/Http/Controllers/SiteController.php` (437 lines)

**Purpose**: Main application controller for transactional operations

#### Key Methods:

##### 1. completeBooking($id, BookingService $bookingService)
**Purpose**: Student manually marks session complete

**Route**: `GET /student/complete-booking/{id}`

**Logic**:
1. Get booking by ID via service
2. Validate: booking exists, status is 'active', session has ended
3. Update booking status to 'completed'
4. **Release funds from escrow**:
   ```php
   (new WalletService())->makePendingFundsAvailable(
       $booking->tutor_id, 
       ($booking->session_fee - $booking?->orderItem?->platform_fee), 
       $booking?->orderItem?->order_id
   );
   ```
5. Dispatch `CompleteBookingJob` - Send notifications, trigger events
6. Redirect with success message

**Business Rule**: Platform commission deducted before funds released

##### 2. processPayment($gateway, Request $request)
**Purpose**: Process payment through selected gateway

**Route**: `GET /{gateway}/process/payment`

**Supported Gateways**:
- Stripe, Razorpay, PayFast, Iyzico, PayTM, PayStack, Mollie, FlutterWave

**Logic**:
1. Retrieve `payment_data` from session (set by checkout page)
2. If empty, redirect to checkout with error
3. Get gateway object via `getGatewayObject($gateway)` helper
4. Call `$gatewayObj->chargeCustomer($paymentData)`
5. Handle response:
   - Success: Gateway redirects to callback URL
   - Error: Redirect to checkout with error message
6. For API/UPI requests, return JSON response

**Session Data**:
```php
session('payment_data') = [
    'order_id' => 123,
    'amount' => 150.00,
    'currency' => 'USD',
    'customer_email' => 'user@example.com',
    'gateway' => 'stripe'
];
```

##### 3. paymentSuccess(Request $request, $webhook = false)
**Purpose**: Handle successful payment callback

**Route**: `GET /payment/success` (or webhook POST for PayFast)

**Logic**:
1. Get gateway object
2. Call `$gatewayObj->paymentResponse($request->all())`
3. Parse payment data: transaction_id, order_id, status
4. If status = 200 (OK):
   - Get order via `OrderService`
   - Update order: status='complete', transaction_id
   - Dispatch `CompletePurchaseJob` - Creates bookings, sends emails
   - Clear cart and payment session data
   - Auto-login guest users (for guest checkout)
   - Redirect to thank-you page with order ID
5. If status ≠ 200:
   - Redirect to checkout with error

##### 4. removeCart(Request $request)
**Purpose**: Remove item from cart (AJAX)

**Route**: `POST /remove-cart`

**Logic**:
1. Get `cartable_id` and `cartable_type` from request
2. If type is `SlotBooking`, call `BookingService::removeReservedBooking()` - Deletes temporary booking
3. Remove from cart facade: `Cart::remove($id, $type)`
4. Delete OrderItem via `OrderService`
5. Recalculate cart totals
6. Return JSON with updated cart data, totals, discount

**Response**:
```json
{
    "success": true,
    "cart_data": [...],
    "total": "$150.00",
    "subTotal": "$140.00",
    "discount": "$10.00",
    "toggle_cart": "open"
}
```

##### 5. logout(Logout $logout)
**Purpose**: Log user out

**Route**: `GET /logout`

**Logic**:
1. If IPManager module active, log logout event
2. Call Livewire `Logout` action - Clears session, invalidates remember token
3. Redirect to `/login`

##### 6. getGoogleToken(Request $request)
**Purpose**: OAuth callback for Google Calendar integration

**Route**: `GET /google/callback`

**Logic**:
1. Get OAuth code from query string
2. Exchange code for access token via `GoogleCalender::getAccessTokenInfo()`
3. Get user's primary calendar via `getUserPrimaryCalendar()`
4. Save to user's account settings: `google_access_token`, `google_calendar_info`
5. Set default sync interval to 30 minutes
6. Redirect to account settings with success message

**Use Case**: Tutors sync sessions to Google Calendar automatically

##### 7. preparePayment($id)
**Purpose**: Prepare order for payment (API endpoint)

**Route**: `GET /prepare-payment/{id}`

**Logic**:
1. Get order by `unique_payment_id`
2. Validate order exists and status is 'pending'
3. Return order data for payment gateway initialization

---

### 3.3 SearchController

**File**: `app/Http/Controllers/Frontend/SearchController.php`

**Purpose**: Tutor search and discovery

#### Key Methods:

##### 1. findTutors(Request $request)
**Purpose**: Search tutors with filters

**Route**: `GET /find-tutors`

**Filters**:
- `keyword` - Name, subject, description search
- `subject_group_ids[]` - Filter by subjects
- `rating` - Minimum rating (1-5)
- `hourly_rate_min`, `hourly_rate_max` - Price range
- `session_type` - one-on-one or group
- `languages[]` - Spoken languages
- `country`, `state`, `city` - Location
- `availability` - Date/time filters
- `sort` - newest, rating, price_low, price_high

**Logic**:
1. Start with User query where `default_role = 'tutor'`
2. Eager load: profile, subjects, languages, ratings
3. Apply keyword search across: first_name, last_name, tagline, description
4. Filter by subject groups via `whereHas('subjects')`
5. Filter by minimum rating using subquery
6. Filter by price range
7. Filter by session type (spaces = 1 or > 1)
8. Filter by languages
9. Filter by location via address relationship
10. Filter by availability (complex slot query)
11. Sort by selected option
12. Paginate results
13. Return view with tutors collection

**Complex Availability Filter**:
```php
->when($availability, function ($query) use ($availability) {
    $query->whereHas('subjects.slots', function ($slotQuery) use ($availability) {
        $slotQuery->where('start_time', '>=', $availability['start'])
                  ->where('end_time', '<=', $availability['end'])
                  ->whereRaw('spaces > total_booked');
    });
})
```

##### 2. tutorDetail($slug)
**Purpose**: Tutor profile page

**Route**: `GET /tutor/{slug}`

**Logic**:
1. Find tutor by slug
2. Eager load: profile, subjects with slots, education, experience, ratings, certifications
3. Calculate average rating
4. Get available slots for next 30 days
5. Get recent reviews (limit 10)
6. Return view with tutor data

##### 3. favouriteTutor(Request $request)
**Purpose**: Add/remove tutor from favorites (AJAX)

**Route**: `POST /favourite-tutor`

**Logic**:
1. Validate: user authenticated, tutor ID provided
2. Check if already favorited: `Auth::user()->favourites()->where('favourite_user_id', $tutorId)->exists()`
3. If exists, detach (remove favorite)
4. If not, attach (add favorite)
5. Return JSON success response

**Relationship**:
```php
// User model
public function favourites() {
    return $this->belongsToMany(User::class, 'favourite_users', 'user_id', 'favourite_user_id');
}
```

---

### 3.4 Admin/GeneralController

**File**: `app/Http/Controllers/Admin/GeneralController.php`

**Purpose**: Admin dashboard and management

**Middleware**: `role:admin|sub_admin`

#### Expected Methods (based on typical admin controllers):
- Dashboard statistics (total users, bookings, revenue)
- User management (view, edit, delete users)
- Order management (view, refund orders)
- Dispute resolution (mediate disputes)
- Platform settings (commission rates, currencies)
- Module management (enable/disable addons)
- Reports and analytics

---

## 4. Business Flow Diagrams

### 4.1 Session Booking Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│ SESSION BOOKING FLOW                                                 │
└─────────────────────────────────────────────────────────────────────┘

1. TUTOR CREATES SLOTS
   ┌──────────────────────────────────────────────────────────────┐
   │ Tutor → Manage Sessions → Create Subject                     │
   │ ↓                                                             │
   │ Tutor → Add Time Slots → Set recurring schedule             │
   │ ↓                                                             │
   │ BookingService::addUserSubjectGroupSessions()                │
   │   ├─ Parse date range and recurring days                    │
   │   ├─ Calculate slots per day (duration + breaks)            │
   │   ├─ Check for overlaps (prevent double-booking)            │
   │   └─ Create UserSubjectSlot records (UTC timezone)          │
   └──────────────────────────────────────────────────────────────┘

2. STUDENT SEARCHES TUTORS
   ┌──────────────────────────────────────────────────────────────┐
   │ Student → Find Tutors → Apply filters                        │
   │ ↓                                                             │
   │ SearchController::findTutors()                               │
   │   ├─ Filter by: subject, price, rating, location            │
   │   ├─ Filter by availability (date/time)                     │
   │   └─ Return tutor list with available slots                 │
   │ ↓                                                             │
   │ Student → Select Tutor → View Profile                       │
   │ ↓                                                             │
   │ BookingService::getTutorAvailableSlots()                    │
   │   ├─ Get slots for tutor                                    │
   │   ├─ Convert UTC → Student timezone                         │
   │   ├─ Check available spaces (spaces > total_booked)         │
   │   └─ Return bookable slots                                  │
   └──────────────────────────────────────────────────────────────┘

3. STUDENT BOOKS SESSION
   ┌──────────────────────────────────────────────────────────────┐
   │ Student → Select Slot → Add to Cart                          │
   │ ↓                                                             │
   │ Cart::add(SlotBooking, $slotId, $price)                     │
   │ ↓                                                             │
   │ Create SlotBooking (status = 4 "Reserved")                  │
   │   ├─ student_id, tutor_id                                   │
   │   ├─ user_subject_slot_id                                   │
   │   ├─ start_time, end_time (student timezone → UTC)          │
   │   ├─ session_fee                                            │
   │   └─ status = 4 (Reserved - temporary hold)                │
   └──────────────────────────────────────────────────────────────┘

4. CHECKOUT & PAYMENT
   ┌──────────────────────────────────────────────────────────────┐
   │ Student → Checkout → Enter billing details                   │
   │ ↓                                                             │
   │ OrderService::createOrder()                                  │
   │   ├─ user_id, payment_method, order_total                   │
   │   ├─ status = 2 (Pending)                                   │
   │   └─ unique_payment_id (UUID)                               │
   │ ↓                                                             │
   │ OrderService::storeOrderItems()                             │
   │   └─ Create OrderItem (orderable = SlotBooking)             │
   │ ↓                                                             │
   │ Store payment_data in session                                │
   │ ↓                                                             │
   │ Redirect to payment gateway                                  │
   │ ↓                                                             │
   │ SiteController::processPayment($gateway)                    │
   │   ├─ Get payment_data from session                          │
   │   └─ $gatewayObj->chargeCustomer()                          │
   └──────────────────────────────────────────────────────────────┘

5. PAYMENT SUCCESS
   ┌──────────────────────────────────────────────────────────────┐
   │ Gateway Callback → SiteController::paymentSuccess()          │
   │ ↓                                                             │
   │ Parse payment response (transaction_id, order_id)            │
   │ ↓                                                             │
   │ OrderService::updateOrder()                                  │
   │   ├─ status = 1 (Complete)                                  │
   │   └─ transaction_id = "txn_abc123"                          │
   │ ↓                                                             │
   │ Dispatch CompletePurchaseJob                                 │
   │   ├─ Update SlotBooking: status 4 → 1 (Active)             │
   │   ├─ Increment slot's total_booked counter                  │
   │   ├─ WalletService::pendingAvailableFunds()                 │
   │   │    └─ Hold tutor earnings in escrow                     │
   │   ├─ Send confirmation emails (student + tutor)             │
   │   ├─ Create Google Calendar events (if synced)              │
   │   └─ Create notification records                            │
   │ ↓                                                             │
   │ Cart::clear()                                                │
   │ ↓                                                             │
   │ Redirect to Thank You page                                   │
   └──────────────────────────────────────────────────────────────┘

6. SESSION OCCURS
   ┌──────────────────────────────────────────────────────────────┐
   │ Session date/time arrives                                     │
   │ ↓                                                             │
   │ Student + Tutor join meeting (Google Meet / Zoom)            │
   │ ↓                                                             │
   │ Session completes                                             │
   └──────────────────────────────────────────────────────────────┘

7. SESSION COMPLETION & FUNDS RELEASE
   ┌──────────────────────────────────────────────────────────────┐
   │ Option A: Student manually completes                         │
   │   Student → Complete Booking button                          │
   │   ↓                                                           │
   │   SiteController::completeBooking()                          │
   │   ↓                                                           │
   │   Validate: session ended, status = active                   │
   │                                                               │
   │ Option B: Auto-complete (7 days after session end)           │
   │   Scheduled command runs daily                               │
   │   ↓                                                           │
   │   Find bookings: end_time < now() - 7 days, status = active │
   │                                                               │
   │ BOTH OPTIONS:                                                 │
   │   ↓                                                           │
   │   BookingService::updateBooking(status = 5 "Completed")     │
   │   ↓                                                           │
   │   WalletService::makePendingFundsAvailable()                │
   │     ├─ Find pending_available detail for this order         │
   │     ├─ Calculate: session_fee - platform_fee                │
   │     ├─ Reduce pending amount                                │
   │     ├─ Create "add" detail                                  │
   │     └─ Increment wallet balance                             │
   │   ↓                                                           │
   │   Dispatch CompleteBookingJob                                │
   │     ├─ Send completion emails                               │
   │     ├─ Trigger "session completed" event                    │
   │     └─ Prompt student to leave review                       │
   └──────────────────────────────────────────────────────────────┘

8. STUDENT LEAVES REVIEW
   ┌──────────────────────────────────────────────────────────────┐
   │ Student → My Bookings → Rate Session                         │
   │ ↓                                                             │
   │ BookingService::addBookingReview()                          │
   │   ├─ Validate: booking completed, no existing rating        │
   │   ├─ Create Rating record                                   │
   │   │    ├─ student_id, tutor_id                              │
   │   │    ├─ rating (1-5 stars)                                │
   │   │    └─ comment                                           │
   │   └─ Update tutor's average rating                          │
   └──────────────────────────────────────────────────────────────┘
```

### 4.2 Dispute Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│ DISPUTE RESOLUTION FLOW                                              │
└─────────────────────────────────────────────────────────────────────┘

1. STUDENT RAISES DISPUTE
   ┌──────────────────────────────────────────────────────────────┐
   │ Student → Completed Bookings → Raise Dispute                 │
   │ ↓                                                             │
   │ DisputeService::createDispute($bookingId, $reason)          │
   │   ├─ Validate: booking completed, within dispute window     │
   │   ├─ Create Dispute record                                  │
   │   │    ├─ slot_booking_id                                   │
   │   │    ├─ raised_by (student_id)                            │
   │   │    ├─ reason (text)                                     │
   │   │    ├─ status = "open"                                   │
   │   │    └─ created_at                                        │
   │   ├─ Update SlotBooking status = 6 (Disputed)               │
   │   └─ Notify tutor + admin                                   │
   └──────────────────────────────────────────────────────────────┘

2. ADMIN REVIEWS DISPUTE
   ┌──────────────────────────────────────────────────────────────┐
   │ Admin → Disputes → View Details                              │
   │ ↓                                                             │
   │ View: Student reason, tutor response, session details        │
   │ ↓                                                             │
   │ Admin makes decision: Favor student OR Favor tutor           │
   └──────────────────────────────────────────────────────────────┘

3. RESOLUTION: FAVOR STUDENT (REFUND)
   ┌──────────────────────────────────────────────────────────────┐
   │ Admin → Resolve Dispute → Refund Student                     │
   │ ↓                                                             │
   │ DisputeService::resolveDispute($disputeId, 'student')       │
   │   ├─ Update Dispute: status = "resolved", resolved_in_favor = "student" │
   │   ├─ WalletService::refundFromPendingFunds()                │
   │   │    ├─ Find pending_available detail for order           │
   │   │    ├─ Reduce pending amount                             │
   │   │    └─ Create deduct_refund detail                       │
   │   ├─ Refund payment to student (via gateway)                │
   │   ├─ Update SlotBooking: status = 3 (Refunded)              │
   │   └─ Notify both parties                                    │
   └──────────────────────────────────────────────────────────────┘

4. RESOLUTION: FAVOR TUTOR (RELEASE FUNDS)
   ┌──────────────────────────────────────────────────────────────┐
   │ Admin → Resolve Dispute → Release to Tutor                   │
   │ ↓                                                             │
   │ DisputeService::resolveDispute($disputeId, 'tutor')         │
   │   ├─ Update Dispute: status = "resolved", resolved_in_favor = "tutor" │
   │   ├─ WalletService::makePendingFundsAvailable()             │
   │   │    └─ Same logic as normal completion                   │
   │   ├─ Keep SlotBooking: status = 5 (Completed)               │
   │   └─ Notify both parties                                    │
   └──────────────────────────────────────────────────────────────┘
```

### 4.3 Tutor Payout Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│ TUTOR PAYOUT FLOW                                                    │
└─────────────────────────────────────────────────────────────────────┘

1. TUTOR REQUESTS PAYOUT
   ┌──────────────────────────────────────────────────────────────┐
   │ Tutor → Payouts → Request Withdrawal                          │
   │ ↓                                                             │
   │ PayoutService::requestPayout($amount, $method)               │
   │   ├─ Validate: amount <= available wallet balance           │
   │   ├─ Validate: meets minimum payout threshold               │
   │   ├─ Create UserWithdrawal record                           │
   │   │    ├─ user_id, amount, method (bank, paypal)            │
   │   │    ├─ status = "pending"                                │
   │   │    └─ bank details / paypal email                       │
   │   ├─ WalletService::deductFunds(type='withdrawn')           │
   │   │    ├─ Deduct from wallet balance                        │
   │   │    └─ Create wallet detail: type=3 (Withdrawn)          │
   │   └─ Notify admin                                            │
   └──────────────────────────────────────────────────────────────┘

2. ADMIN PROCESSES PAYOUT
   ┌──────────────────────────────────────────────────────────────┐
   │ Admin → Payouts → Pending Requests                           │
   │ ↓                                                             │
   │ Admin verifies bank details / PayPal                         │
   │ ↓                                                             │
   │ Admin initiates bank transfer / PayPal payment               │
   │ ↓                                                             │
   │ PayoutService::processPayout($withdrawalId, $transactionId)  │
   │   ├─ Update UserWithdrawal: status = "completed"            │
   │   ├─ Store transaction_id from bank/PayPal                  │
   │   ├─ Set completed_at timestamp                             │
   │   └─ Notify tutor                                            │
   └──────────────────────────────────────────────────────────────┘

3. WALLET STATES THROUGHOUT LIFECYCLE
   ┌──────────────────────────────────────────────────────────────┐
   │ Booking Created:                                              │
   │   Wallet: $0 | Pending: $100 (session_fee - commission)     │
   │                                                               │
   │ Session Completed (7 days later):                            │
   │   Wallet: $100 | Pending: $0                                │
   │                                                               │
   │ Payout Requested:                                             │
   │   Wallet: $0 | Pending: $0 | Withdrawn: $100                │
   └──────────────────────────────────────────────────────────────┘
```

---

## 5. Domain Logic Rules

### 5.1 Booking Rules

1. **Slot Availability**
   - `spaces > total_booked` - Slot must have available spaces
   - No overlapping slots for same tutor
   - Slots stored in UTC, displayed in user's timezone
   - Past slots cannot be booked

2. **Booking States** (SlotBooking.status)
   - **1 - Active**: Confirmed, payment complete
   - **2 - Rescheduled**: Student rescheduled to different slot
   - **3 - Refunded**: Payment returned to student
   - **4 - Reserved**: Temporary hold (in cart, unpaid)
   - **5 - Completed**: Session finished, funds released
   - **6 - Disputed**: Under dispute resolution

3. **Booking Lifecycle**
   - Reserve (status=4) → Pay → Active (status=1) → Session Occurs → Complete (status=5) → Review
   - Disputes can be raised within X days after completion
   - Auto-completion after 7 days if student doesn't manually complete

### 5.2 Payment Rules

1. **Order Status** (Order.status)
   - **1 - Complete**: Payment successful
   - **2 - Pending**: Awaiting payment
   - **3 - Failed**: Payment declined
   - **4 - Refunded**: Payment returned

2. **Payment Flow**
   - Create Order (status=2) → Process Payment → Update Order (status=1) → Complete Purchase

3. **Polymorphic OrderItems**
   - Support multiple orderable types: SlotBooking, Course, Bundle, Subscription
   - Dynamically check enabled modules before querying

### 5.3 Wallet Rules

1. **Escrow System**
   - Funds held as "pending_available" immediately after payment
   - Released to available balance after completion/dispute resolution
   - Platform commission deducted before release: `session_fee - platform_fee`

2. **Wallet Detail Types**
   - **1 - Add**: Funds added to available balance
   - **2 - Deduct**: Funds removed (payouts, penalties)
   - **3 - Withdrawn**: Payout processed
   - **4 - Pending Available**: Escrow state
   - **5 - Deduct Refund**: Refund from escrow

3. **Payout Rules**
   - Minimum balance required (configurable)
   - Only available balance can be withdrawn (not pending)
   - Admin approval required
   - Payout methods: Bank transfer, PayPal, Stripe Connect

### 5.4 Authorization Rules

1. **Role-Based Data Access**
   - **Students**: See only their own bookings/orders
   - **Tutors**: See sessions where they are the tutor
   - **Admin**: See all data

2. **Role-Specific Actions**
   - Only tutors can create slots
   - Only students can book slots (tutors can book if also have student role)
   - Only admins can resolve disputes
   - Only session participants can view session details

3. **Ownership Validation**
   - Controllers validate: `booking->student_id == Auth::id()`
   - Services use role-aware queries: `->where('tutor_id', $this->user->id)`
   - Middleware blocks unauthorized route access

### 5.5 Timezone Rules

1. **Storage**: All timestamps in UTC
2. **Display**: Convert to user's timezone via `parseToUserTz()`
3. **Input**: Convert from user's timezone to UTC via `parseToUTC()`
4. **Helper**: `getUserTimezone()` retrieves from user profile or session

### 5.6 Commission Rules

1. **Platform Commission**:
   - Configurable percentage per session
   - Stored in `order_items.platform_fee`
   - Deducted from tutor earnings: `session_fee - platform_fee`

2. **Admin View**:
   - Total commission: `OrderItem::sum('platform_fee')`
   - Per-order commission: `order->items->sum('platform_fee')`

### 5.7 Rating Rules

1. **Eligibility**
   - Only students can rate
   - Only completed sessions (status=5)
   - One rating per booking (enforced via `whereDoesntHave('rating')`)

2. **Rating Impact**
   - Tutor average rating recalculated after each new rating
   - Displayed on tutor profile and search results
   - Used for tutor ranking/filtering

### 5.8 Dispute Rules

1. **Eligibility**
   - Only on completed sessions
   - Within dispute window (X days after completion)
   - Student or tutor can initiate

2. **Resolution**
   - Admin mediates
   - If favor student: Refund from escrow, booking status→refunded
   - If favor tutor: Release escrow to wallet, booking stays completed

### 5.9 Module Dependency Rules

1. **Dynamic Feature Checks**
   - Services check: `Module::has('Courses')`, `Module::isEnabled('Courses')`
   - Query constraints updated based on enabled modules
   - Polymorphic orderable types filtered by module status

2. **Examples**:
   - Courses: `if (Module::isEnabled('Courses')) { $orderableTypes[] = Course::class; }`
   - Subscriptions: Metadata added to slots if subscriptions active
   - KuponDeal: Coupon discounts applied if module enabled

---

## Summary

**Service Layer** (26 services):
- **BookingService**: 1208 lines, handles slot creation, booking retrieval, timezone conversion, complex filtering
- **OrderService**: 295 lines, manages orders, polymorphic order items, role-based queries
- **WalletService**: 195 lines, escrow system with pending funds, wallet ledger, payout tracking
- **ProfileService**: 87 lines, profile/address/language/social management

**Controller Layer** (4+ controllers):
- **SiteController**: 437 lines, handles payments, cart, bookings, OAuth callbacks
- **SearchController**: Tutor search with complex filters, profile views, favorites
- **Admin/GeneralController**: Admin dashboard, user/order/dispute management

**Business Flows**:
1. **Booking**: Tutor creates slots → Student searches → Books → Pays → Session occurs → Completes → Reviews
2. **Dispute**: Student raises → Admin reviews → Resolves (refund student OR release to tutor)
3. **Payout**: Tutor requests → Admin processes → Funds transferred

**Domain Rules**:
- Escrow system holds funds until completion/dispute
- Platform commission deducted from tutor earnings
- All times stored UTC, converted to user timezones
- Role-based data access and action authorization
- Module-aware polymorphic relationships
- Auto-completion after 7 days

**Key Patterns**:
- Service Repository Pattern for business logic
- Role-based query filtering throughout
- Polymorphic relationships for flexible order items
- Timezone-aware datetime handling
- Database transactions for wallet operations
- Job dispatching for async operations (emails, notifications)
