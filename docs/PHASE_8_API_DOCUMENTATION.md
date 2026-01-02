# PHASE 8: API DOCUMENTATION

## Table of Contents
1. [API Overview](#api-overview)
2. [Authentication](#authentication)
3. [Response Formats](#response-formats)
4. [Error Handling](#error-handling)
5. [Public API Endpoints](#public-api-endpoints)
6. [Authenticated API Endpoints](#authenticated-api-endpoints)
7. [API Resources](#api-resources)
8. [Rate Limiting & Security](#rate-limiting--security)

---

## 1. API Overview

### 1.1 API Architecture

**Base URL**: `https://yourdomain.com/api`

**API Type**: RESTful JSON API

**Authentication**: Laravel Sanctum (Token-based)

**Request Format**: JSON (`Content-Type: application/json`)

**Response Format**: JSON with standardized structure

**API Version**: No versioning (single version)

**Total Endpoints**: 50+ endpoints

### 1.2 API Features

**Supported Operations**:
- ✅ User authentication (register, login, social login)
- ✅ Tutor search and filtering
- ✅ Tutor profile viewing
- ✅ Booking management
- ✅ Cart operations
- ✅ Payment processing
- ✅ Review submission
- ✅ Dispute management
- ✅ Notifications
- ✅ Profile management
- ✅ Payout management (tutors)
- ✅ Educational credentials (CRUD)

**Not Supported**:
- ❌ Admin operations (web-only)
- ❌ Real-time messaging (uses web interface)
- ❌ Video conferencing (uses Zoom/Google Meet)

### 1.3 API Client Support

**Intended Clients**:
- Mobile applications (iOS/Android)
- Single Page Applications (SPA)
- Third-party integrations
- Internal microservices

**SDK Availability**: No official SDK (raw HTTP/REST)

---

## 2. Authentication

### 2.1 Authentication Flow

**Registration → Login → Token → Authenticated Requests**

```
1. Register (POST /api/register)
   ↓
2. Verify Email (check email_verified_at)
   ↓
3. Login (POST /api/login)
   ↓
4. Receive Bearer Token
   ↓
5. Use Token in Authorization Header
   ↓
6. Access Protected Endpoints
```

### 2.2 Register Endpoint

**Endpoint**: `POST /api/register`

**Authentication**: None (public)

**Request Body**:
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone_number": "+1 555-123-4567",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "user_role": "student",
    "terms": "accepted"
}
```

**Validation Rules**:
```json
{
    "first_name": "required|string|max:255",
    "last_name": "required|string|max:255",
    "email": "required|email|unique:users",
    "phone_number": "required|regex:/^(\\+?\\(?\\d{1,4}\\)?)?[\\d\\s\\-]{7,15}$/",
    "password": "required|confirmed|min:8",
    "user_role": "required|in:tutor,student",
    "terms": "required"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "User registered successfully",
    "data": {
        "token": "5|xJh7kP9qR2mN8vL3wY6tF1sZ4bC0dG",
        "user": {
            "id": 123,
            "email": "john.doe@example.com",
            "role": "student",
            "profile": {
                "first_name": "John",
                "last_name": "Doe",
                "image": null
            }
        },
        "email_verified": null
    }
}
```

**Error Response** (422):
```json
{
    "status": 422,
    "message": "Validation errors",
    "errors": {
        "email": "The email has already been taken.",
        "password": "The password confirmation does not match."
    }
}
```

### 2.3 Login Endpoint

**Endpoint**: `POST /api/login`

**Authentication**: None (public)

**Request Body**:
```json
{
    "email": "john.doe@example.com",
    "password": "SecurePass123!"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "User login successfully",
    "data": {
        "token": "6|aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2u",
        "user": {
            "id": 123,
            "email": "john.doe@example.com",
            "status": "active",
            "profile_completed": true,
            "verified": true,
            "role": "student",
            "profile": {
                "id": 456,
                "first_name": "John",
                "last_name": "Doe",
                "slug": "john-doe-123",
                "image": "profiles/john-doe.jpg",
                "gender": "male",
                "description": "Passionate learner seeking knowledge."
            },
            "address": {
                "country_id": 1,
                "state_id": 5,
                "city": "New York",
                "address": "123 Main St",
                "zipcode": "10001"
            },
            "languages": [
                {"id": 1, "name": "English"},
                {"id": 2, "name": "Spanish"}
            ],
            "balance": "$0.00"
        }
    }
}
```

**Error Responses**:

**User Not Found** (400):
```json
{
    "status": 400,
    "message": "User not found"
}
```

**Invalid Credentials** (400):
```json
{
    "status": 400,
    "message": "Email or password doesn't match"
}
```

**Email Not Verified** (400):
```json
{
    "status": 400,
    "message": "User not verified",
    "data": {
        "token": "...",
        "user": { ... }
    }
}
```

### 2.4 Social Login

**Endpoint**: `POST /api/social-login`

**Authentication**: None (public)

**Request Body**:
```json
{
    "auth_code": "4/0AZEOvhXj...",
    "provider": "google"
}
```

**Flow**:
1. Client obtains authorization code from Google/Facebook
2. Sends code to backend
3. Backend exchanges code for user info
4. Creates or retrieves user account
5. Returns token

**Success Response** (200):
```json
{
    "status": 200,
    "message": "User login successfully",
    "data": {
        "token": "7|xY9zW8vU7tS6rQ5pO4nM3lK2jH1g",
        "user": { ... }
    }
}
```

**Profile Missing Response** (422):
```json
{
    "status": 422,
    "message": "Profile information missing. Please complete your profile.",
    "data": {
        "email": "john.social@gmail.com"
    }
}
```

### 2.5 Complete Social Profile

**Endpoint**: `POST /api/social-profile`

**Authentication**: None (public)

**Request Body**:
```json
{
    "email": "john.social@gmail.com",
    "first_name": "John",
    "last_name": "Social",
    "phone_number": "+1 555-987-6543",
    "user_role": "tutor"
}
```

**Purpose**: Complete profile for social login users

**Success Response** (200):
```json
{
    "status": 200,
    "message": "User login successfully",
    "data": {
        "token": "8|fD3sA9pL2kJ7mN1qR5tY8wZ4bC0v",
        "user": { ... }
    }
}
```

### 2.6 Token Usage

**Header Format**:
```http
Authorization: Bearer 5|xJh7kP9qR2mN8vL3wY6tF1sZ4bC0dG
```

**Token Properties**:
- **Type**: Personal Access Token (Sanctum)
- **Expiration**: 7 days from creation
- **Name**: "lernen"
- **Abilities**: `['*']` (all permissions)

**Token Refresh**: Not automatic, user must re-login

**Token Revocation**:
```bash
POST /api/logout
Authorization: Bearer {token}
```

**Multiple Tokens**: Old tokens deleted on new login

```php
// Controller logic
$user->tokens()->where('name', 'lernen')->delete();
$success['token'] = $user->createToken('lernen', ['*'], now()->addDays(7))
    ->plainTextToken;
```

---

## 3. Response Formats

### 3.1 Success Response Structure

**Standard Success Response**:
```json
{
    "status": 200,
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

**HTTP Status Codes**:
- `200 OK` - Successful GET, PUT, PATCH, DELETE
- `201 Created` - Successful POST (resource created)

### 3.2 Pagination Response

**Paginated Collection**:
```json
{
    "status": 200,
    "data": {
        "list": [
            { "id": 1, "name": "Tutor 1" },
            { "id": 2, "name": "Tutor 2" },
            { "id": 3, "name": "Tutor 3" }
        ],
        "pagination": {
            "total": 47,
            "count": 3,
            "perPage": 15,
            "currentPage": 1,
            "totalPages": 4
        }
    }
}
```

**Implementation** (`TutorCollection`):
```php
public function toArray(Request $request): array
{
    return [
        'list' => FindTutorResource::collection($this->collection),
        'pagination' => [
            'total'        => $this->total(),
            'count'        => $this->count(),
            'perPage'      => $this->perPage(),
            'currentPage'  => $this->currentPage(),
            'totalPages'   => $this->lastPage()
        ],
    ];
}
```

### 3.3 Resource Response

**Single Resource**:
```json
{
    "status": 200,
    "data": {
        "id": 123,
        "email": "tutor@example.com",
        "role": "tutor",
        "avg_rating": 4.8,
        "total_reviews": 234,
        "min_price": "$25.00",
        "profile": {
            "first_name": "Jane",
            "last_name": "Smith",
            "slug": "jane-smith-123",
            "tagline": "Experienced Math tutor with 10+ years",
            "description": "I specialize in...",
            "image": "profiles/jane-smith.jpg"
        },
        "subjects": [
            {
                "id": 5,
                "subject_group_id": 2,
                "name": "Calculus",
                "price": "$30.00",
                "subject_group": "Mathematics"
            }
        ],
        "languages": [
            {"id": 1, "name": "English"},
            {"id": 3, "name": "French"}
        ]
    }
}
```

### 3.4 Empty Response

**No Data**:
```json
{
    "status": 200,
    "message": "Operation successful"
}
```

**Example**: Logout, mark notification as read

---

## 4. Error Handling

### 4.1 Error Response Structure

**Standard Error Response**:
```json
{
    "status": 400,
    "message": "Error message"
}
```

**Validation Error Response** (422):
```json
{
    "status": 422,
    "message": "Validation errors",
    "errors": {
        "field_name": "Error message",
        "another_field": "Another error message"
    }
}
```

### 4.2 HTTP Status Codes

| Status Code | Meaning | Usage |
|-------------|---------|-------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid parameters or business logic error |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Demo site restriction or insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error (logged) |

### 4.3 Common Error Scenarios

**Authentication Errors**:

**Missing Token** (401):
```json
{
    "status": 401,
    "message": "Unauthenticated."
}
```

**Expired Token** (401):
```json
{
    "status": 401,
    "message": "Token has expired."
}
```

**Invalid Token** (401):
```json
{
    "status": 401,
    "message": "Invalid token."
}
```

**Resource Not Found Errors**:

**Tutor Not Found** (404):
```json
{
    "status": 404,
    "message": "Tutor not found."
}
```

**Booking Not Found** (404):
```json
{
    "status": 404,
    "message": "Booking not found."
}
```

**Business Logic Errors**:

**Demo Site Restriction** (403):
```json
{
    "status": 403,
    "message": "This action is not allowed on the demo site."
}
```

**Slot Fully Booked** (400):
```json
{
    "status": 400,
    "message": "This time slot is fully booked."
}
```

**Insufficient Wallet Balance** (400):
```json
{
    "status": 400,
    "message": "Insufficient wallet balance."
}
```

### 4.4 Error Logging

**Server Errors**: Automatically logged to `storage/logs/laravel.log`

**Example Log Entry**:
```
[2026-01-02 10:30:45] production.ERROR: Tutor not found {"user_id":123,"tutor_slug":"invalid-slug"}
```

---

## 5. Public API Endpoints

### 5.1 Authentication Endpoints

#### Register
```
POST /api/register
```
**Description**: Create new user account

**Request**: See [2.2 Register Endpoint](#22-register-endpoint)

**Response**: Token + User object

---

#### Login
```
POST /api/login
```
**Description**: Authenticate user and obtain token

**Request**: See [2.3 Login Endpoint](#23-login-endpoint)

**Response**: Token + User object

---

#### Social Login
```
POST /api/social-login
```
**Description**: Login via Google/Facebook OAuth

**Request**: See [2.4 Social Login](#24-social-login)

**Response**: Token + User object or profile completion required

---

#### Create Social Profile
```
POST /api/social-profile
```
**Description**: Complete profile for social login users

**Request**: See [2.5 Complete Social Profile](#25-complete-social-profile)

**Response**: Token + User object

---

#### Forgot Password
```
POST /api/forget-password
```
**Description**: Send password reset link to email

**Request**:
```json
{
    "email": "user@example.com"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Password reset link sent to your email."
}
```

**Error Response** (400):
```json
{
    "status": 400,
    "message": "User not found with this email."
}
```

---

### 5.2 Tutor Search Endpoints

#### Get Recommended Tutors
```
GET /api/recommended-tutors
```
**Description**: Get top-rated tutors

**Query Parameters**: None

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {
            "id": 45,
            "email": "tutor1@example.com",
            "avg_rating": 4.9,
            "total_reviews": 156,
            "min_price": "$30.00",
            "is_favorite": false,
            "profile": {
                "first_name": "Alice",
                "last_name": "Johnson",
                "slug": "alice-johnson-45",
                "tagline": "Expert in Advanced Mathematics",
                "image": "profiles/alice.jpg"
            },
            "subjects": [
                {"id": 3, "name": "Algebra", "price": "$30.00"},
                {"id": 5, "name": "Calculus", "price": "$35.00"}
            ]
        }
    ]
}
```

**Sorting**: By ratings, descending

**Limit**: 10 tutors

---

#### Find Tutors (Search & Filter)
```
GET /api/find-tutors
```
**Description**: Search tutors with advanced filters

**Query Parameters**:
```
?keyword=math
&subject_ids=3,5,7
&language_ids=1,2
&min_price=10
&max_price=50
&rating=4
&country_id=1
&state_id=5
&gender=female
&page=1
&per_page=15
```

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| keyword | string | Search in name, tagline, description |
| subject_ids | string | Comma-separated subject IDs |
| language_ids | string | Comma-separated language IDs |
| min_price | numeric | Minimum hourly rate |
| max_price | numeric | Maximum hourly rate |
| rating | integer | Minimum rating (1-5) |
| country_id | integer | Country ID filter |
| state_id | integer | State ID filter |
| gender | string | male, female, not_specified |
| page | integer | Page number (default: 1) |
| per_page | integer | Results per page (default: 15) |

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "list": [
            {
                "id": 23,
                "avg_rating": 4.7,
                "total_reviews": 89,
                "min_price": "$25.00",
                "is_favorite": true,
                "profile": { ... },
                "subjects": [ ... ]
            }
        ],
        "pagination": {
            "total": 47,
            "count": 15,
            "perPage": 15,
            "currentPage": 1,
            "totalPages": 4
        }
    }
}
```

---

#### Get Tutor Detail
```
GET /api/tutor/{slug}
```
**Description**: Get detailed tutor profile

**Path Parameters**:
- `slug` (string) - Tutor's unique slug (e.g., "john-doe-123")

**Example**: `GET /api/tutor/jane-smith-45`

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "id": 45,
        "email": "jane@example.com",
        "avg_rating": 4.8,
        "total_reviews": 234,
        "active_students": 67,
        "min_price": "$25.00",
        "is_favorite": false,
        "verified": true,
        "profile": {
            "id": 90,
            "first_name": "Jane",
            "last_name": "Smith",
            "slug": "jane-smith-45",
            "gender": "female",
            "tagline": "Experienced Math tutor with 10+ years",
            "description": "I specialize in helping students...",
            "intro_video": "videos/jane-intro.mp4",
            "image": "profiles/jane-smith.jpg",
            "native_language": "English",
            "verified_at": "2025-03-15T10:30:00Z"
        },
        "address": {
            "country_id": 1,
            "state_id": 5,
            "city": "Boston",
            "address": "456 Academic Ave",
            "zipcode": "02108"
        },
        "subjects": [
            {
                "id": 3,
                "subject_group_id": 2,
                "name": "Algebra",
                "price": "$25.00",
                "subject_group": "Mathematics"
            },
            {
                "id": 5,
                "subject_group_id": 2,
                "name": "Calculus",
                "price": "$35.00",
                "subject_group": "Mathematics"
            }
        ],
        "languages": [
            {"id": 1, "name": "English"},
            {"id": 3, "name": "French"}
        ],
        "educations": [
            {
                "id": 12,
                "university": "MIT",
                "degree": "Master's in Mathematics",
                "start_date": "2010",
                "end_date": "2012"
            }
        ],
        "experiences": [
            {
                "id": 8,
                "title": "Math Tutor",
                "company": "ABC Learning Center",
                "description": "Tutored high school students",
                "start_date": "2015-01-01",
                "end_date": "2020-12-31"
            }
        ],
        "reviews": [
            {
                "id": 456,
                "rating": 5,
                "comment": "Excellent tutor! Very patient and knowledgeable.",
                "student": {
                    "id": 89,
                    "first_name": "John",
                    "last_name": "Student",
                    "image": "profiles/john-student.jpg"
                },
                "created_at": "2025-12-20T14:30:00Z"
            }
        ]
    }
}
```

**Error** (404):
```json
{
    "status": 404,
    "message": "Tutor not found."
}
```

**Error** (401):
```json
{
    "status": 401,
    "message": "Tutor profile not verified."
}
```

---

#### Get Tutor Available Slots
```
GET /api/tutor-available-slots
```
**Description**: Get tutor's available time slots for a date range

**Query Parameters**:
```
?user_id=45
&start_date=2026-01-05
&end_date=2026-01-11
&type=next
&user_time_zone=America/New_York
&filter[subject_ids]=3,5
```

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| user_id | integer | Tutor user ID (required) |
| start_date | date | Start date (YYYY-MM-DD) |
| end_date | date | End date (YYYY-MM-DD) |
| type | string | prev, next (navigate weeks) |
| user_time_zone | string | User's timezone (default: UTC) |
| filter[subject_ids] | string | Filter by subject IDs |

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "start_date": "2026-01-05 00:00:00",
        "end_date": "2026-01-11 23:59:59",
        "05 Jan 2026": [
            {
                "id": 234,
                "subject_id": 3,
                "subject_name": "Algebra",
                "subject_group": "Mathematics",
                "date": "2026-01-05",
                "start_time": "09:00 AM",
                "end_time": "10:00 AM",
                "duration": 60,
                "session_fee": "$30.00",
                "spaces": 5,
                "available_spaces": 3,
                "status": "available"
            },
            {
                "id": 235,
                "subject_id": 5,
                "subject_name": "Calculus",
                "date": "2026-01-05",
                "start_time": "02:00 PM",
                "end_time": "03:30 PM",
                "duration": 90,
                "session_fee": "$45.00",
                "spaces": 3,
                "available_spaces": 1,
                "status": "available"
            }
        ],
        "06 Jan 2026": [
            { ... }
        ]
    }
}
```

**Note**: All times displayed in user's timezone

---

#### Get Slot Detail
```
GET /api/slot-detail/{id}
```
**Description**: Get details of a specific time slot

**Path Parameters**:
- `id` (integer) - Slot ID

**Example**: `GET /api/slot-detail/234`

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "id": 234,
        "tutor_id": 45,
        "subject_id": 3,
        "subject_name": "Algebra",
        "subject_group": "Mathematics",
        "date": "2026-01-05",
        "start_time": "09:00 AM",
        "end_time": "10:00 AM",
        "duration": 60,
        "session_fee": "$30.00",
        "spaces": 5,
        "available_spaces": 3,
        "total_booked": 2,
        "status": "available",
        "description": "Introduction to Algebraic Equations",
        "tutor": {
            "id": 45,
            "first_name": "Jane",
            "last_name": "Smith",
            "image": "profiles/jane-smith.jpg",
            "avg_rating": 4.8
        }
    }
}
```

---

### 5.3 Taxonomy Endpoints

#### Get Countries
```
GET /api/countries
```
**Description**: Get list of all countries

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {"id": 1, "name": "United States", "code": "US"},
        {"id": 2, "name": "United Kingdom", "code": "GB"},
        {"id": 3, "name": "Canada", "code": "CA"}
    ]
}
```

---

#### Get States
```
GET /api/states?country_id=1
```
**Description**: Get states for a specific country

**Query Parameters**:
- `country_id` (integer) - Country ID

**Response** (200):
```json
{
    "status": 200,
    "message": "States fetched successfully",
    "data": [
        {"id": 1, "name": "California"},
        {"id": 2, "name": "Texas"},
        {"id": 3, "name": "New York"}
    ]
}
```

**Error** (404):
```json
{
    "status": 404,
    "message": "No states found for this country."
}
```

---

#### Get Languages
```
GET /api/languages
```
**Description**: Get list of all languages

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {"id": 1, "name": "English"},
        {"id": 2, "name": "Spanish"},
        {"id": 3, "name": "French"}
    ]
}
```

---

## 6. Authenticated API Endpoints

**Authentication Required**: All endpoints in this section require Bearer token

**Header**:
```
Authorization: Bearer {token}
```

### 6.1 User Management

#### Get Profile
```
GET /api/profile-settings/{id}
```
**Description**: Get user profile details

**Path Parameters**:
- `id` (integer) - User ID

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "id": 123,
        "email": "user@example.com",
        "role": "student",
        "profile": {
            "first_name": "John",
            "last_name": "Doe",
            "phone_number": "+1 555-123-4567",
            "gender": "male",
            "description": "Learning enthusiast",
            "image": "profiles/john-doe.jpg"
        },
        "address": {
            "country_id": 1,
            "state_id": 5,
            "city": "New York",
            "zipcode": "10001"
        },
        "languages": [
            {"id": 1, "name": "English"}
        ]
    }
}
```

---

#### Update Profile
```
POST /api/profile-settings/{id}
```
**Description**: Update user profile

**Path Parameters**:
- `id` (integer) - User ID

**Request** (multipart/form-data):
```
first_name: John
last_name: Doe
phone_number: +1 555-123-4567
gender: male
description: Updated bio
image: [file]
user_languages[]: 1
user_languages[]: 2
native_language: English
country: 1
state: 5
city: New York
zipcode: 10001
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Profile updated successfully",
    "data": {
        "user": { ... }
    }
}
```

---

#### Update Password
```
POST /api/update-password/{id}
```
**Description**: Change user password

**Request**:
```json
{
    "current_password": "OldPass123!",
    "new_password": "NewPass456!",
    "new_password_confirmation": "NewPass456!"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Password updated successfully"
}
```

**Error** (400):
```json
{
    "status": 400,
    "message": "Current password is incorrect"
}
```

---

#### Delete Account
```
DELETE /api/delete-account
```
**Description**: Permanently delete user account

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Account deleted successfully"
}
```

---

#### Logout
```
POST /api/logout
```
**Description**: Revoke current access token

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Logged out successfully"
}
```

---

### 6.2 Booking Management

#### Get Upcoming Bookings
```
GET /api/upcoming-bookings
```
**Description**: Get user's upcoming bookings (tutor or student)

**Query Parameters**:
```
?show_by=daily
&type=next
&start_date=2026-01-05
&end_date=2026-01-05
&filter[status]=active
```

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| show_by | string | daily, weekly, monthly |
| type | string | prev, next (navigate) |
| start_date | date | Filter start date |
| end_date | date | Filter end date |
| filter[status] | string | active, completed, cancelled |

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "bookings": [
            {
                "id": 789,
                "slot_id": 234,
                "booking_date": "2026-01-05",
                "start_time": "09:00 AM",
                "end_time": "10:00 AM",
                "duration": 60,
                "session_fee": "$30.00",
                "status": "active",
                "meeting_link": "https://zoom.us/j/123456789",
                "subject": {
                    "id": 3,
                    "name": "Algebra"
                },
                "tutor": {
                    "id": 45,
                    "first_name": "Jane",
                    "last_name": "Smith",
                    "image": "profiles/jane-smith.jpg"
                },
                "student": {
                    "id": 123,
                    "first_name": "John",
                    "last_name": "Doe",
                    "image": "profiles/john-doe.jpg"
                }
            }
        ],
        "date_range": {
            "start": "2026-01-05",
            "end": "2026-01-05"
        }
    }
}
```

---

#### Complete Booking
```
POST /api/complete-booking/{id}
```
**Description**: Mark booking as completed (students can then review)

**Path Parameters**:
- `id` (integer) - Booking ID

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Booking completed successfully"
}
```

---

#### Book Free Slot
```
POST /api/book-free-slot
```
**Description**: Book a time slot with $0 fee

**Request**:
```json
{
    "slot_id": 234,
    "booking_date": "2026-01-05"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Slot booked successfully",
    "data": {
        "booking_id": 790
    }
}
```

---

### 6.3 Cart & Checkout

#### Get Cart
```
GET /api/booking-cart
```
**Description**: Get items in cart

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "items": [
            {
                "id": 12,
                "slot_id": 234,
                "booking_date": "2026-01-05",
                "start_time": "09:00 AM",
                "end_time": "10:00 AM",
                "session_fee": "$30.00",
                "subject": "Algebra",
                "tutor": {
                    "first_name": "Jane",
                    "last_name": "Smith"
                }
            }
        ],
        "total": "$30.00"
    }
}
```

---

#### Add to Cart
```
POST /api/booking-cart
```
**Description**: Add time slot to cart

**Request**:
```json
{
    "slot_id": 234,
    "booking_date": "2026-01-05"
}
```

**Success Response** (201):
```json
{
    "status": 201,
    "message": "Added to cart successfully",
    "data": {
        "cart_item_id": 13
    }
}
```

---

#### Remove from Cart
```
DELETE /api/booking-cart/{id}
```
**Description**: Remove item from cart

**Path Parameters**:
- `id` (integer) - Cart item ID

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Removed from cart"
}
```

