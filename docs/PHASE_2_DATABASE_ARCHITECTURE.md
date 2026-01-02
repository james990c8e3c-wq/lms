# Phase 2: Database Architecture Deep-Dive

## 1. Entity Relationship Diagram (Textual Representation)

### Core Entity Relationships

```
┌─────────────────┐         ┌─────────────────┐
│     users       │         │   profiles      │
├─────────────────┤         ├─────────────────┤
│ id (PK)         │────1:1──│ id (PK)         │
│ email (UNIQUE)  │         │ user_id (FK)    │
│ password        │         │ first_name      │
│ status          │         │ last_name       │
│ default_role    │         │ image           │
│ provider        │         │ verified_at     │
│ provider_id     │         │ gender          │
└─────────────────┘         └─────────────────┘
        │                            
        │ 1:M                        
        ▼                            
┌─────────────────┐         ┌─────────────────┐
│user_subject_grps│         │ subject_groups  │
├─────────────────┤         ├─────────────────┤
│ id (PK)         │────M:1──│ id (PK)         │
│ user_id (FK)    │         │ name            │
│ subject_grp_id  │         │ description     │
│ sort_order      │         │ status          │
└─────────────────┘         └─────────────────┘
        │                            
        │ 1:M                        
        ▼                            
┌──────────────────────┐    ┌─────────────────┐
│user_subject_grp_subj │    │   subjects      │
├──────────────────────┤    ├─────────────────┤
│ id (PK)              │────│ id (PK)         │
│ user_subject_grp_id  │    │ name            │
│ subject_id (FK)      │────│ description     │
│ session_fee          │    │ status          │
│ session_duration     │    │ deleted_at      │
└──────────────────────┘    └─────────────────┘
        │
        │ 1:M
        ▼
┌──────────────────────┐
│ user_subject_slots   │
├──────────────────────┤
│ id (PK)              │
│ user_subject_grp_    │
│   subject_id (FK)    │
│ day_id (FK)          │
│ start_time           │
│ end_time             │
│ status               │
│ meta_data (JSON)     │
└──────────────────────┘
        │
        │ 1:M
        ▼
┌──────────────────────┐    ┌─────────────────┐
│   slot_bookings      │    │   orders        │
├──────────────────────┤    ├─────────────────┤
│ id (PK)              │    │ id (PK)         │
│ student_id (FK)──────┼──M:1┤ user_id (FK)    │
│ tutor_id (FK)        │    │ payment_id      │
│ user_subject_slot_id │    │ payment_method  │
│ start_time           │    │ order_total     │
│ end_time             │    │ status          │
│ session_fee          │    │ created_at      │
│ status               │    └─────────────────┘
│ meta_data (JSON)     │            │
└──────────────────────┘            │ 1:M
        │                            ▼
        │ 1:1                ┌─────────────────┐
        ▼                    │  order_items    │
┌──────────────────────┐    ├─────────────────┤
│   booking_logs       │    │ id (PK)         │
├──────────────────────┤    │ order_id (FK)   │
│ id (PK)              │    │ orderable_type  │
│ booking_id (FK)      │    │ orderable_id    │
│ description          │    │ title           │
│ created_at           │    │ quantity        │
└──────────────────────┘    │ price           │
                            │ platform_fee    │
                            └─────────────────┘

┌─────────────────┐
│    ratings      │         Polymorphic: ratings.ratingable_type/id
├─────────────────┤         → Can reference: slot_bookings, courses, etc.
│ id (PK)         │
│ student_id (FK) │
│ tutor_id (FK)   │
│ ratingable_type │
│ ratingable_id   │
│ rating          │
│ feedback        │
└─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│  user_wallets   │    │user_wallet_dtls │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │──1:M│ id (PK)         │
│ user_id (FK)    │    │ user_wallet_id  │
│ balance         │    │ amount          │
│ total_earned    │    │ type            │
└─────────────────┘    │ description     │
        │               │ created_at      │
        │               └─────────────────┘
        │ 1:M
        ▼
┌─────────────────┐
│user_withdrawals │
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │
│ amount          │
│ payout_method_id│
│ status          │
│ processed_at    │
└─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│   disputes      │    │dispute_convs    │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │──1:M│ id (PK)         │
│ disputable_type │    │ dispute_id (FK) │
│ disputable_id   │    │ message         │
│ creator_by (FK) │    │ sender_id (FK)  │
│ responsible_by  │    │ created_at      │
│ resolved_by     │    └─────────────────┘
│ favour_to       │
│ status          │
│ reason          │
└─────────────────┘

┌─────────────────┐
│   addresses     │         Polymorphic: addressable_type/id
├─────────────────┤         → Can reference: users, ratings, etc.
│ id (PK)         │
│ addressable_type│
│ addressable_id  │
│ country_id (FK) │
│ state_id (FK)   │
│ address_line_1  │
│ city            │
│ postal_code     │
└─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│   countries     │    │ country_states  │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │──1:M│ id (PK)         │
│ name            │    │ country_id (FK) │
│ iso_code        │    │ name            │
│ flag            │    └─────────────────┘
└─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│   languages     │    │ user_languages  │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │──M:M│ user_id (FK)    │
│ name            │    │ language_id (FK)│
│ code            │    └─────────────────┘
│ status          │
└─────────────────┘

┌──────────────────────┐
│ user_identity_verifs │
├──────────────────────┤
│ id (PK)              │
│ user_id (FK) UNIQUE  │
│ document_type        │
│ document_front       │
│ document_back        │
│ status               │
│ verified_at          │
│ verified_by (FK)     │
└──────────────────────┘

┌─────────────────┐
│user_educations  │
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │
│ institute_name  │
│ degree_name     │
│ start_date      │
│ end_date        │
│ description     │
└─────────────────┘

┌─────────────────┐
│user_experiences │
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │
│ company_name    │
│ job_title       │
│ start_date      │
│ end_date        │
│ description     │
└─────────────────┘

┌─────────────────┐
│user_certificates│
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │
│ certificate_name│
│ authority       │
│ issue_date      │
│ certificate_file│
└─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│     blogs       │    │ blog_categories │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │──M:M│ id (PK)         │
│ title           │    │ name            │
│ slug (UNIQUE)   │    │ slug (UNIQUE)   │
│ content         │    │ status          │
│ featured_image  │    └─────────────────┘
│ status          │            │
│ author_id (FK)  │            │ M:M (via blog_category_links)
└─────────────────┘            │
        │                      │
        │ M:M (via blog_tag_links)
        ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│   blog_tags     │    │blog_cat_links   │
├─────────────────┤    ├─────────────────┤
│ id (PK)         │    │ blog_id (FK)    │
│ name            │    │ blog_cat_id(FK) │
│ slug (UNIQUE)   │    └─────────────────┘
└─────────────────┘

┌─────────────────┐
│favourite_users  │         Self-referencing M:M on users
├─────────────────┤
│ id (PK)         │
│ user_id (FK)    │         User who favorited
│ favourite_user_id│        User who is favorited
└─────────────────┘
```

