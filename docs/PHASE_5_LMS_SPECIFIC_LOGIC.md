# PHASE 5: LMS-SPECIFIC LOGIC (TUTORING MARKETPLACE)

## Table of Contents
1. [Tutoring Marketplace Overview](#tutoring-marketplace-overview)
2. [Subject & Taxonomy System](#subject--taxonomy-system)
3. [Tutor Features](#tutor-features)
4. [Student Features](#student-features)
5. [Session Management](#session-management)
6. [Rating & Review System](#rating--review-system)
7. [Dispute Resolution System](#dispute-resolution-system)
8. [Integration Features](#integration-features)
9. [Modular Addons](#modular-addons)

---

## 1. Tutoring Marketplace Overview

### 1.1 System Architecture

**Marketplace Type**: Two-sided marketplace (Tutors ↔ Students)

**Core Business Model**:
- **Tutors** create subject offerings with time slots and pricing
- **Students** search, book, and pay for tutoring sessions
- **Platform** takes commission on each transaction
- **Escrow system** holds funds until session completion

**Key Differentiators**:
1. **Time slot-based booking** (not course-based)
2. **Real-time session scheduling** with timezone support
3. **Flexible subject taxonomy** (groups → subjects → slots)
4. **One-on-one AND group sessions** support
5. **Multi-meeting platform** integration (Google Meet, Zoom, custom)
6. **Wallet escrow** with dispute resolution

### 1.2 User Roles in Marketplace

| Role | Primary Actions | Revenue Model |
|------|----------------|---------------|
| **Admin** | Platform management, commission settings, dispute resolution | Platform commission |
| **Sub Admin** | Limited admin functions (no financial access) | N/A |
| **Tutor** | Create subjects, manage slots, conduct sessions, receive payouts | Session fees - commission |
| **Student** | Search tutors, book sessions, attend, leave reviews | Pay session fees |

**Multi-Role Support**: Users can be both tutor AND student simultaneously with session-based role switching.

### 1.3 Transaction Flow

```
1. Tutor creates subject group (e.g., "Mathematics")
2. Tutor adds subjects under group (e.g., "Algebra", "Calculus")
3. Tutor creates time slots for each subject (recurring schedules supported)
4. Student searches tutors by subject/location/price/availability
5. Student selects slot → Added to cart (status: Reserved)
6. Student proceeds to checkout → Creates Order (status: Pending)
7. Payment processed → Order status: Complete
8. CompletePurchaseJob dispatched:
   - Booking status: Reserved → Active
   - Funds moved to tutor's pending wallet (escrow)
   - Google Calendar events created
   - Meeting links generated
   - Email notifications sent
9. Session occurs (Google Meet/Zoom/MeetFusion)
10. After session ends (7 days later or manual):
    - Student completes booking
    - Funds released: Pending → Available wallet
    - Platform commission deducted
    - Student can leave review
11. Tutor requests payout → Admin approves → Bank transfer/PayPal
```

---

## 2. Subject & Taxonomy System

### 2.1 Taxonomy Hierarchy

**3-Level Hierarchy**:
```
Subject Groups (Categories)
  ├─ Subjects (Individual topics)
      └─ User Subject Slots (Time-based offerings)
```

**Database Structure**:
- `subject_groups` - Top-level categories (Math, Science, Languages, etc.)
- `subjects` - Individual subjects (Algebra, Physics, Spanish, etc.)
- `user_subject_groups` - Tutor's offerings (which groups they teach)
- `user_subject_group_subjects` - Tutor's specific subjects (Algebra under Math)
- `user_subject_slots` - Actual bookable time slots

### 2.2 Subject Group Model

**File**: `app/Models/SubjectGroup.php`

**Fields**:
- `name` - Group name (e.g., "Mathematics")
- `description` - Optional description
- `status` - Active/inactive (1/0)

**Relationships**:
```php
public function subjects() {
    return $this->belongsToMany(Subject::class, 'subject_group_subjects');
}

public function userSubjectGroups() {
    return $this->hasMany(UserSubjectGroup::class);
}
```

**Admin Management**: 
- Route: `/admin/taxonomies/subject-groups`
- Livewire: `Admin\Taxonomy\SubjectGroups`
- Permission: `can-manage-subject-groups`

### 2.3 Subject Model

**File**: `app/Models/Subject.php`

**Fields**:
- `name` - Subject name (e.g., "Algebra")
- `description` - Optional description
- `status` - Active/inactive
- `deleted_at` - Soft delete support

**Features**:
- Global scope: `ActiveScope` (only fetch active subjects)
- Soft deletes enabled
- No timestamps

**Admin Management**:
- Route: `/admin/taxonomies/subjects`
- Livewire: `Admin\Taxonomy\Subjects`
- Permission: `can-manage-subjects`

### 2.4 User Subject Groups

**Purpose**: Link tutors to subject groups they teach

**Table**: `user_subject_groups`

**Fields**:
- `user_id` - Tutor ID
- `subject_group_id` - Subject group ID
- `status` - Active/inactive

**Use Case**: When tutor signs up, they select which groups they teach (Math, Science, etc.)

### 2.5 User Subject Group Subjects

**Purpose**: Link tutors to specific subjects with pricing

**Table**: `user_subject_group_subjects`

**Fields**:
- `user_subject_group_id` - Parent group link
- `subject_id` - Specific subject
- `hour_rate` - Hourly rate for this subject
- `image` - Subject image/icon
- `status` - Active/inactive

**Relationship Chain**:
```
User → UserSubjectGroup → UserSubjectGroupSubject → Subject
     → UserSubjectSlot (bookable times)
```

### 2.6 Languages Taxonomy

**Purpose**: Multi-language support for tutors and site

**Table**: `languages`

**Fields**:
- `name` - Language name
- `short_code` - ISO code (e.g., "en", "es", "fr")
- `flag` - Country flag icon
- `status` - Active/inactive

**User-Language Relationship**:
- Many-to-many via `user_languages` pivot table
- Tutors can speak multiple languages
- Students can search by language

**Admin Management**:
- Route: `/admin/taxonomies/languages`
- Livewire: `Admin\Taxonomy\Languages`

---

## 3. Tutor Features

### 3.1 Tutor Onboarding Flow

**Step 1: Registration**
- User registers with email/password or OAuth
- Default role: `student`

**Step 2: Profile Completion**
- Add `tagline` field → System converts to `tutor` role
- Upload profile photo, video intro
- Add description/bio (up to 1000 chars)
- Set default hourly rate
- Select spoken languages
- Add address (for location-based search)

**Step 3: Resume Building**
- **Education**: Add degrees, institutions, years
  - Route: `/tutor/profile/resume/education`
  - Livewire: `Common\ProfileSettings\Resume\Education`
- **Experience**: Add work history
  - Route: `/tutor/profile/resume/experience`
  - Livewire: `Common\ProfileSettings\Resume\Experience`
- **Certifications**: Upload certificates
  - Route: `/tutor/profile/resume/certificate`
  - Livewire: `Common\ProfileSettings\Resume\Certificate`

**Step 4: Identity Verification** (KYC)
- Upload ID documents (passport, driver's license)
- Admin reviews and approves
- Route: `/tutor/profile/identification`
- Livewire: `Common\ProfileSettings\IdentityVerification`
- Table: `user_identity_verifications`

**Step 5: Subject Setup**
- Select subject groups to teach
- Add specific subjects with pricing
- Route: `/tutor/bookings/manage-subjects`
- Livewire: `Tutor\ManageSessions\ManageSubjects`

**Step 6: Create Time Slots**
- Set availability schedule (recurring or one-time)
- Route: `/tutor/bookings/manage-sessions`
- Livewire: `Tutor\ManageSessions\MyCalendar`

### 3.2 Tutor Dashboard

**Route**: `/tutor/dashboard`

**Livewire**: `Tutor\ManageAccount\ManageAccount`

**Key Metrics Displayed**:
1. **Earned Amount**: Total lifetime earnings (`WalletService::getEarnedIncome()`)
2. **Pending Funds**: Escrow balance (`WalletService::getPendingAvailableFunds()`)
3. **Available Balance**: Withdrawable funds (`WalletService::getWalletAmount()`)
4. **Withdrawal Status**: Pending/completed payouts (`PayoutService::getPayoutStatus()`)
5. **Monthly Earnings Chart**: Daily breakdown (`WalletService::getUserEarnings()`)

**Actions**:
- Request payout (bank transfer, PayPal, Payoneer)
- View withdrawal history
- Update payout methods

**Code Example** (ManageAccount.php):
```php
public function mount() {
    $this->selectedDate = now(getUserTimezone());
    $this->data = $this->walletService->getUserEarnings(Auth::user()->id, $this->selectedDate);
    $this->earnedAmount = $this->walletService->getEarnedIncome(Auth::user()->id);
    $this->pendingFunds = $this->walletService->getPendingAvailableFunds(Auth::user()->id);
}
```

### 3.3 Manage Subjects

**Route**: `/tutor/bookings/manage-subjects`

**Livewire**: `Tutor\ManageSessions\ManageSubjects`

**Purpose**: Add/edit/delete tutor's subject offerings

**Process**:
1. **Select Subject Groups**: Choose from available groups (Math, Science, etc.)
2. **Add Subjects**: For each group, select specific subjects
3. **Set Pricing**: Define hourly rate per subject
4. **Upload Image**: Optional subject thumbnail
5. **Set Status**: Active/inactive

**Code Flow**:
```php
// Get tutor's current subject groups
$this->selected_groups = $this->subjectService->getUserSubjectGrouaps()?->toArray();

// Get all available subjects
$this->subjects = $this->subjectService->getSubjects()?->pluck('name','id');

// Add new subject
public function addNewSubject($groupId) {
    $this->form->group_id = $groupId;
    // Filter out already added subjects
    $availableSubjects = array_diff_key($this->subjects, $this->getUserGroupSubject($groupId));
    // Display modal with subject dropdown
}
```

**Validation**:
- Cannot add duplicate subjects under same group
- Hour rate must be numeric, positive
- Image must be JPG/PNG, max 3MB
- Description max 1000 characters

### 3.4 Manage Sessions (Calendar)

**Route**: `/tutor/bookings/manage-sessions`

**Livewire**: `Tutor\ManageSessions\MyCalendar`

**Purpose**: Create and manage time slots

**Features**:
1. **Calendar View**: Visual monthly/weekly calendar
2. **Create Slots**: Single or recurring schedules
3. **Edit/Delete Slots**: Modify existing availability
4. **View Bookings**: See booked vs available slots
5. **Session Details**: Click slot to view bookings

**Recurring Slot Creation**:
```php
BookingService::addUserSubjectGroupSessions([
    'subject_group_id' => 123,
    'date_range' => '2026-01-05 to 2026-01-31',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'recurring_days' => ['Monday', 'Wednesday', 'Friday'],
    'duration' => 60,        // 60-minute sessions
    'break' => 15,           // 15-minute break between
    'spaces' => 1,           // One-on-one (or >1 for group)
    'session_fee' => 50.00,
    'description' => 'Algebra tutoring'
])
```

**Overlap Prevention**: Algorithm checks for overlapping slots:
```php
$slotExists = $dbSlots->where(function ($query) use ($startTime, $endTime) {
    $query->where('start_time', '<=', $startTime)->where('end_time', '>=', $startTime)
        ->orWhere('start_time', '<=', $endTime)->where('end_time', '>=', $endTime)
        ->orWhere('start_time', '>=', $startTime)->where('end_time', '<=', $endTime);
})->exists();
```

### 3.5 Google Calendar Integration

**Route**: `/tutor/profile/account-settings`

**Livewire**: `Common\ProfileSettings\AccountSettings`

**Purpose**: Sync tutoring sessions with Google Calendar

**OAuth Flow**:
1. Tutor clicks "Connect Google Calendar"
2. Redirect to Google OAuth consent screen
3. User authorizes calendar access
4. Callback: `/google/callback`
5. `SiteController::getGoogleToken()` stores access token
6. Future bookings auto-create calendar events

**Service**: `App\Services\GoogleCalender`

**Methods**:
- `getAccessTokenInfo($code)` - Exchange OAuth code for token
- `getUserPrimaryCalendar($accessToken)` - Get primary calendar
- `createEvent($booking)` - Create calendar event for session
- `deleteEvent($eventId)` - Remove event when booking cancelled

**Stored Data** (in user profile):
```json
{
    "google_access_token": {
        "access_token": "ya29.xxx",
        "refresh_token": "1//xxx",
        "expires_in": 3600
    },
    "google_calendar_info": {
        "id": "primary",
        "summary": "John's Calendar",
        "minutes": 30  // Sync interval
    }
}
```

### 3.6 Zoom Integration

**Config**: `config/zoom.php`

**Service**: `App\Services\ZoomService`

**Purpose**: Create Zoom meetings for sessions

**Methods**:
- `createMeeting($booking)` - Generate Zoom meeting link
- `getMeetingDetails($meetingId)` - Retrieve meeting info
- `deleteMeeting($meetingId)` - Cancel meeting

**Credentials Required**:
- `ZOOM_CLIENT_KEY` - OAuth client ID
- `ZOOM_CLIENT_SECRET` - OAuth secret
- `ZOOM_ACCOUNT_ID` - Account ID

**Meeting Creation**:
```php
// Called in CompletePurchaseJob after booking confirmed
$this->bookingService->createMeetingLink($booking);
// Stores meeting URL in booking->meta_data['meeting_link']
```

### 3.7 Tutor Earnings & Payouts

**Payout Request Route**: `/tutor/payouts`

**Livewire**: `Payouts`

**Process**:
1. **Check Balance**: Tutor views available wallet balance
2. **Select Method**: Bank transfer, PayPal, Payoneer, Stripe Connect
3. **Enter Amount**: Must meet minimum threshold (configurable)
4. **Add Details**: Bank account/PayPal email
5. **Submit Request**: Creates `UserWithdrawal` record (status: pending)
6. **Wallet Deducted**: Funds moved from available to withdrawn
7. **Admin Reviews**: `/admin/withdraw-requests`
8. **Admin Pays**: Processes via bank/PayPal
9. **Mark Complete**: Updates status to completed, stores transaction ID

**Payout Methods Table**: `user_payout_methods`

**Fields**:
- `user_id`
- `type` - bank_transfer, paypal, payoneer, stripe
- `details` - JSON (account number, routing, email, etc.)
- `is_default` - Boolean
- `status` - Active/inactive

### 3.8 Tutor Insights

**Purpose**: Analytics for tutor performance

**Metrics**:
- Total earnings (lifetime, monthly, weekly)
- Number of sessions conducted
- Average rating
- Total students taught
- Booking trends (chart)
- Revenue by subject
- Top-rated sessions

**Implementation**: `App\Services\InsightsService`

---

## 4. Student Features

### 4.1 Student Dashboard

**Default Landing**: `/student/bookings`

**Livewire**: `Common\Bookings\UserBooking`

**Displays**:
1. **Upcoming Bookings**: Active sessions sorted by date
2. **Past Bookings**: Completed sessions
3. **Cancelled/Refunded**: Refunded sessions
4. **Disputed**: Sessions under dispute

**Filters**:
- Date range
- Subject group
- Subject
- Session type (one-on-one vs group)
- Status (active, completed, disputed)

### 4.2 Find Tutors

**Route**: `/find-tutors`

**Controller**: `Frontend\SearchController::findTutors()`

**Search Filters**:
1. **Keyword**: Name, subject, bio search
2. **Subject Group**: Filter by category
3. **Subject**: Filter by specific subject (multi-select)
4. **Languages**: Filter by spoken languages (multi-select)
5. **Session Type**: One-on-one or group
6. **Price Range**: Min/max hourly rate
7. **Location**: Country, state, city
8. **Availability**: Date/time availability
9. **Rating**: Minimum rating filter

**Sort Options**:
- Newest
- Highest rated
- Price: Low to high
- Price: High to low

**Query Implementation**:
```php
$tutors = User::where('default_role', 'tutor')
    ->with('profile', 'subjects', 'languages', 'ratings')
    ->when($keyword, function($q) use ($keyword) {
        $q->whereHas('profile', function($profile) use ($keyword) {
            $profile->where('first_name', 'like', "%{$keyword}%")
                   ->orWhere('last_name', 'like', "%{$keyword}%")
                   ->orWhere('tagline', 'like', "%{$keyword}%")
                   ->orWhere('description', 'like', "%{$keyword}%");
        });
    })
    ->when($subjectIds, function($q) use ($subjectIds) {
        $q->whereHas('subjects', function($subjects) use ($subjectIds) {
            $subjects->whereIn('subject_id', $subjectIds);
        });
    })
    ->when($minRating, function($q) use ($minRating) {
        $q->whereHas('ratings', function($ratings) {
            $ratings->selectRaw('AVG(rating) as avg_rating')
                   ->havingRaw('avg_rating >= ?', [$minRating]);
        });
    })
    ->paginate(12);
```

### 4.3 Tutor Profile Page

**Route**: `/tutor/{slug}`

**Controller**: `SearchController::tutorDetail($slug)`

**Sections**:
1. **Header**: Photo, name, tagline, rating, verified badge
2. **About**: Bio/description
3. **Subjects**: List of subjects with pricing
4. **Languages**: Spoken languages with flags
5. **Education**: Degrees and institutions
6. **Experience**: Work history
7. **Certifications**: Uploaded certificates
8. **Reviews**: Student reviews with ratings
9. **Available Slots**: Bookable time slots
10. **Courses** (if Courses module enabled): Featured courses

**Verification Badge**: Only verified tutors are publicly visible

**Code Check**:
```php
if ($tutor?->profile?->verified_at || $isAdmin) {
    // Show profile
} else {
    abort(404); // Hide unverified tutors
}
```

**Favorite Feature**:
- Students can add tutors to favorites
- Heart icon toggle (AJAX)
- Route: `POST /favourite-tutor`
- Table: `favourite_users` (many-to-many)

### 4.4 Booking Process

**Step 1: Select Slot**
- On tutor profile, student clicks "Book" on available slot
- AJAX call: `POST /book-session`
- Creates `SlotBooking` with status=4 (Reserved)
- Adds to `Cart` facade

**Step 2: Cart Review**
- Multiple slots can be added
- Cart shows: Tutor name, subject, date/time, price
- Can remove items: `POST /remove-cart`

**Step 3: Checkout**
- Route: `/checkout`
- Livewire: `Frontend\Checkout`
- Enter billing details (saved for future use)
- Select payment method
- Apply coupon code (if KuponDeal module enabled)

**Step 4: Payment**
- Redirect to gateway: `/{gateway}/process/payment`
- `SiteController::processPayment($gateway)`
- Gateway processes payment
- Callback: `/payment/success`

**Step 5: Confirmation**
- Thank you page: `/thank-you/{orderId}`
- Livewire: `Frontend\ThankYou`
- Display order details, booking confirmation
- Email sent to student and tutor

### 4.5 Session Attendance

**Meeting Links** (stored in `slot_bookings.meta_data`):
- Google Meet link (if Google Calendar synced)
- Zoom meeting link (if Zoom enabled)
- MeetFusion link (if MeetFusion module active)

**Access**:
- Student views booking details
- Clicks "Join Session" button
- Redirected to meeting platform

### 4.6 Complete Booking

**Manual Completion Route**: `/student/complete-booking/{id}`

**Controller**: `SiteController::completeBooking($id)`

**Validation**:
- Booking must be active status
- Session end time must be past

**Process**:
1. Update booking status: active → completed
2. Release escrow funds:
   ```php
   (new WalletService())->makePendingFundsAvailable(
       $tutorId, 
       ($sessionFee - $platformFee), 
       $orderId
   );
   ```
3. Dispatch `CompleteBookingJob`:
   - Send completion emails
   - Trigger events
   - Prompt student to leave review

**Auto-Completion**:
- Scheduled command runs daily
- Finds bookings: `end_time < now() - 7 days` AND `status = active`
- Automatically completes and releases funds

### 4.7 Reschedule Session

**Route**: `/student/reschedule-session/{id}`

**Livewire**: `Student\RescheduleSession`

**Process**:
1. Student requests reschedule
2. Booking status changed to: rescheduled
3. Student can:
   - **Select new slot** → Creates new booking, links to old
   - **Refund** → Refund to student wallet, deduct from tutor's pending

**Refund Logic**:
```php
public function refundSession() {
    $this->bookingService->updateBooking($booking, ['status' => 'refunded']);
    
    // Add funds back to student wallet
    (new WalletService())->addFunds(Auth::user()->id, $booking->session_fee);
    
    // Deduct from tutor's pending escrow
    (new WalletService())->refundFromPendingFunds(
        $booking->tutor_id, 
        ($booking->session_fee - $platformFee), 
        $orderId
    );
}
```

### 4.8 Favorite Tutors

**Route**: `/student/favourites`

**Livewire**: `Student\Favourite\Favourites`

**Purpose**: Saved tutor list for quick access

**Features**:
- View all favorited tutors
- Remove from favorites
- Quick link to tutor profile
- See tutor availability

**Database**: `favourite_users` pivot table

**Columns**:
- `user_id` - Student ID
- `favourite_user_id` - Tutor ID
- `created_at`

### 4.9 Student Invoices

**Route**: `/student/invoices`

**Livewire**: `Student\Invoices`

**Displays**:
- All completed orders
- Order number, date, amount, status
- List of purchased sessions/courses
- Download PDF invoice button

**Download PDF**: `/download-invoice/{id}`

**Controller**: `SiteController::downloadPDF($id)`

**Uses**: `Barryvdh\DomPDF\Facade\Pdf`

### 4.10 Billing Details

**Route**: `/student/billing-detail`

**Livewire**: `Student\BillingDetail\BillingDetail`

**Purpose**: Save billing info for faster checkout

**Fields**:
- First name, last name
- Email, phone
- Address line 1, address line 2
- Country, state, city
- Postal code

**Table**: `billing_details`

**Auto-fill**: Checkout page pre-fills from saved billing details

---

## 5. Session Management

### 5.1 Slot States

**UserSubjectSlot Statuses**:
- **Active**: Bookable
- **Inactive**: Hidden from search
- **Deleted**: Soft deleted

**SlotBooking Statuses** (BookingStatus cast):
1. **Active (1)**: Confirmed booking
2. **Rescheduled (2)**: Student requested reschedule
3. **Refunded (3)**: Payment returned
4. **Reserved (4)**: In cart, unpaid
5. **Completed (5)**: Session finished
6. **Disputed (6)**: Under dispute

### 5.2 Booking Lifecycle

```
┌─────────────────────────────────────────────────────────────┐
│ SLOT BOOKING LIFECYCLE                                       │
└─────────────────────────────────────────────────────────────┘

1. RESERVED (Cart)
   - Student adds slot to cart
   - Temporary hold on slot
   - No payment yet
   - Can be removed from cart
   - Auto-cleanup after X hours (configurable)

2. ACTIVE (Confirmed)
   - Payment successful
   - Order complete
   - Slot space decremented (total_booked++)
   - Funds in tutor's pending wallet
   - Meeting link generated
   - Calendar events created
   - Notifications sent

3. RESCHEDULED
   - Student requests date change
   - Original slot freed (total_booked--)
   - Student can select new slot or refund
   - Deadline: X days before session (configurable)

4. COMPLETED
   - Session occurred
   - Student manually completes OR auto-complete after 7 days
   - Funds released to tutor's available wallet
   - Student can leave review
   - Tutor can withdraw funds

5. REFUNDED
   - Full refund issued
   - Funds return to student wallet (or original payment method)
   - Tutor's pending wallet decremented
   - Slot space freed (total_booked--)

6. DISPUTED
   - Student raises dispute after completion
   - Funds frozen in escrow
   - Admin mediates
   - Resolution: Favor student (refund) OR favor tutor (release funds)
```

### 5.3 Session Completion Rules

**Manual Completion**:
- Available after session end time
- Student clicks "Complete Booking"
- Validates: booking active, session ended

**Auto-Completion**:
- Scheduled command: Daily at midnight
- Query: `end_time < now() - 7 days AND status = active`
- Loops through results, completes each
- Sends completion request notification X hours after session

**Completion Notification**:
```php
// Dispatched at session end time
dispatch(new SendNotificationJob('bookingCompletionRequest', $student, [
    'tutorName' => $tutor->full_name,
    'sessionDateTime' => parseToUserTz($booking->start_time),
    'completeBookingLink' => route('student.complete-booking', $booking->id),
    'days' => 7
]))->delay($sessionEndTime);
```

### 5.4 Timezone Handling

**Critical Feature**: All times stored UTC, displayed in user's timezone

**Helper Functions**:
```php
// Convert from user timezone to UTC (for storage)
parseToUTC($dateTime)

// Convert from UTC to user timezone (for display)
parseToUserTz($dateTime, $timezone = null)

// Get user's timezone
getUserTimezone() // From profile or session
```

**Example**:
```php
// Student in New York books slot for "2026-01-15 14:00"
// Tutor in London

// Storage (UTC):
$slot->start_time = parseToUTC('2026-01-15 14:00'); // Stored as 19:00 UTC

// Display to student (EST):
parseToUserTz($slot->start_time, 'America/New_York'); // Shows "14:00"

// Display to tutor (GMT):
parseToUserTz($slot->start_time, 'Europe/London'); // Shows "19:00"
```

### 5.5 Group Sessions

**Feature**: Tutors can offer group sessions (multiple students per slot)

**Implementation**:
- Slot `spaces` field: Number of students allowed
- `total_booked` field: Current bookings
- Availability: `spaces > total_booked`

**Query for Available Slots**:
```php
UserSubjectSlot::where('spaces', '>', DB::raw('total_booked'))
    ->get();
```

**Group Booking Logic**:
1. Multiple students book same slot
2. Each booking creates separate `SlotBooking` record
3. Slot's `total_booked` incremented per booking
4. When `total_booked == spaces`, slot becomes unavailable
5. All students join same meeting link

**Pricing**: Each student pays full session fee (no splitting)

### 5.6 Session Metadata

**SlotBooking.meta_data** (JSON column):
```json
{
    "meeting_link": "https://meet.google.com/xxx-yyyy-zzz",
    "zoom_meeting_id": "123456789",
    "google_calendar_event_id": "abc123def456",
    "subject_name": "Algebra",
    "subject_group_name": "Mathematics",
    "tutor_name": "John Doe",
    "student_name": "Jane Smith",
    "session_duration": 60,
    "notes": "Focus on quadratic equations"
}
```

**UserSubjectSlot.meta_data**:
```json
{
    "template_id": 5,
    "assign_quiz_certificate": [1, 2, 3],
    "allowed_for_subscriptions": 1
}
```

---

## 6. Rating & Review System

### 6.1 Rating Model

**File**: `app/Models/Rating.php`

**Table**: `ratings`

**Fields**:
- `student_id` - Who left the review
- `tutor_id` - Who received the review
- `rating` - Stars (1-5)
- `comment` - Text review
- `ratingable_id` - Polymorphic ID (SlotBooking or Course)
- `ratingable_type` - Polymorphic type
- `created_at`

**Relationships**:
```php
public function student(): HasOne {
    return $this->hasOne(User::class, 'id', 'student_id');
}

public function tutor(): HasOne {
    return $this->hasOne(User::class, 'id', 'tutor_id');
}

public function ratingable(): MorphTo {
    return $this->morphTo();
}
```

### 6.2 Leaving a Review

**Eligibility**:
- Only students can rate
- Only completed sessions (status=5)
- One rating per booking (`whereDoesntHave('rating')`)

**Process**:
1. Student views completed booking
2. Clicks "Leave Review" button
3. Modal/page with rating stars and comment box
4. Submit: `BookingService::addBookingReview($bookingId, $ratingData)`

**Service Method**:
```php
public function addBookingReview($bookingId, $ratingData) {
    $booking = SlotBooking::whereDoesntHave('rating')
        ->whereKey($bookingId)
        ->whereStudentId($this->user->id)
        ->whereStatus(BookingStatus::$statuses['completed'])
        ->first();
    
    if ($booking) {
        return $booking->rating()->create([
            'student_id' => $this->user->id,
            'tutor_id' => $booking->tutor_id,
            'rating' => $ratingData['rating'],
            'comment' => $ratingData['comment'],
        ]);
    }
    return false;
}
```

### 6.3 Rating Display

**Tutor Profile**:
- Average rating (1.0 - 5.0 stars)
- Total number of reviews
- Recent reviews list (limit 10)

**Search Results**:
- Star rating badge
- Filter by minimum rating

**Calculation**:
```php
$avgRating = Rating::where('tutor_id', $tutorId)->avg('rating');
$totalReviews = Rating::where('tutor_id', $tutorId)->count();
```

### 6.4 Admin Review Management

**Route**: `/admin/reviews`

**Livewire**: `Admin\Reviews\Reviews`

**Permission**: `can-manage-reviews`

**Features**:
- View all ratings
- Filter by tutor, student, rating value
- Delete inappropriate reviews
- Respond to reviews (if feature enabled)

---

## 7. Dispute Resolution System

### 7.1 Dispute Model

**File**: `app/Models/Dispute.php`

**Table**: `disputes`

**Fields**:
- `disputable_id` - Polymorphic ID (SlotBooking or Course)
- `disputable_type` - Polymorphic type
- `creator_by` - User who raised dispute
- `responsible_by` - Other party in dispute
- `favour_to` - Winner of dispute (after resolution)
- `resolved_by` - Admin who resolved
- `status` - open, in_progress, resolved, closed (DisputeStatus cast)
- `reason` - Dispute reason text
- `resolution` - Admin's resolution notes
- `created_at`, `resolved_at`

**Relationships**:
```php
public function disputable(): MorphTo {
    return $this->morphTo();
}

public function creatorBy(): BelongsTo {
    return $this->belongsTo(User::class, 'creator_by');
}

public function responsibleBy(): BelongsTo {
    return $this->belongsTo(User::class, 'responsible_by');
}

public function disputeConversations(): HasMany {
    return $this->hasMany(DisputeConversation::class);
}
```

### 7.2 Raising a Dispute

**Eligibility**:
- Session must be completed
- Within dispute window (configurable, typically 14 days)
- No existing dispute on this booking

**Student Process**:
1. Navigate to: `/student/disputes`
2. Livewire: `Common\Dispute\Dispute`
3. Select completed booking
4. Click "Raise Dispute"
5. Fill form: Reason, description
6. Submit

**Tutor Process**:
- Similar flow from `/tutor/disputes`

**Service Method**:
```php
DisputeService::createDispute($bookingId, $reason) {
    $booking = SlotBooking::find($bookingId);
    
    $dispute = Dispute::create([
        'disputable_id' => $booking->id,
        'disputable_type' => SlotBooking::class,
        'creator_by' => Auth::id(),
        'responsible_by' => ($booking->student_id == Auth::id()) 
            ? $booking->tutor_id 
            : $booking->student_id,
        'status' => 'open',
        'reason' => $reason
    ]);
    
    // Update booking status to disputed
    $booking->update(['status' => 6]);
    
    // Notify admin and other party
    return $dispute;
}
```

### 7.3 Dispute Conversation

**Table**: `dispute_conversations`

**Purpose**: Thread of messages between parties and admin

**Fields**:
- `dispute_id`
- `user_id` - Who sent message
- `message` - Text content
- `attachments` - File uploads (JSON array)
- `created_at`

**Features**:
- Real-time chat interface
- File upload support
- Admin can see all messages
- Parties can only see their own dispute

### 7.4 Admin Resolution

**Route**: `/admin/disputes`

**Livewire**: `Admin\Dispute\Dispute`

**Permission**: `can-manage-disputes-list`

**Admin View**: `/admin/manage-dispute/{id}`

**Livewire**: `Admin\Dispute\ManageDispute`

**Process**:
1. Admin reviews dispute details:
   - Original booking info
   - Session details
   - Messages from both parties
   - Student/tutor profiles
2. Admin makes decision:
   - **Favor Student** (Refund) button
   - **Favor Tutor** (Release Funds) button
3. Admin enters resolution notes
4. Submit

**Resolution Logic**:
```php
// Favor Student (Refund)
DisputeService::resolveDispute($disputeId, 'student') {
    $dispute = Dispute::find($disputeId);
    $booking = $dispute->disputable;
    
    // Update dispute
    $dispute->update([
        'status' => 'resolved',
        'favour_to' => $dispute->creator_by,
        'resolved_by' => Auth::id(),
        'resolved_at' => now()
    ]);
    
    // Refund from pending escrow
    WalletService::refundFromPendingFunds(
        $booking->tutor_id,
        $booking->session_fee - $booking->orderItem->platform_fee,
        $booking->orderItem->order_id
    );
    
    // Process refund to student
    // Update booking status to refunded
    $booking->update(['status' => 3]);
}

// Favor Tutor (Release Funds)
DisputeService::resolveDispute($disputeId, 'tutor') {
    // Same dispute update
    // Release funds from pending to available
    WalletService::makePendingFundsAvailable(
        $booking->tutor_id,
        $booking->session_fee - $booking->orderItem->platform_fee,
        $booking->orderItem->order_id
    );
    
    // Booking stays completed
}
```

### 7.5 Dispute Statistics

**Admin Dashboard Metrics**:
- Total open disputes
- Average resolution time
- Disputes resolved in favor of students vs tutors
- Most common dispute reasons

---

## 8. Integration Features

### 8.1 Google Calendar Sync

**Purpose**: Automatically add tutoring sessions to tutor's Google Calendar

**Setup**:
- Tutor connects Google account via OAuth 2.0
- Grants calendar read/write permissions
- Stores access token and refresh token

**Event Creation**:
```php
GoogleCalender::createEvent([
    'summary' => "Tutoring: {$subject} with {$studentName}",
    'description' => "Session details...",
    'start' => [
        'dateTime' => $booking->start_time->toRfc3339String(),
        'timeZone' => getUserTimezone()
    ],
    'end' => [
        'dateTime' => $booking->end_time->toRfc3339String(),
        'timeZone' => getUserTimezone()
    ],
    'attendees' => [
        ['email' => $student->email],
        ['email' => $tutor->email]
    ],
    'conferenceData' => [
        'createRequest' => ['requestId' => uniqid()]
    ]
]);
```

**Event Deletion**:
- When booking cancelled/refunded, event auto-deleted
- `SlotBooking` model boot method:
  ```php
  protected static function booted() {
      static::deleting(function ($booking) {
          if ($googleEventId = $booking->meta_data['google_calendar_event_id'] ?? null) {
              (new GoogleCalender())->deleteEvent($googleEventId);
          }
      });
  }
  ```

### 8.2 Zoom Integration

**Config**: `config/zoom.php`

**Environment Variables**:
- `ZOOM_CLIENT_KEY`
- `ZOOM_CLIENT_SECRET`
- `ZOOM_ACCOUNT_ID`

**Meeting Creation**:
```php
ZoomService::createMeeting([
    'topic' => "Tutoring: {$subject}",
    'type' => 2, // Scheduled meeting
    'start_time' => $booking->start_time->toIso8601String(),
    'duration' => $booking->duration,
    'timezone' => getUserTimezone(),
    'agenda' => "Session with {$studentName}",
    'settings' => [
        'host_video' => true,
        'participant_video' => true,
        'join_before_host' => false,
        'waiting_room' => true
    ]
]);
```

**Response**:
```json
{
    "id": 123456789,
    "join_url": "https://zoom.us/j/123456789?pwd=xxx",
    "start_url": "https://zoom.us/s/123456789?zak=xxx"
}
```

**Storage**: Join URL saved in `booking->meta_data['meeting_link']`

### 8.3 MeetFusion Module

**Module**: `Modules/MeetFusion`

**Purpose**: Custom video conferencing solution

**Features**:
- WebRTC-based video calls
- Screen sharing
- Chat during session
- Recording capabilities
- No external dependencies

**Integration**:
- Enabled via module manager
- Creates meeting room automatically for each booking
- Accessed via `/session/{bookingId}`

### 8.4 Payment Gateway Integrations

**Supported Gateways** (via LaraPayease package):
1. **Stripe** - Credit/debit cards
2. **Razorpay** - India-focused gateway
3. **PayFast** - South African gateway
4. **Iyzico** - Turkish gateway
5. **PayTM** - Indian wallet/UPI
6. **PayStack** - African markets
7. **Mollie** - European gateway
8. **FlutterWave** - African markets

**Gateway Selection**:
- Admin configures enabled gateways
- Route: `/admin/payment-methods`
- Students select at checkout

**Implementation**:
```php
$gatewayObj = getGatewayObject($gateway);
$response = $gatewayObj->chargeCustomer([
    'amount' => $orderTotal,
    'currency' => 'USD',
    'order_id' => $orderId,
    'customer_email' => $email
]);
```

### 8.5 Email Notifications

**Package**: Laravel's built-in Mail

**Templates**: Dynamic via `email-templates.php`

**Admin Configuration**: `/admin/email-settings`

**Key Email Events**:
- User registration
- Email verification
- Booking confirmation (student + tutor)
- Session reminder (1 hour before)
- Session completion request (7 days after)
- Booking completed
- Review request
- Payout requested/approved
- Dispute raised/resolved

**Example Template**:
```php
getEmailTemplates()['booking_confirmation'] = [
    'subject' => 'Session Booked: {tutorName}',
    'template' => 'emails.booking-confirmation',
    'variables' => [
        'userName', 'tutorName', 'subjectName', 
        'sessionDateTime', 'meetingLink'
    ]
];
```

### 8.6 Push Notifications (Laravel Reverb)

**Purpose**: Real-time notifications

**Config**: `config/reverb.php`

**Use Cases**:
- New booking notification (tutor)
- Payment received (tutor)
- Session starting soon (both)
- Message received (chat)
- Dispute update (both parties)

**Implementation**:
```php
broadcast(new BookingCreatedEvent($booking))->toOthers();
```

**Frontend Listening**:
```javascript
Echo.private(`user.${userId}`)
    .listen('BookingCreatedEvent', (e) => {
        showNotification('New booking received!');
    });
```

---

## 9. Modular Addons

### 9.1 Module Architecture

**Package**: `nwidart/laravel-modules`

**Directory**: `Modules/`

**Installed Modules**:
1. **LaraPayease** - Payment gateway abstraction
2. **MeetFusion** - Video conferencing
3. **Courses** (optional) - Full course platform
4. **Subscriptions** (optional) - Subscription plans
5. **CourseBundles** (optional) - Bundle courses
6. **KuponDeal** (optional) - Coupon system
7. **UpCertify** (optional) - Quiz & certificates
8. **IPManager** (optional) - IP tracking & blocking

**Module Status**: `modules_statuses.json`

**Admin Management**: `/admin/packages`

### 9.2 Courses Module

**Purpose**: Extend from session-based to course-based learning

**Features**:
- Video-based courses
- Course categories
- Enrollment system
- Progress tracking
- Course reviews
- Instructor earnings

**Integration with Core**:
- Polymorphic `OrderItem` supports `Course` orderable
- Shared wallet/payout system
- Unified student dashboard

**Query Example**:
```php
// Only query courses if module enabled
if (Module::isEnabled('Courses')) {
    $orderableTypes[] = \Modules\Courses\Models\Course::class;
}

Order::with('items')
    ->whereHasMorph('items.orderable', $orderableTypes)
    ->get();
```

### 9.3 Subscriptions Module

**Purpose**: Subscription-based access to sessions/courses

**Features**:
- Monthly/annual plans
- Credit-based system (X sessions per month)
- Auto-renewal
- Plan management
- Subscription analytics

**Integration**:
- Bookings can be paid via subscription credits
- `Order.subscription_id` links to plan
- Platform commission logic adjusted for subscriptions

**Logic in CompletePurchaseJob**:
```php
if (Module::isEnabled('subscriptions') && !empty($order->subscription_id)) {
    $subscription = SubscriptionService::getUserSubscription($order->user_id, $order->subscription_id);
    if (($subscription->remaining_credits['sessions'] ?? 0) > 0) {
        $subscription->remaining_credits['sessions']--;
        $tutorEarning = SubscriptionService::getSubscriptionTutorPayout($subscription);
        $platformFee = 0; // No commission on subscription bookings
    }
}
```

### 9.4 KuponDeal Module (Coupons)

**Purpose**: Discount codes for bookings

**Features**:
- Percentage or fixed amount discounts
- Expiry dates
- Usage limits (total uses, per user)
- Applicable to: sessions, courses, or both
- Admin creation/management

**Application**:
- Student enters code at checkout
- Validation checks: expiry, usage limits, applicability
- Discount applied to cart total

### 9.5 UpCertify Module (Quizzes & Certificates)

**Purpose**: Issue certificates after completing sessions/courses

**Features**:
- Create quizzes
- Assign to sessions or courses
- Auto-generate PDF certificates
- Certificate verification via unique code
- Student certificate collection

**Integration**:
- Slot metadata: `assign_quiz_certificate` array
- After session completion, unlock quiz
- Pass quiz → Generate certificate

### 9.6 IPManager Module

**Purpose**: Security and access control

**Features**:
- Log user IP addresses
- Track login locations
- Block suspicious IPs
- User agent tracking
- Activity logs

**Tables**:
- `user_logs` - Login/logout events
- `blocked_ips` - Blacklisted IPs

---

## Summary

**Lernen LMS** is a **tutoring marketplace** (not traditional LMS):
- **Two-sided platform**: Tutors create offerings, students book sessions
- **Time slot-based** booking system with timezone support
- **Flexible taxonomy**: Subject groups → Subjects → Slots
- **Escrow wallet** system with dispute resolution
- **Multi-platform** meeting integration (Google Meet, Zoom, MeetFusion)
- **Modular architecture** for extending with courses, subscriptions, etc.

**Key Differentiators**:
1. Real-time slot availability with overlap prevention
2. Comprehensive timezone handling (UTC storage, user display)
3. Group session support (multiple students per slot)
4. Automated fund release with 7-day review window
5. Google Calendar two-way sync
6. Multi-role users (tutor + student simultaneously)
7. Polymorphic order system supporting multiple purchasable types

**Business Flows**:
- Tutor creates subjects/slots → Student searches/books → Payment → Session occurs → Completion → Review → Payout
- Dispute flow: Raise → Admin mediates → Resolution (refund or release)
- Escrow lifecycle: Pending → Available → Withdrawn

**Technical Highlights**:
- 47 Eloquent models with complex relationships
- 26 service classes for business logic
- Livewire 3.5 for reactive frontend
- Queue-based async processing (emails, notifications, auto-completion)
- Module-aware polymorphic queries
- Comprehensive authorization (role + permission based)