---

#### Checkout
```
POST /api/checkout
```
**Description**: Process cart checkout

**Request**:
```json
{
    "payment_method": "stripe",
    "card_token": "tok_visa"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Payment successful",
    "data": {
        "order_id": 456,
        "total": "$30.00",
        "bookings": [789, 790]
    }
}
```

---

### 6.4 Reviews & Ratings

#### Add Review
```
POST /api/review/{id}
```
**Description**: Submit rating and review for completed booking

**Path Parameters**:
- `id` (integer) - Booking ID

**Request**:
```json
{
    "rating": 5,
    "comment": "Excellent tutor! Very patient and knowledgeable."
}
```

**Validation**:
- `rating`: required, integer, 1-5
- `comment`: required, string

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Review submitted successfully"
}
```

**Error** (400):
```json
{
    "status": 400,
    "message": "You can only review completed bookings"
}
```

---

#### Get Student Reviews
```
GET /api/student-reviews/{id}
```
**Description**: Get reviews written by a student

**Path Parameters**:
- `id` (integer) - Student user ID

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {
            "id": 456,
            "rating": 5,
            "comment": "Great experience!",
            "tutor": {
                "id": 45,
                "first_name": "Jane",
                "last_name": "Smith"
            },
            "created_at": "2025-12-20T14:30:00Z"
        }
    ]
}
```