---

## 2. Complete Table Definitions

### 2.1 User Management Tables

#### `users`
**Purpose**: Core user authentication and identification

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | User ID |
| email | VARCHAR(255) | NO | - | UNIQUE | User email (login) |
| password | VARCHAR(255) | NO | - | - | Hashed password |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| email_verified_at | TIMESTAMP | YES | NULL | - | Email verification time |
| remember_token | VARCHAR(100) | YES | NULL | - | Remember me token |
| provider | VARCHAR(255) | YES | NULL | - | OAuth provider (google, etc.) |
| provider_id | VARCHAR(255) | YES | NULL | - | OAuth provider user ID |
| default_role | VARCHAR(255) | YES | NULL | - | Default user role |
| created_at | TIMESTAMP | NO | - | - | Record creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update time |

**Indexes**:
- PRIMARY: `id`
- UNIQUE: `email`

**Relationships**:
- `hasOne`: Profile
- `hasMany`: UserSubjectGroup, SlotBooking (as tutor), SlotBooking (as student), Order
- `hasOne`: UserWallet, BillingDetail, TuitionSetting, UserIdentityVerification
- `belongsToMany`: Language (via user_languages), User (via favourite_users)

---

#### `profiles`
**Purpose**: Extended user profile information

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Profile ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Owner user |
| first_name | VARCHAR(255) | YES | NULL | - | First name |
| last_name | VARCHAR(255) | YES | NULL | - | Last name |
| image | VARCHAR(255) | YES | NULL | - | Profile picture path |
| tagline | TEXT | YES | NULL | - | Short bio/tagline |
| description | LONGTEXT | YES | NULL | - | Full bio/description |
| gender | VARCHAR(50) | YES | NULL | - | Gender (male/female/other) |
| date_of_birth | DATE | YES | NULL | - | Birth date |
| verified_at | TIMESTAMP | YES | NULL | - | Profile verification time |
| feature_expired_at | TIMESTAMP | YES | NULL | - | Featured listing expiry |
| recommend_tutor | TINYINT | YES | NULL | - | Recommendation status |
| deleted_at | TIMESTAMP | YES | NULL | - | Soft delete timestamp |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Indexes**:
- PRIMARY: `id`
- FOREIGN KEY: `user_id` → `users.id`

