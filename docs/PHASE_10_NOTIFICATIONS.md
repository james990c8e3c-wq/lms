# PHASE 10: NOTIFICATIONS

## Table of Contents
1. [Notification System Overview](#notification-system-overview)
2. [Notification Architecture](#notification-architecture)
3. [Email Notifications](#email-notifications)
4. [Database Notifications](#database-notifications)
5. [Notification Services](#notification-services)
6. [Notification Templates](#notification-templates)
7. [Notification Jobs](#notification-jobs)
8. [Broadcasting](#broadcasting)
9. [Notification API](#notification-api)

---

## 1. Notification System Overview

### 1.1 Dual Notification System

**Channels**:
1. **Email Notifications** - Sent via mail
2. **Database Notifications** - Stored in `notifications` table

**Flow**:
```
Event Triggered
   ↓
Event Listener
   ↓
Dispatch Notification Jobs (async)
   ├─→ SendNotificationJob (email)
   └─→ SendDbNotificationJob (database)
   ↓
Template Parsing (NotificationService/DbNotificationService)
   ↓
Notification Sent
   ├─→ Email delivered via SMTP
   └─→ Record inserted in notifications table
```

### 1.2 Notification Types

**15+ Email Notification Types**:
1. `registration` - Welcome email with verification link
2. `welcome` - Account created successfully
3. `emailVerification` - Email verification link
4. `passwordResetRequest` - Password reset link
5. `identityVerificationRequest` - Admin alert for identity approval
6. `identityVerificationApproved` - Identity approved
7. `identityVerificationRejected` - Identity rejected
8. `withdrawWalletAmountRequest` - Withdrawal request submitted
9. `bookingRescheduled` - Session rescheduled
10. `bookingLinkGenerated` - Meeting link ready
11. `bookingCompletionRequest` - Complete session reminder
12. `sessionBooking` - New booking created
13. `sessionRequest` - Session request received
14. `renewSubscription` - Subscription renewal reminder
15. `parentIdentityVerification` - Parent identity approval for minors

**12+ Database Notification Types**:
1. `identityVerificationApproved`
2. `identityVerificationRejected`
3. `sessionBooking`
4. `bookingRescheduled`
5. `acceptedWithdrawRequest`
6. `newMessage`
7. `bookingLinkGenerated`
8. `bookingCompletionRequest`
9. `sessionRequest`
10. `disputeResolution`
11. `assignedQuiz`
12. `reviewedQuiz`
13. `generateQuizResult`

### 1.3 Template-Based System

**Storage**: Templates stored in database tables
- `email_templates` - Email notification templates
- `notification_templates` - Database notification templates

**Customization**: Admin can edit templates via UI

**Placeholder System**: Dynamic data replacement
- `{userName}` - User's full name
- `{tutorName}` - Tutor's full name
- `{sessionDate}` - Formatted session date
- `{meetingLink}` - Video call URL
- `{verificationLink}` - Action button URL

**Role-Based Templates**: Different templates for tutor vs student

---

## 2. Notification Architecture

### 2.1 Notification Classes

**Email Notification Class**:

**File**: `app/Notifications/EmailNotification.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Blade;

class EmailNotification extends Notification
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderEmail = getOption('sender_email');
        $senderName = getOption('sender_name');
        
        return (new MailMessage)
            ->from($senderEmail, $senderName)
            ->subject($this->data['subject'])
            ->view('emails.template', [
                'greeting' => $this->data['greeting'] ?? '',
                'content' => $this->data['content'] ?? '',
                'signature' => $this->data['signature'] ?? '',
                'copyright' => $this->data['copyright'] ?? ''
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
```

**Key Features**:
- **Channel**: `['mail']` only
- **From Address**: Dynamic from settings (`sender_email`, `sender_name`)
- **View**: `resources/views/emails/template.blade.php`
- **Data**: Accepts parsed template array from NotificationService

**Database Notification Class**:

**File**: `app/Notifications/DbNotification.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DbNotification extends Notification
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
```

**Key Features**:
- **Channel**: `['database']` only
- **Simple Storage**: Returns data array for database insertion
- **No Processing**: Data already parsed by DbNotificationService

### 2.2 Notification Flow

**Complete Flow**:
```
1. Event Occurs (e.g., new chat message)
   ↓
2. Event Listener (MessageReceivedListener)
   ↓
3. Dispatch Jobs
   ├─→ SendNotificationJob('newMessage', $user, $data)
   └─→ SendDbNotificationJob('newMessage', $user, $data)
   ↓
4. Jobs Execute (queued)
   ├─→ NotificationService::parseEmailTemplate('newMessage', 'tutor', $data)
   └─→ DbNotificationService::dispatch('newMessage', $user, $data)
   ↓
5. Template Parsing
   ├─→ Get template from email_templates table
   └─→ Get template from notification_templates table
   ↓
6. Placeholder Replacement
   ├─→ Replace {messageSender} with actual name
   └─→ Replace {userName} with recipient name
   ↓
7. Send Notification
   ├─→ $user->notify(new EmailNotification($parsedEmail))
   └─→ $user->notify(new DbNotification($parsedNotification))
   ↓
8. Delivery
   ├─→ Email sent via SMTP (Mailgun/SendGrid/etc)
   └─→ Record inserted in notifications table
```

---

## 3. Email Notifications

### 3.1 NotificationService

**File**: `app/Services/NotificationService.php` (548 lines)

**Purpose**: Parse email templates and replace placeholders

**Core Method**:
```php
public function parseEmailTemplate($type, $role, $data)
{
    // Get template from database
    $emailTemplate = EmailTemplate::whereType($type)
        ->whereRole($role)
        ->first();
    
    if (!$emailTemplate) {
        return false;
    }

    // Dynamic method resolution: getRegistrationEmail, getWelcomeEmail, etc.
    $parseFunction = "get" . Str::ucfirst(Str::camel($type)) . "Email";
    
    if (!method_exists($this, $parseFunction)) {
        return false;
    }

    // Call specific parser method
    $templateArray = $this->$parseFunction($emailTemplate->content, $data);
    
    return $templateArray;
}
```

**Template Array Structure**:
```php
[
    'greeting' => 'Hi {userName},',
    'subject' => 'New Message Received',
    'content' => 'You have a new message from {messageSender}...',
    'signature' => 'Best regards,<br>The Team',
    'copyright' => '© 2025 YourApp'
]
```

### 3.2 Email Template Parsers

**1. Registration Email**:
```php
public function getRegistrationEmail($content, $data)
{
    $greeting = str_replace(
        ['{userName}', '{userEmail}'],
        [$data['userName'] ?? '', $data['userEmail'] ?? ''],
        $content['greeting'] ?? ''
    );

    $contentHtml = str_replace(
        ['{userName}', '{userEmail}'],
        [$data['userName'] ?? '', $data['userEmail'] ?? ''],
        $content['content'] ?? ''
    );

    // Generate verification button
    $buttonComponent = '';
    if (!empty($data['verificationLink'])) {
        $buttonComponent = view('components.email.button', [
            'btnText' => 'Verify Email',
            'btnUrl' => $data['verificationLink']
        ])->render();
    }

    $contentHtml = str_replace('{verificationLink}', $buttonComponent, $contentHtml);

    return [
        'greeting' => $greeting,
        'subject' => $content['subject'] ?? 'Welcome!',
        'content' => $contentHtml,
        'signature' => $content['signature'] ?? '',
        'copyright' => $content['copyright'] ?? ''
    ];
}
```

**2. Session Booking Email**:
```php
public function getSessionBookingEmail($content, $data)
{
    $greeting = str_replace(
        ['{tutorName}', '{studentName}'],
        [$data['tutorName'] ?? '', $data['studentName'] ?? ''],
        $content['greeting'] ?? ''
    );

    // Generate booking details component
    $bookingDetailsComponent = view('components.email.booking-details', [
        'sessionType' => $data['sessionType'] ?? '',
        'sessionDate' => $data['sessionDate'] ?? '',
        'sessionTime' => $data['sessionTime'] ?? '',
        'sessionDuration' => $data['sessionDuration'] ?? '',
        'sessionSubject' => $data['sessionSubject'] ?? '',
        'sessionPrice' => $data['sessionPrice'] ?? '',
        'studentName' => $data['studentName'] ?? '',
        'studentEmail' => $data['studentEmail'] ?? ''
    ])->render();

    $contentHtml = str_replace(
        ['{tutorName}', '{studentName}', '{bookingDetails}'],
        [$data['tutorName'] ?? '', $data['studentName'] ?? '', $bookingDetailsComponent],
        $content['content'] ?? ''
    );

    return [
        'greeting' => $greeting,
        'subject' => $content['subject'] ?? 'New Booking',
        'content' => $contentHtml,
        'signature' => $content['signature'] ?? ''
    ];
}
```

**3. Booking Link Generated Email**:
```php
public function getBookingLinkGeneratedEmail($content, $data)
{
    $greeting = str_replace(
        ['{tutorName}', '{userName}'],
        [$data['tutorName'] ?? '', $data['userName'] ?? ''],
        $content['greeting'] ?? ''
    );

    $contentHtml = str_replace(
        ['{tutorName}', '{userName}', '{sessionDate}', '{sessionSubject}'],
        [
            $data['tutorName'] ?? '',
            $data['userName'] ?? '',
            $data['sessionDate'] ?? '',
            $data['sessionSubject'] ?? ''
        ],
        $content['content'] ?? ''
    );

    // Generate meeting link button
    $buttonComponent = '';
    if (!empty($data['meetingLink'])) {
        $buttonComponent = view('components.email.button', [
            'btnText' => 'Join Meeting',
            'btnUrl' => $data['meetingLink']
        ])->render();
    }

    $contentHtml = str_replace('{meetingLink}', $buttonComponent, $contentHtml);

    return [
        'greeting' => $greeting,
        'subject' => $content['subject'] ?? 'Meeting Link Ready',
        'content' => $contentHtml,
        'signature' => $content['signature'] ?? ''
    ];
}
```

**4. Password Reset Email**:
```php
public function getPasswordResetRequestEmail($content, $data)
{
    $greeting = str_replace(
        ['{userName}'],
        [$data['userName'] ?? ''],
        $content['greeting'] ?? ''
    );

    $contentHtml = str_replace(
        ['{userName}'],
        [$data['userName'] ?? ''],
        $content['content'] ?? ''
    );

    // Generate reset button
    $buttonComponent = '';
    if (!empty($data['resetLink'])) {
        $buttonComponent = view('components.email.button', [
            'btnText' => 'Reset Password',
            'btnUrl' => $data['resetLink']
        ])->render();
    }

    $contentHtml = str_replace('{resetLink}', $buttonComponent, $contentHtml);

    return [
        'greeting' => $greeting,
        'subject' => $content['subject'] ?? 'Password Reset',
        'content' => $contentHtml,
        'signature' => $content['signature'] ?? ''
    ];
}
```

**5. Subscription Renewal Email**:
```php
public function getRenewSubscriptionEmail($content, $data)
{
    $greeting = str_replace(
        ['{userName}'],
        [$data['userName'] ?? ''],
        $content['greeting'] ?? ''
    );

    $contentHtml = str_replace(
        ['{userName}', '{subscriptionName}', '{subscriptionExpiry}'],
        [
            $data['userName'] ?? '',
            $data['subscriptionName'] ?? '',
            $data['subscriptionExpiry'] ?? ''
        ],
        $content['content'] ?? ''
    );

    // Generate renewal button
    $buttonComponent = '';
    if (!empty($data['renewalLink'])) {
        $buttonComponent = view('components.email.button', [
            'btnText' => 'Renew Now',
            'btnUrl' => $data['renewalLink']
        ])->render();
    }

    $contentHtml = str_replace('{renewalLink}', $buttonComponent, $contentHtml);

    return [
        'greeting' => $greeting,
        'subject' => $content['subject'] ?? 'Renew Subscription',
        'content' => $contentHtml,
        'signature' => $content['signature'] ?? ''
    ];
}
```

### 3.3 Email Template View

**File**: `resources/views/emails/template.blade.php`

```blade
<x-email.layout>
    <x-slot:logo>
        {{-- Logo component --}}
    </x-slot>

    <x-slot:content>
        {!! $greeting !!}
        <br><br>
        {!! $content !!}
    </x-slot>

    <x-slot:signature>
        {!! nl2br($signature) !!}
    </x-slot>

    <x-slot:copyright>
        {{ $copyright }}
    </x-slot>
</x-email.layout>
```

**Email Layout Component**: `resources/views/components/email/layout.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #f0f0f0;
        }
        .content {
            padding: 30px 0;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        {{ $logo }}
    </div>
    
    <div class="content">
        {{ $content }}
    </div>
    
    <div class="signature">
        {{ $signature }}
    </div>
    
    <div class="footer">
        {{ $copyright }}
    </div>
</body>
</html>
```

**Button Component**: `resources/views/components/email/button.blade.php`

```blade
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center">
            <table cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td>
                        <a href="{{ $btnUrl }}" 
                           style="display: inline-block;
                                  padding: 12px 24px;
                                  background-color: #007bff;
                                  color: #ffffff;
                                  text-decoration: none;
                                  border-radius: 4px;
                                  font-weight: bold;">
                            {{ $btnText }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
```

---

## 4. Database Notifications

### 4.1 DbNotificationService

**File**: `app/Services/DbNotificationService.php` (330 lines)

**Purpose**: Parse database notification templates and send in-app notifications

**Core Methods**:

**1. Send Notification**:
```php
public function send($template, $recipient, $data)
{
    $notification = $this->dispatch($template, $recipient, $data);
    
    if ($notification) {
        $recipient->notify(new DbNotification($notification));
        return true;
    }
    
    return false;
}
```

**2. Dispatch (Parse Template)**:
```php
public function dispatch($type, $recipient, $data)
{
    $role = $recipient->profile->role ?? 'student';
    
    // Get template from database
    $notificationTemplate = NotificationTemplate::whereType($type)
        ->whereRole($role)
        ->first();
    
    if (!$notificationTemplate) {
        return false;
    }

    // Dynamic method resolution
    $parseFunction = "get" . Str::ucfirst(Str::camel($type)) . "Notification";
    
    if (!method_exists($this, $parseFunction)) {
        return false;
    }

    // Call specific parser
    $notificationArray = $this->$parseFunction($notificationTemplate->content, $data);
    
    return $notificationArray;
}
```

**3. Get User Notifications**:
```php
public function getUserNotifications($userId)
{
    return Notification::where('notifiable_id', $userId)
        ->where('notifiable_type', 'App\\Models\\User')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();
}
```

**4. Mark as Read**:
```php
public function markAsRead($notificationId, $userId)
{
    $notification = Notification::where('id', $notificationId)
        ->where('notifiable_id', $userId)
        ->first();
    
    if ($notification) {
        $notification->markAsRead();
        return true;
    }
    
    return false;
}
```

**5. Mark All as Read**:
```php
public function markAllAsRead($userId)
{
    Notification::where('notifiable_id', $userId)
        ->where('notifiable_type', 'App\\Models\\User')
        ->whereNull('read_at')
        ->update(['read_at' => now()]);
    
    return true;
}
```

### 4.2 Database Notification Parsers

**Notification Array Structure**:
```php
[
    'title' => 'New Message',
    'message' => 'You have a message from John Doe',
    'icon' => 'mail',
    'has_link' => true,
    'link_target' => '/messages/123',
    'link_text' => 'View Message'
]
```

**1. Identity Verification Approved**:
```php
public function getIdentityVerificationApprovedNotification($content, $data)
{
    $message = str_replace(
        ['{userName}'],
        [$data['userName'] ?? ''],
        $content['message'] ?? ''
    );

    return [
        'title' => $content['title'] ?? 'Identity Approved',
        'message' => $message,
        'icon' => 'check-circle',
        'has_link' => false
    ];
}
```

**2. Session Booking**:
```php
public function getSessionBookingNotification($content, $data)
{
    $message = str_replace(
        ['{tutorName}', '{studentName}', '{sessionDate}'],
        [
            $data['tutorName'] ?? '',
            $data['studentName'] ?? '',
            $data['sessionDate'] ?? ''
        ],
        $content['message'] ?? ''
    );

    return [
        'title' => $content['title'] ?? 'New Booking',
        'message' => $message,
        'icon' => 'calendar',
        'has_link' => !empty($content['link_target']),
        'link_target' => $content['link_target'] ?? '',
        'link_text' => $content['link_text'] ?? 'View Booking'
    ];
}
```

**3. New Message**:
```php
public function getNewMessageNotification($content, $data)
{
    $message = str_replace(
        ['{messageSender}'],
        [$data['messageSender'] ?? ''],
        $content['message'] ?? ''
    );

    return [
        'title' => $content['title'] ?? 'New Message',
        'message' => $message,
        'icon' => 'mail',
        'has_link' => !empty($data['threadId']),
        'link_target' => '/messages/' . ($data['threadId'] ?? ''),
        'link_text' => 'View Message'
    ];
}
```

**4. Booking Link Generated**:
```php
public function getBookingLinkGeneratedNotification($content, $data)
{
    $message = str_replace(
        ['{tutorName}', '{sessionDate}'],
        [$data['tutorName'] ?? '', $data['sessionDate'] ?? ''],
        $content['message'] ?? ''
    );

    return [
        'title' => $content['title'] ?? 'Meeting Link Ready',
        'message' => $message,
        'icon' => 'video',
        'has_link' => !empty($data['meetingLink']),
        'link_target' => $data['meetingLink'] ?? '',
        'link_text' => 'Join Meeting'
    ];
}
```

**5. Assigned Quiz**:
```php
public function getAssignedQuizNotification($content, $data)
{
    $message = str_replace(
        ['{quizTitle}', '{tutorName}'],
        [$data['quizTitle'] ?? '', $data['tutorName'] ?? ''],
        $content['message'] ?? ''
    );

    return [
        'title' => $content['title'] ?? 'New Quiz Assigned',
        'message' => $message,
        'icon' => 'clipboard',
        'has_link' => !empty($data['quizId']),
        'link_target' => '/quizzes/' . ($data['quizId'] ?? ''),
        'link_text' => 'Take Quiz'
    ];
}
```

### 4.3 Notification Table Schema

**Migration**: `database/migrations/2025_01_23_053539_create_notifications_table.php`

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable');
    $table->text('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

**Fields**:
- `id` - UUID primary key
- `type` - Notification class name (e.g., `App\Notifications\DbNotification`)
- `notifiable_type` - Model class (e.g., `App\Models\User`)
- `notifiable_id` - Model ID (user ID)
- `data` - JSON notification data (title, message, icon, link, etc.)
- `read_at` - Timestamp when notification read (null = unread)
- `created_at`, `updated_at` - Standard timestamps

**Example Record**:
```json
{
    "id": "9d3e4f6a-1234-5678-90ab-cdef12345678",
    "type": "App\\Notifications\\DbNotification",
    "notifiable_type": "App\\Models\\User",
    "notifiable_id": 42,
    "data": {
        "title": "New Message",
        "message": "You have a message from John Doe",
        "icon": "mail",
        "has_link": true,
        "link_target": "/messages/123",
        "link_text": "View Message"
    },
    "read_at": null,
    "created_at": "2025-01-25 10:30:00",
    "updated_at": "2025-01-25 10:30:00"
}
```

### 4.4 DbNotification Facade

**File**: `app/Facades/DbNotification.php`

```php
<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DbNotification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db-notification';
    }
}
```

**Service Registration**: `app/Providers/AppServiceProvider.php`

```php
public function register()
{
    $this->app->bind('db-notification', function($app) {
        return new \App\Services\DbNotificationService();
    });
}
```

**Usage**:
```php
use App\Facades\DbNotification;

DbNotification::send('newMessage', $user, [
    'messageSender' => 'John Doe'
]);
```

---

## 5. Notification Services

### 5.1 NotificationService Methods Summary

**File**: `app/Services/NotificationService.php`

**All Email Template Parsers** (15+ methods):

| Method | Purpose | Placeholders | Button |
|--------|---------|--------------|--------|
| `getRegistrationEmail()` | Welcome new user | {userName}, {userEmail} | {verificationLink} |
| `getWelcomeEmail()` | Account created | {userName}, {userEmail} | - |
| `getEmailVerificationEmail()` | Verify email | - | {verificationLink} |
| `getPasswordResetRequestEmail()` | Reset password | {userName} | {resetLink} |
| `getIdentityVerificationRequestEmail()` | Admin alert | {userName}, {userEmail}, {role} | {approveLink} |
| `getIdentityVerificationApprovedEmail()` | Identity approved | {userName} | - |
| `getIdentityVerificationRejectedEmail()` | Identity rejected | {userName} | - |
| `getWithdrawWalletAmountRequestEmail()` | Withdrawal request | {userName}, {withdrawAmount} | - |
| `getBookingRescheduledEmail()` | Session rescheduled | {tutorName}, {userName}, {reason}, {newSessionDate} | {viewLink} |
| `getBookingLinkGeneratedEmail()` | Meeting link ready | {tutorName}, {userName}, {sessionDate}, {sessionSubject} | {meetingLink} |
| `getBookingCompletionRequestEmail()` | Complete session | {tutorName}, {userName}, {sessionDateTime}, {days} | {completeBookingLink} |
| `getSessionBookingEmail()` | New booking | {tutorName}, {studentName} | {bookingDetails} component |
| `getSessionRequestEmail()` | Session requested | {userName}, {studentName}, {studentEmail}, {sessionType}, {message} | - |
| `getRenewSubscriptionEmail()` | Renew subscription | {userName}, {subscriptionName}, {subscriptionExpiry} | {renewalLink} |
| `getParentIdentityVerificationEmail()` | Parent approval | {parent_name}, {user_details} | {approve_identity_link} |

### 5.2 DbNotificationService Methods Summary

**File**: `app/Services/DbNotificationService.php`

**All Database Notification Parsers** (12+ methods):

| Method | Purpose | Placeholders | Link |
|--------|---------|--------------|------|
| `getIdentityVerificationApprovedNotification()` | Identity approved | {userName} | No |
| `getIdentityVerificationRejectedNotification()` | Identity rejected | {userName} | No |
| `getSessionBookingNotification()` | New booking | {tutorName}, {studentName}, {sessionDate} | Yes - booking page |
| `getBookingRescheduledNotification()` | Session rescheduled | {tutorName}, {newSessionDate}, {reason} | Yes - booking page |
| `getAcceptedWithdrawRequestNotification()` | Withdrawal approved | {withdrawAmount} | No |
| `getNewMessageNotification()` | New message | {messageSender} | Yes - message thread |
| `getBookingLinkGeneratedNotification()` | Meeting link ready | {tutorName}, {sessionDate} | Yes - meeting URL |
| `getBookingCompletionRequestNotification()` | Complete session | {tutorName}, {sessionDateTime} | Yes - completion page |
| `getSessionRequestNotification()` | Session requested | {studentName}, {sessionType} | Yes - request page |
| `getDisputeResolutionNotification()` | Dispute resolved | {disputeResult}, {bookingId} | Yes - booking page |
| `getAssignedQuizNotification()` | Quiz assigned | {quizTitle}, {tutorName} | Yes - quiz page |
| `getReviewedQuizNotification()` | Quiz reviewed | {quizTitle}, {score} | Yes - quiz results |
| `getGenerateQuizResultNotification()` | Quiz completed | {quizTitle}, {score}, {grade} | Yes - results page |

---

## 6. Notification Templates

### 6.1 EmailTemplate Model

**File**: `app/Models/EmailTemplate.php`

```php
<?php

namespace App\Models;

use App\Casts\SerializeCast;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'email_templates';

    protected $fillable = [
        'id',
        'title',
        'type',
        'role',
        'content',
        'status'
    ];

    protected $casts = [
        'content' => SerializeCast::class
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ActiveScope);
    }
}
```

**Key Features**:
- **Soft Deletes**: Templates can be restored
- **Active Scope**: Only active templates loaded by default
- **SerializeCast**: Content stored as JSON, accessed as array
- **Role-Based**: Same type can have different templates per role

**Content Structure** (JSON):
```json
{
    "greeting": "Hi {userName},",
    "subject": "New Message Received",
    "content": "You have a new message from {messageSender}. Click below to view.",
    "signature": "Best regards,\nThe LMS Team",
    "copyright": "© 2025 Your LMS"
}
```

### 6.2 NotificationTemplate Model

**File**: `app/Models/NotificationTemplate.php`

```php
<?php

namespace App\Models;

use App\Casts\SerializeCast;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'id',
        'title',
        'type',
        'role',
        'content',
        'status'
    ];

    protected $casts = [
        'content' => SerializeCast::class
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ActiveScope);
    }
}
```

**Identical Structure**: Same as EmailTemplate

**Content Structure** (JSON):
```json
{
    "title": "New Message",
    "message": "You have a new message from {messageSender}",
    "icon": "mail",
    "link_target": "/messages/{threadId}",
    "link_text": "View Message"
}
```

### 6.3 Template Management

**Admin UI**: `/admin/email-templates`, `/admin/notification-templates`

**Features**:
- **CRUD Operations**: Create, read, update, delete templates
- **Role Selection**: Assign template to tutor or student role
- **Type Selection**: Choose notification type (registration, booking, etc.)
- **Rich Editor**: HTML editor for email content
- **Placeholder Help**: Shows available placeholders per template type
- **Preview**: Preview template with sample data
- **Status Toggle**: Enable/disable templates

**Seeding Default Templates**: `database/seeders/EmailTemplateSeeder.php`

```php
EmailTemplate::create([
    'type' => 'registration',
    'role' => 'student',
    'title' => 'Welcome - Registration Email',
    'status' => 'active',
    'content' => [
        'greeting' => 'Hi {userName},',
        'subject' => 'Welcome to Our Platform!',
        'content' => 'Thank you for registering. Please verify your email: {verificationLink}',
        'signature' => 'Best regards,\nThe Team',
        'copyright' => '© 2025 LMS'
    ]
]);
```

---

## 7. Notification Jobs

### 7.1 SendNotificationJob

**File**: `app/Jobs/SendNotificationJob.php`

```php
<?php

namespace App\Jobs;

use App\Notifications\EmailNotification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $template;
    protected $recipient;
    protected $templateData;

    /**
     * Create a new job instance.
     */
    public function __construct($template, $recipient, $templateData)
    {
        $this->template = $template;
        $this->recipient = $recipient;
        $this->templateData = $templateData;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $role = $this->recipient->profile->role ?? 'student';
        
        $emailTemplate = $notificationService->parseEmailTemplate(
            $this->template,
            $role,
            $this->templateData
        );

        if ($emailTemplate) {
            $this->recipient->notify(new EmailNotification($emailTemplate));
        }
    }
}
```

**Key Features**:
- **Implements ShouldQueue**: Runs asynchronously
- **SerializesModels**: Handles User model serialization
- **Service Injection**: NotificationService injected in handle()
- **Role Detection**: Gets role from recipient's profile
- **Graceful Failure**: No email sent if template not found

**Usage**:
```php
dispatch(new SendNotificationJob(
    'sessionBooking',
    $tutor,
    [
        'tutorName' => $tutor->profile->full_name,
        'studentName' => $student->profile->full_name,
        'sessionDate' => $booking->formatted_date
    ]
));
```

### 7.2 SendDbNotificationJob

**File**: `app/Jobs/SendDbNotificationJob.php`

```php
<?php

namespace App\Jobs;

use App\Facades\DbNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDbNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $template;
    protected $recipient;
    protected $templateData;

    /**
     * Create a new job instance.
     */
    public function __construct($template, $recipient, $templateData)
    {
        $this->template = $template;
        $this->recipient = $recipient;
        $this->templateData = $templateData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DbNotification::dispatch(
            $this->template,
            $this->recipient,
            $this->templateData
        );
    }
}
```

**Key Features**:
- **Simpler than Email Job**: No service injection needed
- **Facade Usage**: Uses DbNotification facade
- **Same Queue**: Runs on same queue as email notifications

**Usage**:
```php
dispatch(new SendDbNotificationJob(
    'newMessage',
    $user,
    [
        'messageSender' => $sender->profile->full_name,
        'threadId' => $thread->id
    ]
));
```

### 7.3 Queue Configuration

**File**: `config/queue.php`

**Default Connection**: `sync` (dev), `redis` (production)

```php
'default' => env('QUEUE_CONNECTION', 'sync'),

'connections' => [
    'sync' => [
        'driver' => 'sync',
    ],

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
```

**Queue Workers**: Run via `php artisan queue:work`

**Retry Strategy**: Failed jobs retry 3 times

**Job Lifecycle**:
```
1. Job dispatched to queue
   ↓
2. Queue worker picks up job
   ↓
3. Job->handle() executes
   ↓
4. Success: Job removed from queue
   OR
   Failure: Job retried or moved to failed_jobs table
```

---

## 8. Broadcasting

### 8.1 Broadcasting Configuration

**File**: `config/broadcasting.php`

**Default Driver**: `log` (development)

**Production Drivers**:
- **Reverb**: Laravel's native WebSocket server (recommended)
- **Pusher**: Third-party WebSocket service

**Reverb Configuration**:
```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 443),
        'scheme' => env('REVERB_SCHEME', 'https'),
        'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
    ],
    'client_options' => [
        // Guzzle options
    ],
],
```

**Environment Variables**:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=abcdefgh
REVERB_APP_SECRET=ijklmnop
REVERB_HOST=reverb.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

### 8.2 Broadcast Channels

**File**: `routes/channels.php`

**Private User Channel**:
```php
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

**Purpose**: User-specific notifications

**Authorization**: User can only listen to their own channel

**Broadcasting Notifications**:
```php
class DbNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'icon' => $this->data['icon'],
        ]);
    }
}
```

**Currently**: Broadcasting not enabled for notifications (only `database` channel)

### 8.3 Frontend Integration (Laravel Echo)

**Installation**:
```bash
npm install --save laravel-echo pusher-js
```

**Configuration**: `resources/js/bootstrap.js`
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**Listening to Notifications**:
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log('New notification:', notification);
        
        // Show toast/alert
        showNotification(notification.title, notification.message);
        
        // Update notification count
        updateNotificationCount();
        
        // Play sound
        playNotificationSound();
    });
```

**Livewire Integration**:
```php
<div wire:poll.30s="refreshNotifications">
    @foreach($notifications as $notification)
        <x-notification :data="$notification" />
    @endforeach
</div>
```

---

## 9. Notification API

### 9.1 Get Notifications

**Endpoint**: `GET /api/notifications`

**Authentication**: Required (Bearer token)

**Query Parameters**:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 10)
- `unread` - Filter unread only (boolean)

**Response**:
```json
{
    "data": [
        {
            "id": "9d3e4f6a-1234-5678-90ab-cdef12345678",
            "type": "App\\Notifications\\DbNotification",
            "data": {
                "title": "New Message",
                "message": "You have a message from John Doe",
                "icon": "mail",
                "has_link": true,
                "link_target": "/messages/123",
                "link_text": "View Message"
            },
            "read_at": null,
            "created_at": "2025-01-25T10:30:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost/api/notifications?page=1",
        "last": "http://localhost/api/notifications?page=3",
        "prev": null,
        "next": "http://localhost/api/notifications?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 10,
        "to": 10,
        "total": 27
    }
}
```

**Controller**: `App\Http\Controllers\Api\NotificationController@index`

```php
public function index(Request $request)
{
    $query = $request->user()->notifications();
    
    if ($request->boolean('unread')) {
        $query->whereNull('read_at');
    }
    
    $notifications = $query->paginate($request->input('per_page', 10));
    
    return response()->json($notifications);
}
```

### 9.2 Mark as Read

**Endpoint**: `POST /api/notifications/{id}/read`

**Authentication**: Required

**Response**:
```json
{
    "success": true,
    "message": "Notification marked as read"
}
```

**Controller**:
```php
public function markAsRead(Request $request, $id)
{
    $notification = $request->user()
        ->notifications()
        ->findOrFail($id);
    
    $notification->markAsRead();
    
    return response()->json([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
}
```

### 9.3 Mark All as Read

**Endpoint**: `POST /api/notifications/read-all`

**Authentication**: Required

**Response**:
```json
{
    "success": true,
    "message": "All notifications marked as read",
    "count": 12
}
```

**Controller**:
```php
public function markAllAsRead(Request $request)
{
    $count = $request->user()
        ->unreadNotifications()
        ->update(['read_at' => now()]);
    
    return response()->json([
        'success' => true,
        'message' => 'All notifications marked as read',
        'count' => $count
    ]);
}
```

### 9.4 Delete Notification

**Endpoint**: `DELETE /api/notifications/{id}`

**Authentication**: Required

**Response**:
```json
{
    "success": true,
    "message": "Notification deleted"
}
```

**Controller**:
```php
public function destroy(Request $request, $id)
{
    $notification = $request->user()
        ->notifications()
        ->findOrFail($id);
    
    $notification->delete();
    
    return response()->json([
        'success' => true,
        'message' => 'Notification deleted'
    ]);
}
```

### 9.5 Get Unread Count

**Endpoint**: `GET /api/notifications/unread-count`

**Authentication**: Required

**Response**:
```json
{
    "count": 5
}
```

**Controller**:
```php
public function unreadCount(Request $request)
{
    $count = $request->user()->unreadNotifications()->count();
    
    return response()->json(['count' => $count]);
}
```

---

## Summary

**Notification System Components**:
1. **2 Notification Classes**: EmailNotification, DbNotification
2. **2 Jobs**: SendNotificationJob, SendDbNotificationJob
3. **2 Services**: NotificationService (15+ parsers), DbNotificationService (12+ parsers)
4. **2 Template Models**: EmailTemplate, NotificationTemplate
5. **1 Facade**: DbNotification
6. **1 API Controller**: NotificationController (5 endpoints)

**Notification Flow**:
```
Event → Listener → Dispatch Jobs → Parse Templates → Send Notifications → Delivery
```

**Template System**:
- ✅ Database-stored templates (customizable by admin)
- ✅ Role-based templates (tutor vs student)
- ✅ Placeholder replacement ({userName}, {tutorName}, etc.)
- ✅ Component-based buttons ({verificationLink}, {meetingLink})
- ✅ Active scope (only active templates used)

**Queue Integration**:
- ✅ All notifications sent asynchronously
- ✅ Jobs implement ShouldQueue
- ✅ Retry logic (3 attempts)
- ✅ Failed job tracking

**Broadcasting** (configured but not actively used):
- ✅ Reverb and Pusher configured
- ✅ Private user channels defined
- ✅ Channel authorization implemented
- ✅ Laravel Echo ready for frontend

**API Endpoints**:
- `GET /api/notifications` - List notifications
- `POST /api/notifications/{id}/read` - Mark as read
- `POST /api/notifications/read-all` - Mark all as read
- `DELETE /api/notifications/{id}` - Delete notification
- `GET /api/notifications/unread-count` - Get unread count

**Best Practices Implemented**:
✅ Dual notification channels (email + database)
✅ Template-based system (flexible, customizable)
✅ Queue-based delivery (non-blocking)
✅ Service layer (business logic separation)
✅ Facade pattern (simplified interface)
✅ API endpoints (frontend integration)
✅ Broadcasting support (real-time updates)

**Not Implemented** (potential improvements):
- SMS notifications (Twilio integration)
- Push notifications (FCM/APNS)
- Notification preferences (user settings)
- Notification batching (digest emails)
- Read receipts (tracking)
- Notification channels per user (granular control)