---

### 6.5 Disputes

#### Create Dispute
```
POST /api/dispute/{id}
```
**Description**: Raise a dispute for a booking

**Path Parameters**:
- `id` (integer) - Booking ID

**Request**:
```json
{
    "reason": "Tutor didn't show up",
    "description": "I waited for 20 minutes but the tutor never joined."
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Dispute created successfully",
    "data": {
        "dispute_id": 89
    }
}
```

---

#### Get Disputes
```
GET /api/dispute-listing
```
**Description**: Get user's disputes

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "list": [
            {
                "id": 89,
                "reason": "Tutor didn't show up",
                "status": "open",
                "created_at": "2026-01-03T10:00:00Z",
                "booking": {
                    "id": 789,
                    "booking_date": "2026-01-02",
                    "subject": "Algebra"
                }
            }
        ]
    }
}
```

---

#### Get Dispute Detail
```
GET /api/dispute-detail/{id}
```
**Description**: Get dispute details

**Path Parameters**:
- `id` (integer) - Dispute ID

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "id": 89,
        "reason": "Tutor didn't show up",
        "description": "I waited for 20 minutes...",
        "status": "open",
        "created_at": "2026-01-03T10:00:00Z",
        "resolved_at": null,
        "resolution": null,
        "booking": { ... },
        "creator": { ... },
        "responsible": { ... }
    }
}
```