**Relationships**:
- `belongsTo`: User

**Accessors**:
- `full_name` → Concatenates first_name + last_name
- `short_name` → first_name + last_name initial
- `is_verified` → Boolean from verified_at
- `is_featured` → Check if feature_expired_at > now()
- `profile_image` → Returns full URL or default avatar

---

### 2.2 Subject & Tutoring Tables

#### `subject_groups`
**Purpose**: Categories/groups for subjects (e.g., "Mathematics", "Sciences")

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Group ID |
| name | VARCHAR(255) | NO | - | - | Group name |
| description | TEXT | YES | NULL | - | Group description |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `subjects`
**Purpose**: Individual subjects (e.g., "Algebra", "Physics")

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Subject ID |
| name | VARCHAR(255) | NO | - | - | Subject name |
| description | TEXT | YES | NULL | - | Subject description |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| deleted_at | TIMESTAMP | YES | NULL | - | Soft delete |

**Global Scope**: `ActiveScope` (only active subjects by default)

---

#### `user_subject_groups`
**Purpose**: Links tutors to subject groups they teach

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Link ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| subject_group_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (subject_groups.id) | Group ID |
| sort_order | INT | NO | 0 | - | Display order |

**Indexes**:
- PRIMARY: `id`
- UNIQUE: `(user_id, subject_group_id)`
- FOREIGN KEY: `user_id`, `subject_group_id`

---

#### `user_subject_group_subjects`
**Purpose**: Individual subjects within a tutor's subject group with pricing

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Subject link ID |
| user_subject_group_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY | Parent group link |
| subject_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (subjects.id) | Subject ID |
| session_fee | DECIMAL(8,2) | NO | - | - | Price per session |
| session_duration | INT | NO | - | - | Duration in minutes |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Relationships**:
- `belongsTo`: UserSubjectGroup, Subject

---

#### `user_subject_slots`
**Purpose**: Tutor's availability time slots for each subject

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Slot ID |
| user_subject_group_subject_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY | Subject link |
| day_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (days.id) | Day of week |
| start_time | TIME | NO | - | - | Slot start time |
| end_time | TIME | NO | - | - | Slot end time |
| status | TINYINT | NO | 1 | - | 1=Available, 0=Unavailable |
| meta_data | JSON | YES | NULL | - | Extra data (Google event ID, etc.) |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Relationships**:
- `belongsTo`: UserSubjectGroupSubject, Day
- `hasMany`: SlotBooking

---

### 2.3 Booking & Session Tables

#### `slot_bookings`
**Purpose**: Core booking/session records

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Booking ID |
| student_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Student user ID |
| tutor_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor user ID |
| user_subject_slot_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY | Slot being booked |
| start_time | TIMESTAMP | YES | NULL | - | Session start |
| end_time | TIMESTAMP | YES | NULL | - | Session end |
| session_fee | DOUBLE | NO | - | - | Amount charged |
| booked_at | TIMESTAMP | YES | NULL | - | Booking creation time |
| calendar_event_id | VARCHAR(255) | YES | NULL | - | Google Calendar event ID |
| status | TINYINT | NO | 1 | - | See status enum below |
| meta_data | JSON | YES | NULL | - | Extra booking data |

