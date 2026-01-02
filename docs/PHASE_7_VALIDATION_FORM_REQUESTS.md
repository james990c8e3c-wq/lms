# PHASE 7: VALIDATION & FORM REQUESTS

## Table of Contents
1. [Validation Architecture Overview](#validation-architecture-overview)
2. [Form Request Classes](#form-request-classes)
3. [Validation Rules Reference](#validation-rules-reference)
4. [Custom Validation Logic](#custom-validation-logic)
5. [Livewire Validation](#livewire-validation)
6. [Client-Side Validation](#client-side-validation)
7. [Error Handling & Display](#error-handling--display)
8. [Sanitization & Input Cleaning](#sanitization--input-cleaning)

---

## 1. Validation Architecture Overview

### 1.1 Validation Strategy

**Multi-Layer Validation Approach**:
```
1. Client-Side Validation (JavaScript/Alpine.js)
   ↓ (Immediate feedback)
2. Livewire Real-Time Validation (wire:model.blur)
   ↓ (AJAX validation as user types)
3. Form Request Validation (Server-Side)
   ↓ (Controller action validation)
4. Database Constraints (Foreign keys, unique, not null)
   ↓ (Final safety net)
```

**Benefits**:
- **User Experience**: Instant feedback before submission
- **Security**: Server-side validation prevents bypass
- **Data Integrity**: Database constraints as final safety
- **DRY Principle**: Reusable validation rules in Form Requests

### 1.2 Validation Components

**Form Request Classes**: 31+ dedicated classes in `app/Http/Requests/`

**Directory Structure**:
```
app/Http/Requests/
├── BaseFormRequest.php           - Base class with API error handling
├── Auth/
│   ├── LoginRequest.php          - Login validation
│   ├── RegisterUserRequest.php   - User registration
│   ├── ResetPasswordRequest.php  - Password reset
│   ├── SocialLoginRequest.php    - OAuth login
│   └── AdminUserRequest.php      - Admin user creation
├── Common/
│   ├── PersonalDetail/
│   │   └── PersonalDetailRequest.php  - Profile updates
│   ├── AccountSetting/
│   │   ├── AccountSettingStoreRequest.php
│   │   └── ZoomSettingStoreRequest.php
│   ├── Identity/
│   │   └── IdentityStoreRequest.php   - ID verification
│   └── CartRequest/
│       └── CartRequest.php            - Cart operations
├── Student/
│   ├── Booking/
│   │   ├── CreateDisputeRequest.php   - Dispute creation
│   │   ├── ReviewStoreRequest.php     - Rating/review submission
│   │   ├── RequestSessionRequest.php  - Session booking request
│   │   └── SendMessageRequest.php     - Messaging
│   ├── BillingDetail/
│   │   └── BillingDetailStoreRequest.php
│   ├── Order/
│   │   └── OrderRequest.php
│   └── Payout/
│       └── PayoutRequest.php
└── Tutor/
    ├── Subject/
    │   └── SubjectRequest.php        - Subject management
    ├── ManageSessions/
    │   ├── SessionStoreRequest.php   - Time slot creation
    │   ├── ResheduleSessionStoreRequest.php
    │   └── SubjectSessionStoreRequest.php
    ├── Certificate/
    ├── Education/
    ├── Experience/
    ├── Payout/
    └── Withdrawal/
```

### 1.3 Validation Flow

**API Request Flow**:
```
1. API Request with Bearer Token
   ↓
2. Sanctum Authentication
   ↓
3. Form Request Validation (extends BaseFormRequest)
   ↓
4. Controller Action
   ↓
5. Success Response (200-201)
   OR
   Validation Error Response (422)
```

**Web Request Flow (Livewire)**:
```
1. User Input in Browser
   ↓
2. Alpine.js Client Validation (optional)
   ↓
3. Livewire Component wire:model.blur (real-time)
   ↓
4. Livewire $this->validate() on submit
   ↓
5. Form Request Validation (if forwarded to controller)
   ↓
6. Success or Error Message Display
```

---

## 2. Form Request Classes

### 2.1 Base Form Request

**File**: `app/Http/Requests/BaseFormRequest.php`

**Purpose**: Centralized API validation error handling

**Implementation**:
```php
<?php

namespace App\Http\Requests;

use App\Traits\ApiResponser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseFormRequest extends FormRequest
{
    use ApiResponser;

    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(
            $this->validationError('Validation errors', $validator->errors())
        );
    }
}
```

**Key Features**:
1. **ApiResponser Trait**: Standardized JSON responses
2. **failedValidation Override**: Returns 422 status with structured errors
3. **Inheritance**: All API Form Requests extend this

**Error Response Format** (422):
```json
{
    "status": 422,
    "message": "Validation errors",
    "errors": {
        "email": "The email field is required.",
        "password": "The password must be at least 8 characters."
    }
}
```

### 2.2 Registration Request

**File**: `app/Http/Requests/Auth/RegisterUserRequest.php`

**Usage**: User registration (tutor or student)

**Implementation**:
```php
<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function rules(): array
    {
        $isProfilePhoneMendatory = setting('_lernen.phone_number_on_signup') == 'yes';
        
        return [
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone_number'  => $isProfilePhoneMendatory 
                ? 'required|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/' 
                : 'nullable|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/',
            'password'      => ['required', 'string', 'confirmed', Password::defaults()],
            'user_role'     => 'required|in:tutor,student',
            'terms'         => 'required|string'
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'first_name'    => sanitizeTextField($this->first_name),
            'last_name'     => sanitizeTextField($this->last_name),
        ]);
    }
}
```

**Key Validation Rules**:
1. **email**: `unique:users` - Checks email not already registered
2. **password**: `Password::defaults()` - Laravel 11 password rules (min 8 chars)
3. **phone_number**: Conditional based on admin setting
4. **user_role**: Must be 'tutor' or 'student'
5. **prepareForValidation**: Sanitizes inputs before validation

**Dynamic Validation**:
- Phone required/optional based on `setting('_lernen.phone_number_on_signup')`
- Allows flexibility per deployment

### 2.3 Session Store Request

**File**: `app/Http/Requests/Tutor/ManageSessions/SessionStoreRequest.php`

**Usage**: Tutor creating time slot availability

**Implementation**:
```php
<?php

namespace App\Http\Requests\Tutor\ManageSessions;

use Illuminate\Foundation\Http\FormRequest;

class SessionStoreRequest extends FormRequest {
    
    public function rules() {
        return [
            'subject_group_id'  => 'required|integer|gt:0',
            'date_range'        => 'required',
            'start_time'        => 'required',
            'end_time'          => 'required|after:start_time',
            'spaces'            => 'required|integer|min:1',
            'session_fee'       => isPaidSystem() ? 'required|numeric' : 'nullable',
            'duration'          => 'required',
            'break'             => 'required',
            'recurring_days'    => 'nullable',
            'description'       => 'required',
        ];
    }

    public function messages() {
        return [
            'required'      => __('validation.required_field'),
            'end_time'      => __('validation.time_range_error')
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'description' => sanitizeTextField($this->description, keep_linebreak: true),
        ]);
    }
}
```

**Business Logic Validation**:
1. **end_time**: `after:start_time` - Ensures valid time range
2. **session_fee**: Conditional on `isPaidSystem()` helper
3. **spaces**: `min:1` - At least one booking space
4. **Custom Messages**: Translated validation messages

### 2.4 Dispute Creation Request

**File**: `app/Http/Requests/Student/Booking/CreateDisputeRequest.php`

**Usage**: Student raising a dispute

**Implementation**:
```php
<?php

namespace App\Http\Requests\Student\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDisputeRequest extends FormRequest
{
    public function rules(): array
    {
        $disputeReasons = collect(setting('_dispute_setting.dispute_reasons') ?? [])
            ->pluck('dispute_reason')
            ->toArray();
        
        return [
            'reason'      => ['required', 'string', Rule::in($disputeReasons)],
            'description' => 'required|string'
        ];
    }

    public function messages() {
        return [
            'required' => __('validation.required_field'),
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'description' => sanitizeTextField($this->description)
        ]);
    }
}
```

**Dynamic Validation**:
- **Rule::in($disputeReasons)**: Validates against admin-configured dispute reasons
- Fetches allowed reasons from settings database
- Prevents arbitrary dispute reason submission

### 2.5 Personal Detail Request

**File**: `app/Http/Requests/Common/PersonalDetail/PersonalDetailRequest.php`

**Usage**: Profile completion for tutors and students

**Implementation** (abbreviated):
```php
<?php

namespace App\Http\Requests\Common\PersonalDetail;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class PersonalDetailRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $enableGooglePlaces = setting('_api.enable_google_places') ?? '0';
        $isProfilePhoneMendatory = setting('_lernen.profile_phone_number') == 'yes';
        $isProfileVideoMendatory = setting('_lernen.profile_video') == 'yes';
        
        $rules = [
            'first_name'        => 'required|string|min:3|max:150',
            'phone_number'      => $isProfilePhoneMendatory 
                ? 'required|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/' 
                : 'nullable|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/',
            'last_name'         => 'sometimes|string|min:3|max:150',
            'gender'            => 'required|in:male,female,not_specified',
            'user_languages'    => 'required|array|min:1',
            'native_language'   => 'required|string:max:255',
            'description'       => 'required|string|min:20|max:65535',
            'email'             => 'required|email|max:255',
            'image'             => 'required',
        ];

        // Tutor-specific fields
        if (Auth::user()->role == 'tutor') {
            $rules['intro_video'] = $isProfileVideoMendatory ? 'required' : 'nullable';
            $rules['tagline'] = 'required|string|min:20|max:255';
            $rules['keywords'] = 'nullable|string|max:255';
            
            // Social media validation
            $socialPlatforms = setting('_social.platforms');
            if (!empty($socialPlatforms) && is_array($socialPlatforms)) {
                $rules['social_profiles'] = 'nullable|array';
                foreach ($socialPlatforms as $profile) {
                    if ($profile == 'WhatsApp') {
                        $rules["social_profiles.{$profile}"] = 'nullable|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/';
                    } else {
                        $rules["social_profiles.{$profile}"] = 'nullable|url|max:255';
                    }
                }
            }
        }

        // Address validation
        if ($enableGooglePlaces != '1') {
            $rules['country'] = 'required|numeric';
            $rules['city'] = 'required|string|max:255';
            $rules['zipcode'] = 'required|regex:/^[A-Za-z0-9\s\-]{3,10}$/';
        } else {
            $rules['address'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'required'      => __('validation.required_field'),
            'email'         => __('validation.invalid_email'),
            'zipcode.regex' => __('general.invalid_zipcode'),
        ];

        // Dynamic social media error messages
        $socialPlatforms = setting('_social.platforms');
        if (!empty($socialPlatforms) && is_array($socialPlatforms)) {
            foreach ($socialPlatforms as $platform) {
                if ($platform == 'WhatsApp') {  
                    $messages["social_profiles.{$platform}.regex"] = 
                        __('validation.valid_phone_number', ['attribute' => $platform]);
                } else {
                    $messages["social_profiles.{$platform}.url"] = 
                        __('validation.valid_url', ['attribute' => $platform]);
                }
            }
        }

        return $messages;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name'      => sanitizeTextField($this->first_name),
            'last_name'       => sanitizeTextField($this->last_name),
            'native_language' => sanitizeTextField($this->native_language),
            'description'     => sanitizeTextField($this->description, keep_linebreak: true),
            'city'            => sanitizeTextField($this->city),
            'address'         => sanitizeTextField($this->address),
        ]);
    }
}
```

**Complex Validation Features**:
1. **Role-Based Rules**: Different requirements for tutor vs student
2. **Setting-Driven**: Phone, video, keywords optional based on admin config
3. **Dynamic Social Profiles**: Loops through enabled platforms
4. **Address Strategy**: Google Places API or manual country/city/zipcode
5. **Sanitization**: All text fields cleaned before validation

### 2.6 Review Store Request

**File**: `app/Http/Requests/Student/Booking/ReviewStoreRequest.php`

**Usage**: Student submitting rating and review

**Implementation**:
```php
<?php

namespace App\Http\Requests\Student\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest {
    
    public function rules() {
        return [
            'rating'  => 'required|integer|in:1,2,3,4,5',
            'comment' => 'required|string|max:16777215'
        ];
    }

    public function messages() {
        return [
            'rating'   => __('validation.required_field'),
            'required' => __('validation.required_field'),
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'comment' => sanitizeTextField($this->comment),
        ]);
    }
}
```

**Simple Validation**:
- **rating**: Must be integer 1-5 (star rating)
- **comment**: Text field (MySQL TEXT column max 16MB)
- **Sanitization**: Prevents XSS in review comments

---

## 3. Validation Rules Reference

### 3.1 Common Validation Rules

**Laravel Built-in Rules Used**:

| Rule | Usage | Example |
|------|-------|---------|
| `required` | Field must be present | `'email' => 'required'` |
| `string` | Must be string type | `'name' => 'string'` |
| `email` | Valid email format | `'email' => 'email'` |
| `unique:{table},{column}` | Database uniqueness | `'email' => 'unique:users,email'` |
| `confirmed` | Matches field_confirmation | `'password' => 'confirmed'` |
| `min:{value}` | Minimum length/value | `'name' => 'min:3'` |
| `max:{value}` | Maximum length/value | `'name' => 'max:255'` |
| `integer` | Must be integer | `'rating' => 'integer'` |
| `numeric` | Must be numeric | `'price' => 'numeric'` |
| `in:{values}` | Must be in list | `'role' => 'in:tutor,student'` |
| `array` | Must be array | `'subjects' => 'array'` |
| `url` | Valid URL format | `'website' => 'url'` |
| `regex:{pattern}` | Matches pattern | `'phone' => 'regex:/^[0-9]+$/'` |
| `after:{date}` | Date after value | `'end_time' => 'after:start_time'` |
| `nullable` | Can be null | `'middle_name' => 'nullable'` |
| `sometimes` | Validate if present | `'bio' => 'sometimes'` |
| `exists:{table},{column}` | Foreign key exists | `'country_id' => 'exists:countries,id'` |
| `gt:{field}` | Greater than | `'spaces' => 'gt:0'` |
| `lowercase` | Convert to lowercase | `'email' => 'lowercase'` |
| `mimes:{types}` | File MIME types | `'image' => 'mimes:jpg,png'` |

### 3.2 Custom Regex Patterns

**Phone Number Validation**:
```php
'phone_number' => 'regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/'
```
- Optional country code: `\+?\(?\d{1,4}\)?`
- 7-15 digits with optional spaces/hyphens
- Examples: `+1 (555) 123-4567`, `555-1234`

**Zipcode Validation**:
```php
'zipcode' => 'regex:/^[A-Za-z0-9\s\-]{3,10}$/'
```
- 3-10 characters
- Alphanumeric with spaces and hyphens
- Examples: `12345`, `SW1A 1AA`, `K1A-0B1`

### 3.3 Password Validation

**Laravel 11 Password Rules**:
```php
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'string', 'confirmed', Password::defaults()]
```

**Default Password Rules** (configurable):
- Minimum 8 characters
- Can add: uppercase, lowercase, numbers, symbols
- No compromised password check

**Custom Password Rules** (if needed):
```php
Password::min(8)
    ->letters()
    ->mixedCase()
    ->numbers()
    ->symbols()
    ->uncompromised()
```

### 3.4 File Upload Validation

**Image Validation**:
```php
'image' => [
    'required',
    'mimes:' . $this->imageFileExt,  // jpg,jpeg,png,gif
    'max:' . $this->imageFileSize * 1024  // MB to KB conversion
]
```

**Video Validation**:
```php
'introVideo' => [
    'required',
    'mimes:' . implode(',', $this->allowVideoFileExt),  // mp4,avi,mov
    'max:' . $this->allowVideoSize * 1024  // e.g., 20MB
]
```

**Document Validation**:
```php
'certificate' => [
    'required',
    'mimes:pdf,doc,docx',
    'max:5120'  // 5MB in KB
]
```

### 3.5 Conditional Validation

**Based on Settings**:
```php
$isPhoneRequired = setting('_lernen.phone_number_on_signup') == 'yes';

return [
    'phone_number' => $isPhoneRequired 
        ? 'required|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/' 
        : 'nullable|regex:/^(\+?\(?\d{1,4}\)?)?[\d\s\-]{7,15}$/',
];
```

**Based on Payment System**:
```php
'session_fee' => isPaidSystem() ? 'required|numeric' : 'nullable'
```

**Based on User Role**:
```php
if (Auth::user()->role == 'tutor') {
    $rules['intro_video'] = 'required';
    $rules['tagline'] = 'required|string|min:20|max:255';
}
```

**Based on Google Places API**:
```php
if ($enableGooglePlaces != '1') {
    $rules['country'] = 'required|numeric';
    $rules['city'] = 'required|string';
} else {
    $rules['address'] = 'required|string';
}
```

---

## 4. Custom Validation Logic

### 4.1 Rule Objects (Not Used)

**Note**: This project does NOT use custom Rule classes in `app/Rules/`.

**Validation handled via**:
1. Form Request `rules()` methods
2. Livewire component `$this->validate()` calls
3. Inline validation in controllers

### 4.2 Custom Error Messages

**Translation Files**:
- `lang/en/validation.php` - Validation messages
- `lang/en/general.php` - General error messages
- `lang/en/app.php` - Application-specific messages

**Validation Message File** (`lang/en/validation.php`):
```php
<?php

return [
    'active_url'         => 'The :attribute field must be a valid URL.',
    'valid_url'          => 'The :attribute URL must be a valid URL.',
    'valid_phone_number' => 'The :attribute number must be a valid phone number.',
    'email'              => 'The :attribute field must be a valid email address.',
    'invalid_phone'      => 'Invalid phone number, please add 11 digits phone number',
    'required'           => 'The :attribute field is required.',
    'required_field'     => 'This field is required.',
    'invalid_email'      => 'Please enter valid email',
    'invalid_file_type'  => 'The file must be a type of :file_types',
    'max_file_size_err'  => 'The file size should not be greater than :file_size MB',
    'time_range_error'   => 'End time must after start time',
    'file_too_large'     => 'File failed to upload as maximum allowed file size is :max',
];
```

**Usage in Form Requests**:
```php
public function messages() {
    return [
        'required'      => __('validation.required_field'),
        'email'         => __('validation.invalid_email'),
        'end_time'      => __('validation.time_range_error')
    ];
}
```

### 4.3 Attribute Name Translation

**Custom Attribute Names**:
```php
public function attributes() {
    return [
        'first_name'    => __('general.first_name'),
        'last_name'     => __('general.last_name'),
        'category_ids'  => __('general.categories'),
    ];
}
```

**Example Validation Error**:
- Without attribute: "The first_name field is required."
- With attribute: "The First Name field is required."

### 4.4 Business Logic Validation

**Unique Validation with Context**:
```php
// Ensure subject not already added for this tutor in this group
'subject_id' => [
    'required',
    Rule::unique('user_subject_group_subjects')
        ->where('user_id', auth()->id())
        ->where('subject_group_id', $this->subject_group_id)
]
```

**Date Range Validation**:
```php
'start_date' => [
    'required',
    'date',
    'after_or_equal:today'
],
'end_date' => [
    'required',
    'date',
    'after:start_date'
]
```

**Slot Availability Validation** (in Controller/Livewire):
```php
// Check if slot already booked
$existingBooking = SlotBooking::where('user_subject_slot_id', $slotId)
    ->where('booking_date', $date)
    ->whereIn('status', ['active', 'pending'])
    ->count();

if ($existingBooking >= $slot->spaces) {
    throw ValidationException::withMessages([
        'slot' => 'This time slot is fully booked.'
    ]);
}
```

---

## 5. Livewire Validation

### 5.1 Real-Time Validation

**Livewire Component Example**:
```php
class CreateBlog extends Component
{
    public $title;
    public $description;
    public $category_ids = [];
    public $image;

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'category_ids'  => 'required|array|min:1',
            'category_ids.*'=> 'required|exists:blog_categories,id',
            'image'         => 'required|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function store()
    {
        $validatedData = $this->validate();
        
        // Create blog with validated data
        Blog::create($validatedData);
    }
}
```

**Blade Template**:
```blade
<div>
    <input type="text" wire:model.blur="title" 
           class="form-control @error('title') is-invalid @enderror">
    @error('title')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>
```

**How It Works**:
1. `wire:model.blur="title"` - Syncs input on blur
2. `updated($propertyName)` - Triggered on property change
3. `$this->validateOnly($propertyName)` - Validates single field
4. `@error('title')` - Displays validation error

### 5.2 Livewire Validation Patterns

**Pattern 1: Inline Validation**:
```php
public function updateProfile()
{
    $this->validate([
        'first_name' => 'required|min:3|max:150',
        'email' => 'required|email',
    ]);
}
```

**Pattern 2: Rules Method**:
```php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
    ];
}

public function save()
{
    $data = $this->validate();
}
```

**Pattern 3: Form Request Integration**:
```php
use App\Http\Requests\Common\PersonalDetail\PersonalDetailRequest;

public function updateProfile()
{
    $request = new PersonalDetailRequest();
    $rules = $request->rules();
    $data = $this->validate($rules);
}
```

### 5.3 Custom Error Messages in Livewire

**Method 1: messages() Method**:
```php
protected function messages()
{
    return [
        'title.required' => __('validation.required_field'),
        'email.email' => __('validation.invalid_email'),
    ];
}
```

**Method 2: Inline in validate()**:
```php
$this->validate($rules, [
    'required' => __('validation.required_field'),
], [
    'category_ids' => 'categories'
]);
```

### 5.4 Dynamic Validation in Livewire

**Example: Blog Creation**:
```php
public function store()
{
    $rules = $this->rules();
    $this->beforeValidation(['image', 'category_ids']);
    
    $validatedData = $this->validate($rules, [], [
        'category_ids' => 'categories'
    ]);

    // Handle file upload
    if ($this->image) {
        $randomString = Str::random(30);
        $this->image->storeAs('blogs', 
            $randomString . '.' . $this->image->getClientOriginalExtension(), 
            getStorageDisk()
        );
        $validatedData['image'] = 'blogs/' . $randomString . '.' . 
            $this->image->getClientOriginalExtension();
    }

    Blog::create($validatedData);
}
```

---

## 6. Client-Side Validation

### 6.1 Blade Directive Validation

**Error Display Pattern**:
```blade
<div class="form-group">
    <label>{{ __('general.email') }}</label>
    <input type="email" 
           wire:model.defer="email" 
           class="form-control @error('email') is-invalid @enderror">
    @error('email')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
```

**Multiple Field Errors**:
```blade
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

### 6.2 Alpine.js Validation (Not Heavily Used)

**Example Pattern** (if implemented):
```html
<div x-data="{ 
    email: '', 
    emailError: '' 
}" x-init="
    $watch('email', value => {
        if (!value.includes('@')) {
            emailError = 'Please enter valid email';
        } else {
            emailError = '';
        }
    })
">
    <input type="email" x-model="email">
    <span x-show="emailError" x-text="emailError" class="text-danger"></span>
</div>
```

**Note**: Project primarily relies on server-side validation with Livewire

### 6.3 HTML5 Validation Attributes

**Common HTML5 Attributes**:
```blade
<input type="email" required maxlength="255">
<input type="number" min="1" max="10">
<input type="tel" pattern="[0-9]{10}">
<textarea required minlength="20" maxlength="500"></textarea>
```

**Purpose**: Basic client-side checks before submission

---

## 7. Error Handling & Display

### 7.1 API Error Response

**BaseFormRequest Trait**:
```php
protected function validationError(?string $message = null, $errors = null, int $code = 422)
{
    $error_list = collect($errors->toArray())->mapWithKeys(function ($messages, $field) {
        return [$field => $messages[0]];  // First error only
    });

    return response()->json([
        'status' => $code,
        'message' => $message,
        'errors' => $error_list
    ], $code);
}
```

**Example Error Response**:
```json
{
    "status": 422,
    "message": "Validation errors",
    "errors": {
        "email": "The email field is required.",
        "password": "The password must be at least 8 characters.",
        "user_role": "The user_role field must be tutor or student."
    }
}
```

### 7.2 Livewire Error Display

**Component Input Error**:
```blade
<x-input-error :field_name="'email'" />
```

**Component Definition** (`resources/views/components/input-error.blade.php`):
```blade
@props(['field_name'])

@error($field_name)
    <span class="text-danger">{{ $message }}</span>
@enderror
```

**Usage Example**:
```blade
<input type="text" wire:model="first_name" class="form-control">
<x-input-error :field_name="'first_name'" />
```

### 7.3 Flash Messages

**Success Message**:
```php
// Livewire
$this->dispatch('showAlertMessage', 
    type: 'success',
    title: __('general.success'),
    message: __('general.profile_updated')
);
```

**Error Message**:
```php
// Livewire
$this->dispatch('showAlertMessage', 
    type: 'error',
    title: __('general.error'),
    message: __('general.something_went_wrong')
);
```

**Validation Error Message**:
```php
// Controller
return redirect()->back()
    ->withErrors($validator)
    ->withInput();
```

### 7.4 Error Logging

**Validation Failure Logging** (if needed):
```php
use Illuminate\Support\Facades\Log;

public function failedValidation(Validator $validator)
{
    Log::warning('Validation failed', [
        'user_id' => auth()->id(),
        'errors' => $validator->errors()->toArray(),
        'input' => $this->except(['password', 'password_confirmation'])
    ]);
    
    parent::failedValidation($validator);
}
```

---

## 8. Sanitization & Input Cleaning

### 8.1 Sanitization Helper

**Function**: `sanitizeTextField($string, $keep_linebreak = false)`

**File**: `app/Helpers/helpers.php`

**Implementation**:
```php
function sanitizeTextField($string, $keep_linebreak = false)
{
    if (is_object($string) || is_array($string)) {
        return '';
    }

    $string = (string) $string;
    $filtered = checkValidUTF8($string);

    // Decode HTML entities
    $filtered = html_entity_decode($filtered, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Strip HTML tags
    if (strpos($filtered, '<') !== false) {
        $filtered = stripAllTags($filtered, false);
        $filtered = str_replace("<\n", "&lt;\n", $filtered);
    }

    // Remove line breaks (optional)
    if (!$keep_linebreak) {
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
    }
    $filtered = trim($filtered);

    // Remove percent-encoded characters
    $found = false;
    while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
        $filtered = str_replace($match[0], '', $filtered);
        $found = true;
    }

    if ($found) {
        $filtered = trim(preg_replace('/ +/', ' ', $filtered));
    }

    // HTML Purifier
    $filtered = clean($filtered, ['Attr.EnableID' => true]);

    return $filtered;
}
```

**Features**:
1. **UTF-8 Validation**: Ensures valid character encoding
2. **HTML Entity Decoding**: Prevents double-encoding
3. **Tag Stripping**: Removes HTML tags
4. **Line Break Handling**: Optional preservation for descriptions
5. **URL Encoding Removal**: Strips percent-encoded chars
6. **HTML Purifier**: Final XSS protection

### 8.2 prepareForValidation Hook

**Usage in Form Requests**:
```php
protected function prepareForValidation(): void
{
    $this->merge([
        'first_name'   => sanitizeTextField($this->first_name),
        'last_name'    => sanitizeTextField($this->last_name),
        'description'  => sanitizeTextField($this->description, keep_linebreak: true),
        'comment'      => sanitizeTextField($this->comment),
    ]);
}
```

**Execution Order**:
1. Request received
2. `prepareForValidation()` runs (sanitizes input)
3. `rules()` applied to sanitized input
4. Validation passes/fails
5. Controller receives clean data

### 8.3 Array Sanitization

**Function**: `SanitizeArray(&$arr)`

**Implementation**:
```php
function SanitizeArray(&$arr)
{
    foreach ($arr as $key => &$el) {
        if (is_array($el)) {
            SanitizeArray($el);  // Recursive
        } else {
            $el = sanitizeTextField($el, true);
        }
    }
    return $arr;
}
```

**Usage**:
```php
$userInput = [
    'subjects' => ['<script>Math</script>', 'Science', 'English'],
    'keywords' => ['<b>tutoring</b>', 'online', 'expert']
];

SanitizeArray($userInput);

// Result:
// [
//     'subjects' => ['Math', 'Science', 'English'],
//     'keywords' => ['tutoring', 'online', 'expert']
// ]
```

### 8.4 Sanitization Best Practices

**✅ DO**:
- Sanitize all user input before validation
- Use `keep_linebreak: true` for text areas
- Apply sanitization in `prepareForValidation()`
- Sanitize nested arrays with `SanitizeArray()`

**❌ DON'T**:
- Rely only on client-side sanitization
- Skip sanitization for "trusted" users
- Sanitize passwords (validate format only)
- Strip HTML from rich text editors (use HTML Purifier instead)

---

## Summary

**Validation Architecture**:
- **31+ Form Request Classes**: Organized by role and feature
- **BaseFormRequest**: Centralized API error handling
- **Livewire Integration**: Real-time validation with wire:model.blur
- **Dynamic Rules**: Conditional based on settings and user role

**Key Validation Patterns**:
1. **prepareForValidation()**: Sanitize before validation
2. **rules()**: Define validation rules
3. **messages()**: Custom error messages
4. **attributes()**: Friendly field names

**Sanitization Strategy**:
- **sanitizeTextField()**: XSS prevention, HTML stripping
- **HTML Purifier Integration**: Final safety layer
- **UTF-8 Validation**: Character encoding security
- **prepareForValidation Hook**: Clean data before rules apply

**Error Handling**:
- **API**: 422 status with structured JSON errors
- **Web**: Livewire error display with @error directive
- **Logging**: Optional validation failure logging
- **Flash Messages**: Success/error notifications

**Best Practices Implemented**:
✅ Server-side validation required
✅ Input sanitization before validation
✅ Conditional validation based on settings
✅ Role-specific validation rules
✅ Translated error messages
✅ API standardized error responses
✅ File upload validation (size, type)
✅ Business logic validation (time ranges, uniqueness)

**Security Features**:
🔒 XSS prevention via sanitization
🔒 SQL injection prevention (Laravel ORM)
🔒 CSRF protection (web routes)
🔒 Rate limiting (API routes)
🔒 Password strength validation
🔒 File type validation
🔒 Input length limits