---

#### Get Dispute Discussion
```
GET /api/dispute-discussion/{id}
```
**Description**: Get conversation thread for a dispute

**Path Parameters**:
- `id` (integer) - Dispute ID

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {
            "id": 234,
            "message": "I waited for 20 minutes but tutor never showed up.",
            "sender": {
                "id": 123,
                "first_name": "John",
                "role": "student"
            },
            "created_at": "2026-01-03T10:05:00Z"
        },
        {
            "id": 235,
            "message": "I apologize, there was a technical issue.",
            "sender": {
                "id": 45,
                "first_name": "Jane",
                "role": "tutor"
            },
            "created_at": "2026-01-03T11:30:00Z"
        }
    ]
}
```

---

#### Reply to Dispute
```
POST /api/dispute-reply/{id}
```
**Description**: Add message to dispute conversation

**Path Parameters**:
- `id` (integer) - Dispute ID

**Request**:
```json
{
    "message": "I understand. Can we reschedule?"
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Reply sent successfully"
}
```

---

### 6.6 Favorites

#### Get Favorite Tutors
```
GET /api/favourite-tutors
```
**Description**: Get student's favorite tutors

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {
            "id": 45,
            "first_name": "Jane",
            "last_name": "Smith",
            "avg_rating": 4.8,
            "min_price": "$25.00",
            "subjects": [ ... ]
        }
    ]
}
```