**Status Enum**:
1. Active
2. Rescheduled
3. Refunded
4. Reserved
5. Completed

**Relationships**:
- `belongsTo`: User (booker/student), User (bookee/tutor), UserSubjectSlot
- `hasMany`: BookingLog
- `hasOne`: Dispute
- `morphOne`: Rating, OrderItem

**Boot Logic**: On delete, deletes related booking_logs and Google Calendar events

---

#### `booking_logs`
**Purpose**: Audit trail for booking changes

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Log ID |
| booking_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (slot_bookings.id) | Related booking |
| description | TEXT | NO | - | - | Log message |
| created_at | TIMESTAMP | NO | - | - | Log timestamp |

---

### 2.4 Payment & Order Tables

#### `orders`
**Purpose**: Payment orders (parent of order items)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Order ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Buyer (student/tutor) |
| payment_id | VARCHAR(255) | YES | NULL | UNIQUE | Gateway transaction ID |
| payment_method | VARCHAR(100) | YES | NULL | - | stripe, razorpay, etc. |
| order_total | DECIMAL(10,2) | NO | - | - | Total amount |
| status | TINYINT | NO | 1 | - | Order status |
| created_at | TIMESTAMP | NO | - | - | Order creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Status Values** (via OrderStatusCast):
- 1: Complete
- 2: Pending
- 3: Failed
- 4: Refunded

**Relationships**:
- `belongsTo`: User (orderBy)
- `hasMany`: OrderItem

**Boot Logic**: On delete, cascades to order_items

---

#### `order_items`
**Purpose**: Line items in an order (polymorphic to bookings, courses, etc.)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Item ID |
| order_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (orders.id) CASCADE | Parent order |
| title | VARCHAR(255) | NO | - | - | Item description |
| quantity | INT | NO | - | - | Quantity purchased |
| options | JSON | YES | NULL | - | Item options |
| price | DECIMAL(8,2) | NO | - | - | Unit price |
| total | DECIMAL(8,2) | NO | - | - | Line total |
| platform_fee | DOUBLE | NO | 0 | - | Commission/fee |
| orderable_type | VARCHAR(255) | NO | - | - | Polymorphic type |
| orderable_id | BIGINT UNSIGNED | NO | - | - | Polymorphic ID |
| extra_fee | DOUBLE | YES | 0 | - | Additional charges |
| created_at | TIMESTAMP | NO | - | - | Item creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Indexes**:
- PRIMARY: `id`
- INDEX: `(orderable_type, orderable_id)`
- FOREIGN KEY: `order_id` → `orders.id` ON DELETE CASCADE

**Polymorphic Relationship**:
- `morphTo`: orderable (SlotBooking, Course, etc.)
- `belongsTo`: Order

---

### 2.5 Wallet & Payout Tables

#### `user_wallets`
**Purpose**: Tutor earnings wallet

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Wallet ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) UNIQUE | Owner |
| balance | DECIMAL(10,2) | NO | 0.00 | - | Available balance |
| total_earned | DECIMAL(10,2) | NO | 0.00 | - | Lifetime earnings |
| created_at | TIMESTAMP | NO | - | - | Wallet creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Relationships**:
- `belongsTo`: User
- `hasMany`: UserWalletDetail, UserWithdrawal

---

#### `user_wallet_details`
**Purpose**: Transaction log for wallet

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Transaction ID |
| user_wallet_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY | Wallet ID |
| amount | DECIMAL(10,2) | NO | - | - | Transaction amount |
| type | VARCHAR(50) | NO | - | - | credit/debit |
| description | TEXT | YES | NULL | - | Transaction note |
| created_at | TIMESTAMP | NO | - | - | Transaction time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_payout_methods`
**Purpose**: Tutor's payout preferences (bank, PayPal, etc.)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Method ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| method_type | VARCHAR(50) | NO | - | - | bank/paypal/stripe |
| method_details | JSON | NO | - | - | Account details |
| is_default | TINYINT | NO | 0 | - | Default method flag |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_withdrawals`
**Purpose**: Withdrawal requests from tutors

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Withdrawal ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| amount | DECIMAL(10,2) | NO | - | - | Withdrawal amount |
| user_payout_method_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY | Payout method |
| status | VARCHAR(50) | NO | pending | - | pending/paid/rejected |
| admin_note | TEXT | YES | NULL | - | Admin comments |
| processed_at | TIMESTAMP | YES | NULL | - | Processing time |
| created_at | TIMESTAMP | NO | - | - | Request time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