---

#### Add/Remove Favorite
```
PUT /api/favourite-tutors/{id}
```
**Description**: Toggle tutor as favorite

**Path Parameters**:
- `id` (integer) - Tutor user ID

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Tutor added to favorites"
}
```

OR

```json
{
    "status": 200,
    "message": "Tutor removed from favorites"
}
```

---

### 6.7 Invoices

#### Get Invoices
```
GET /api/invoices
```
**Description**: Get user's invoices (student or tutor)

**Query Parameters**:
```
?filter[status]=paid
&filter[date_from]=2026-01-01
&filter[date_to]=2026-01-31
&page=1
```

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "list": [
            {
                "id": 456,
                "invoice_number": "INV-2026-000456",
                "date": "2026-01-05",
                "total": "$30.00",
                "status": "paid",
                "items": [
                    {
                        "description": "Algebra Session - Jan 5, 2026",
                        "amount": "$30.00"
                    }
                ]
            }
        ],
        "pagination": { ... }
    }
}
```

---

### 6.8 Notifications

#### Get Notifications
```
GET /api/notifications
```
**Description**: Get user notifications

**Query Parameters**:
```
?unread_only=true
&page=1
```

**Response** (200):
```json
{
    "status": 200,
    "data": [
        {
            "id": "9a123456-7890-1234-5678-90abcdef1234",
            "type": "booking_confirmed",
            "title": "Booking Confirmed",
            "message": "Your booking for Algebra on Jan 5 is confirmed.",
            "read_at": null,
            "created_at": "2026-01-03T10:00:00Z"
        }
    ]
}
```

---

#### Mark Notification as Read
```
POST /api/notifications/{id}/read
```
**Description**: Mark single notification as read

**Path Parameters**:
- `id` (string) - Notification UUID

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Notification marked as read"
}
```

---

#### Mark All Notifications as Read
```
POST /api/notifications/read-all
```
**Description**: Mark all notifications as read

**Success Response** (200):
```json
{
    "status": 200,
    "message": "All notifications marked as read"
}
```

---

### 6.9 Tutor-Specific Endpoints

#### Get Tutor Earnings
```
GET /api/my-earning/{id}
```
**Description**: Get tutor's earnings summary

**Path Parameters**:
- `id` (integer) - Tutor user ID

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "total_earned": "$5,420.00",
        "pending_funds": "$320.00",
        "available_balance": "$2,100.00",
        "withdrawn": "$3,000.00",
        "monthly_earnings": [
            {"month": "Jan 2026", "amount": "$450.00"},
            {"month": "Dec 2025", "amount": "$680.00"}
        ]
    }
}
```

---

#### Get Payout History
```
GET /api/tutor-payouts/{id}
```
**Description**: Get tutor's payout history

**Response** (200):
```json
{
    "status": 200,
    "data": {
        "list": [
            {
                "id": 234,
                "amount": "$500.00",
                "method": "bank_transfer",
                "status": "completed",
                "requested_at": "2026-01-01T10:00:00Z",
                "processed_at": "2026-01-03T14:30:00Z"
            }
        ],
        "pagination": { ... }
    }
}
```