### 2.6 Review & Rating Tables

#### `ratings`
**Purpose**: Session/course reviews (polymorphic)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Rating ID |
| student_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Reviewer |
| tutor_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Reviewed tutor |
| ratingable_type | VARCHAR(255) | NO | - | - | Polymorphic type |
| ratingable_id | BIGINT UNSIGNED | NO | - | - | Polymorphic ID |
| rating | TINYINT | NO | - | - | 1-5 stars |
| feedback | TEXT | YES | NULL | - | Review text |
| created_at | TIMESTAMP | NO | - | - | Review time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Indexes**:
- PRIMARY: `id`
- INDEX: `(ratingable_type, ratingable_id)`

**Polymorphic Targets**:
- SlotBooking
- Course (if module enabled)

---

### 2.7 Dispute Tables

#### `disputes`
**Purpose**: Booking dispute records

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Dispute ID |
| disputable_type | VARCHAR(255) | NO | - | - | Polymorphic type |
| disputable_id | BIGINT UNSIGNED | NO | - | - | Polymorphic ID |
| creator_by | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Dispute initiator |
| responsible_by | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Counterparty |
| resolved_by | BIGINT UNSIGNED | YES | NULL | FOREIGN KEY (users.id) | Admin resolver |
| favour_to | BIGINT UNSIGNED | YES | NULL | FOREIGN KEY (users.id) | Winner |
| status | VARCHAR(50) | NO | pending | - | pending/resolved/rejected |
| reason | TEXT | NO | - | - | Dispute reason |
| created_at | TIMESTAMP | NO | - | - | Dispute start |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Relationships**:
- `morphTo`: disputable (SlotBooking)
- `belongsTo`: User (creatorBy, responsibleBy, resolvedBy, favourTo)
- `hasMany`: DisputeConversation

---

#### `dispute_conversations`
**Purpose**: Messages within a dispute thread

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Message ID |
| dispute_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (disputes.id) | Parent dispute |
| sender_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Message sender |
| message | TEXT | NO | - | - | Message content |
| created_at | TIMESTAMP | NO | - | - | Message time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

### 2.8 Verification & Trust Tables