---

#### Request Withdrawal
```
POST /api/user-withdrawal
```
**Description**: Request payout withdrawal

**Request**:
```json
{
    "amount": 500.00,
    "payout_method_id": 12
}
```

**Success Response** (200):
```json
{
    "status": 200,
    "message": "Withdrawal request submitted successfully",
    "data": {
        "request_id": 235
    }
}
```

**Error** (400):
```json
{
    "status": 400,
    "message": "Insufficient available balance"
}
```

---

## 7. API Resources

### 7.1 Resource Transformers

**Laravel API Resources**: Used to transform models to JSON

**Directory**: `app/Http/Resources/`

**Major Resources**:
- `UserResource` - User model with profile, languages, subjects
- `FindTutorResource` - Tutor search result
- `TutorDetailResource` - Complete tutor profile
- `SlotBookingResource` - Booking details
- `TutorSlotResource` - Time slot details
- `InvoiceResource` - Invoice with line items
- `DisputeResource` - Dispute with conversation
- `NotificationResource` - Notification details

### 7.2 UserResource Example

**File**: `app/Http/Resources/UserResource.php`

**Transformation**:
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->whenHas('id'),
        'email' => $this->whenHas('email'),
        'status' => $this->whenHas('status'),
        'is_favorite' => $this->whenHas('is_favorite'),
        'avg_rating' => $this->whenHas('avg_rating'),
        'min_price' => $this->whenHas('min_price', function ($min_price) {
            return formatAmount($min_price);
        }),
        'total_reviews' => $this->whenHas('total_reviews'),
        'verified' => !empty($this->verfied_at),
        'profile' => new ProfileResource($this->whenLoaded('profile')),
        'subjects' => UserSubjectResource::collection($this->whenLoaded('subjects')),
        'languages' => LanguageResource::collection($this->whenLoaded('languages')),
        'address' => new AddressResource($this->whenLoaded('address')),
        'role' => $this->whenHas('default_role'),
        'balance' => $this->whenLoaded('userWallet', function() {
            return formatAmount($this->userWallet?->amount ?? 0);
        })
    ];
}
```

**Features**:
- `whenHas()` - Include only if attribute exists
- `whenLoaded()` - Include only if relationship loaded
- Nested resources for related models
- Custom formatting (currency, dates)

---

## 8. Rate Limiting & Security

### 8.1 Rate Limiting

**Default Rate Limit**: 60 requests per minute per IP/user

**Configuration**: `app/Providers/RouteServiceProvider.php`

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Rate Limit Headers**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1735823400
```