#### `user_identity_verifications`
**Purpose**: ID document verification for tutors

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Verification ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) UNIQUE | User ID |
| document_type | VARCHAR(100) | NO | - | - | passport/license/etc. |
| document_front | VARCHAR(255) | NO | - | - | Front image path |
| document_back | VARCHAR(255) | YES | NULL | - | Back image path |
| status | VARCHAR(50) | NO | pending | - | pending/approved/rejected |
| verified_at | TIMESTAMP | YES | NULL | - | Verification time |
| verified_by | BIGINT UNSIGNED | YES | NULL | FOREIGN KEY (users.id) | Admin verifier |
| rejection_reason | TEXT | YES | NULL | - | If rejected |
| created_at | TIMESTAMP | NO | - | - | Submission time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_educations`
**Purpose**: Tutor education history

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Education ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| institute_name | VARCHAR(255) | NO | - | - | School/university |
| degree_name | VARCHAR(255) | NO | - | - | Degree obtained |
| field_of_study | VARCHAR(255) | YES | NULL | - | Major/field |
| start_date | DATE | NO | - | - | Start date |
| end_date | DATE | YES | NULL | - | End date (null=ongoing) |
| description | TEXT | YES | NULL | - | Additional details |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_experiences`
**Purpose**: Tutor work experience

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Experience ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| company_name | VARCHAR(255) | NO | - | - | Employer name |
| job_title | VARCHAR(255) | NO | - | - | Position |
| start_date | DATE | NO | - | - | Start date |
| end_date | DATE | YES | NULL | - | End date (null=current) |
| description | TEXT | YES | NULL | - | Job description |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_certificates`
**Purpose**: Tutor certifications

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Certificate ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| certificate_name | VARCHAR(255) | NO | - | - | Certificate title |
| authority | VARCHAR(255) | NO | - | - | Issuing authority |
| issue_date | DATE | NO | - | - | Issue date |
| expiry_date | DATE | YES | NULL | - | Expiry (if applicable) |
| certificate_file | VARCHAR(255) | YES | NULL | - | File path |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

### 2.9 Localization Tables

#### `languages`
**Purpose**: Supported platform languages

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Language ID |
| name | VARCHAR(100) | NO | - | - | Language name |
| code | VARCHAR(10) | NO | - | UNIQUE | ISO code (en, fr, es) |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `user_languages`
**Purpose**: Languages spoken by tutors (M:M pivot)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Link ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Tutor ID |
| language_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (languages.id) | Language ID |

**Indexes**:
- UNIQUE: `(user_id, language_id)`

---

#### `countries`
**Purpose**: Countries database

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Country ID |
| name | VARCHAR(100) | NO | - | - | Country name |
| iso_code | VARCHAR(3) | NO | - | UNIQUE | ISO 3166 code |
| flag | VARCHAR(255) | YES | NULL | - | Flag image path |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `country_states`
**Purpose**: States/provinces within countries

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | State ID |
| country_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (countries.id) | Country ID |
| name | VARCHAR(100) | NO | - | - | State name |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `addresses`
**Purpose**: Address records (polymorphic)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Address ID |
| addressable_type | VARCHAR(255) | NO | - | - | Polymorphic type |
| addressable_id | BIGINT UNSIGNED | NO | - | - | Polymorphic ID |
| country_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (countries.id) | Country |
| state_id | BIGINT UNSIGNED | YES | NULL | FOREIGN KEY (country_states.id) | State |
| address_line_1 | VARCHAR(255) | NO | - | - | Address line 1 |
| address_line_2 | VARCHAR(255) | YES | NULL | - | Address line 2 |
| city | VARCHAR(100) | NO | - | - | City |
| postal_code | VARCHAR(20) | YES | NULL | - | ZIP/postal code |
| created_at | TIMESTAMP | NO | - | - | Record creation |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Polymorphic Targets**:
- User
- Rating
- (extensible to other models)

---

### 2.10 Content Management Tables

#### `blogs`
**Purpose**: Blog posts

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Blog ID |
| title | VARCHAR(255) | NO | - | - | Post title |
| slug | VARCHAR(255) | NO | - | UNIQUE | URL slug |
| content | LONGTEXT | NO | - | - | Post content (HTML) |
| excerpt | TEXT | YES | NULL | - | Short description |
| featured_image | VARCHAR(255) | YES | NULL | - | Featured image path |
| author_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Author |
| status | VARCHAR(50) | NO | draft | - | draft/published/archived |
| published_at | TIMESTAMP | YES | NULL | - | Publish time |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Relationships**:
- `belongsTo`: User (author)
- `belongsToMany`: BlogCategory, BlogTag

---

#### `blog_categories`
**Purpose**: Blog categories

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Category ID |
| name | VARCHAR(100) | NO | - | - | Category name |
| slug | VARCHAR(100) | NO | - | UNIQUE | URL slug |
| description | TEXT | YES | NULL | - | Category description |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `blog_category_links`
**Purpose**: M:M pivot for blogs and categories

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Link ID |
| blog_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (blogs.id) | Blog ID |
| blog_category_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (blog_categories.id) | Category ID |

---

#### `blog_tags`
**Purpose**: Blog tags

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Tag ID |
| name | VARCHAR(100) | NO | - | - | Tag name |
| slug | VARCHAR(100) | NO | - | UNIQUE | URL slug |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `blog_tag_links`
**Purpose**: M:M pivot for blogs and tags

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Link ID |
| blog_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (blogs.id) | Blog ID |
| blog_tag_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (blog_tags.id) | Tag ID |

---

### 2.11 Menu System Tables

#### `menus`
**Purpose**: Menu definitions

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Menu ID |
| name | VARCHAR(100) | NO | - | - | Menu identifier |
| description | TEXT | YES | NULL | - | Menu description |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `menu_items`
**Purpose**: Items within menus (hierarchical)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Item ID |
| menu_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (menus.id) | Parent menu |
| parent_id | BIGINT UNSIGNED | YES | NULL | FOREIGN KEY (menu_items.id) | Parent item (for submenus) |
| title | VARCHAR(100) | NO | - | - | Display text |
| url | VARCHAR(255) | YES | NULL | - | Link URL |
| target | VARCHAR(20) | YES | _self | - | _self/_blank |
| icon | VARCHAR(100) | YES | NULL | - | Icon class |
| order | INT | NO | 0 | - | Display order |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Hidden |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

**Hierarchical**: Uses `parent_id` for nested menu items (via `staudenmeir/laravel-adjacency-list`)

---

### 2.12 System Configuration Tables

#### `email_templates`
**Purpose**: Customizable email templates

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Template ID |
| slug | VARCHAR(100) | NO | - | UNIQUE | Template identifier |
| subject | VARCHAR(255) | NO | - | - | Email subject |
| body | LONGTEXT | NO | - | - | Email body (HTML) |
| variables | JSON | YES | NULL | - | Available variables |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `notification_templates`
**Purpose**: In-app notification templates

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Template ID |
| slug | VARCHAR(100) | NO | - | UNIQUE | Template identifier |
| title | VARCHAR(255) | NO | - | - | Notification title |
| body | TEXT | NO | - | - | Notification content |
| variables | JSON | YES | NULL | - | Available variables |
| status | TINYINT | NO | 1 | - | 1=Active, 0=Inactive |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

### 2.13 Additional Support Tables

#### `days`
**Purpose**: Days of the week reference

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Day ID (1-7) |
| name | VARCHAR(20) | NO | - | - | Monday, Tuesday, etc. |

---

#### `favourite_users`
**Purpose**: User favorites (M:M self-referencing on users)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Favorite ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | User who favorited |
| favourite_user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | User who is favorited |
| created_at | TIMESTAMP | NO | - | - | Favorite time |

**Indexes**:
- UNIQUE: `(user_id, favourite_user_id)`

---

#### `billing_details`
**Purpose**: Student billing information

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Billing ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | Student ID |
| billing_name | VARCHAR(255) | NO | - | - | Name on card |
| billing_email | VARCHAR(255) | YES | NULL | - | Billing email |
| billing_address | TEXT | YES | NULL | - | Billing address |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `account_settings`
**Purpose**: User account preferences

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Setting ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) | User ID |
| setting_key | VARCHAR(100) | NO | - | - | Setting identifier |
| setting_value | TEXT | YES | NULL | - | Setting value |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `tuition_settings`
**Purpose**: Tutor-specific settings

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Setting ID |
| user_id | BIGINT UNSIGNED | NO | - | FOREIGN KEY (users.id) UNIQUE | Tutor ID |
| min_booking_hours | INT | YES | NULL | - | Minimum booking notice |
| max_students_per_slot | INT | YES | NULL | - | Group session limit |
| cancellation_policy | TEXT | YES | NULL | - | Cancellation policy text |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `addons`
**Purpose**: Installed addon/module tracking

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Addon ID |
| name | VARCHAR(100) | NO | - | - | Addon name |
| slug | VARCHAR(100) | NO | - | UNIQUE | Addon identifier |
| version | VARCHAR(20) | YES | NULL | - | Installed version |
| status | TINYINT | NO | 1 | - | 1=Enabled, 0=Disabled |
| installed_at | TIMESTAMP | YES | NULL | - | Installation time |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

### 2.14 Laravel System Tables

#### `personal_access_tokens` (Sanctum)
**Purpose**: API tokens for authentication

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO | PRIMARY KEY | Token ID |
| tokenable_type | VARCHAR(255) | NO | - | - | Model type (User) |
| tokenable_id | BIGINT UNSIGNED | NO | - | - | Model ID |
| name | VARCHAR(255) | NO | - | - | Token name |
| token | VARCHAR(64) | NO | - | UNIQUE | Token hash |
| abilities | TEXT | YES | NULL | - | Token scopes |
| last_used_at | TIMESTAMP | YES | NULL | - | Last use time |
| expires_at | TIMESTAMP | YES | NULL | - | Expiry time |
| created_at | TIMESTAMP | NO | - | - | Creation time |
| updated_at | TIMESTAMP | NO | - | - | Last update |

---

#### `sessions`
**Purpose**: Session storage

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| id | VARCHAR(255) | NO | - | PRIMARY KEY | Session ID |
| user_id | BIGINT UNSIGNED | YES | NULL | INDEX | Authenticated user |
| ip_address | VARCHAR(45) | YES | NULL | - | Client IP |
| user_agent | TEXT | YES | NULL | - | Client user agent |
| payload | LONGTEXT | NO | - | - | Session data |
| last_activity | INT | NO | - | INDEX | Timestamp |

---

#### `cache` / `cache_locks`
**Purpose**: Cache storage

---

#### `jobs` / `job_batches` / `failed_jobs`
**Purpose**: Queue system

---

## 3. Key Relationships Summary

### User → Profile (1:1)
- One user has one profile
- Profile stores extended information

### User → UserSubjectGroup (1:M)
- Tutor can teach multiple subject groups
- Each group has subjects with individual pricing

### UserSubjectGroupSubject → UserSubjectSlot (1:M)
- Each subject has multiple time slots
- Slots define tutor availability

### UserSubjectSlot → SlotBooking (1:M)
- Slot can be booked multiple times (different dates)
- SlotBooking is the core transaction

### User → SlotBooking (1:M as student, 1:M as tutor)
- Student books sessions
- Tutor provides sessions

### SlotBooking → Order → OrderItem (1:1:M)
- Booking creates order
- Order contains order items
- OrderItem polymorphically links to SlotBooking

### User → UserWallet (1:1)
- Tutor has wallet for earnings
- Wallet tracks balance and transactions

### User → Rating (1:M as student, 1:M as tutor)
- Students leave ratings
- Tutors receive ratings

### SlotBooking → Dispute (1:1)
- Booking can have one dispute
- Dispute has conversation thread

---

## 4. Polymorphic Relationships

### `orderable` (order_items)
**Targets**: SlotBooking, Course (if module enabled)

### `ratingable` (ratings)
**Targets**: SlotBooking, Course

### `disputable` (disputes)
**Targets**: SlotBooking

### `addressable` (addresses)
**Targets**: User, Rating

---

## 5. Soft Deletes

Models with soft delete:
- Profile (`deleted_at`)
- Subject (`deleted_at`)

---

## 6. JSON Columns

- `slot_bookings.meta_data` - Extra booking data (calendar event IDs, notes)
- `user_subject_slots.meta_data` - Slot metadata
- `order_items.options` - Item options
- `user_payout_methods.method_details` - Payout account details

---

## 7. Migration Chronology (Key Milestones)

1. **2022-10**: Foundation (users, profiles, countries, languages, addresses)
2. **2022-11**: Subject system, ratings, slots, identity verification
3. **2022-12**: Booking system, withdrawals
4. **2023-01**: Orders, billing, wallets
5. **2024-07**: Telescope, account settings, slot bookings, booking logs, certificates
6. **2024-08**: Order items
7. **2024-09-10**: Cart, blogs, addons
8. **2024-11**: Dispute system, role updates
9. **2025-01**: Notification templates
10. **2025-02**: Addon system updates

---

## 8. Database Optimization Notes

### Indexes
- Foreign keys are indexed automatically
- Unique constraints on: emails, slugs, pivot table combinations
- Composite indexes on polymorphic relationships

### Query Optimization
- Eager loading used extensively (via `$with` in models)
- Global scopes for active records (ActiveScope on Subject)
- Pagination for large datasets

### Caching Strategy
- Cache driver: redis/database (configurable)
- User online status cached (`Cache::has('user-online-' . $id)`)
- Settings cached (via optionbuilder)

---

**Next**: Phase 3 - Authentication & Authorization Analysis