**Rate Limit Exceeded** (429):
```json
{
    "status": 429,
    "message": "Too Many Requests"
}
```

**Retry-After Header**: Indicates seconds until limit resets

### 8.2 CORS Configuration

**File**: `config/cors.php`

**Settings**:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],  // Production: specify domains
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

**Production**: Restrict `allowed_origins` to your domains

### 8.3 API Security Best Practices

**Implemented**:
✅ Token-based authentication (Sanctum)
✅ HTTPS required (production)
✅ CSRF protection (SPA)
✅ Rate limiting
✅ Input sanitization
✅ SQL injection prevention (Eloquent ORM)
✅ XSS prevention (sanitization)
✅ Password hashing (bcrypt)

**Recommendations**:
- Use HTTPS in production
- Rotate tokens regularly
- Implement IP whitelisting for sensitive operations
- Add API request logging
- Monitor for abuse patterns
- Implement stricter rate limits for write operations

---

## Summary

**API Overview**:
- **50+ Endpoints**: Authentication, search, booking, payment, reviews, disputes
- **RESTful Design**: Standard HTTP methods (GET, POST, PUT, DELETE)
- **JSON Responses**: Standardized success and error formats
- **Token Authentication**: Laravel Sanctum with 7-day expiration

**Key Endpoint Categories**:
1. **Public** (15+): Register, login, search tutors, view profiles
2. **Student** (20+): Cart, checkout, bookings, reviews, favorites
3. **Tutor** (10+): Earnings, payouts, withdrawal requests
4. **Shared** (10+): Profile, notifications, disputes, messages

**Response Structure**:
```json
{
    "status": 200,
    "message": "Optional message",
    "data": { ... }
}
```

**Error Codes**:
- 400: Bad request / business logic error
- 401: Unauthorized (missing/invalid token)
- 403: Forbidden (demo site, insufficient permissions)
- 404: Resource not found
- 422: Validation errors
- 429: Rate limit exceeded

**Security Features**:
🔒 Token authentication
🔒 60 req/min rate limiting
🔒 Input validation & sanitization
🔒 CORS configuration
🔒 HTTPS enforcement (production)

**Best Practices**:
✅ Consistent response format
✅ Detailed error messages
✅ Pagination for lists
✅ Resource transformers
✅ Timezone-aware responses
✅ Currency formatting

**Next Steps for Implementation**:
1. Generate Swagger/OpenAPI documentation
2. Create Postman collection
3. Add API versioning (/api/v1)
4. Implement webhook system
5. Add API analytics/monitoring
