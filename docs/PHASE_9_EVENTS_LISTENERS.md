# PHASE 9: EVENTS & LISTENERS

## Table of Contents
1. [Event System Overview](#event-system-overview)
2. [Event-Listener Mappings](#event-listener-mappings)
3. [Model Observers](#model-observers)
4. [Custom Event Listeners](#custom-event-listeners)
5. [Event Broadcasting](#event-broadcasting)
6. [Event Dispatching](#event-dispatching)
7. [Queued Listeners](#queued-listeners)

---

## 1. Event System Overview

### 1.1 Architecture

**Laravel Event System**: Decoupled publish-subscribe pattern

**Event Flow**:
```
1. Event Triggered (dispatch, fire, model lifecycle)
   ↓
2. EventServiceProvider Routes Event
   ↓
3. Listener(s) Execute (synchronous or queued)
   ↓
4. Side Effects (emails, notifications, logs, etc.)
```

**Benefits**:
- ✅ **Decoupling**: Separate concerns (e.g., email sending from user registration)
- ✅ **Reusability**: One event can trigger multiple listeners
- ✅ **Testability**: Easy to test listeners independently
- ✅ **Maintainability**: Clear separation of business logic

### 1.2 Event Types in System

**1. Laravel Built-in Events**:
- `Registered` - User registration completed
- Model events (via Observers): `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`

**2. Third-Party Package Events**:
- `GuppyChatPrivateEvent` - Chat message received (LaraGuppy package)

**3. Custom Wildcard Events**:
- `modules.*.*` - Module management (enable, disable, delete)
- `settings.updated` - Settings changed

**4. Model Observer Events**:
- Profile slug generation
- Blog slug generation
- Blog category operations

### 1.3 Event Registration

**File**: `app/Providers/EventServiceProvider.php`

**Structure**:
```php
protected $listen = [
    EventClass::class => [
        ListenerClass::class,
    ],
];

protected $observers = [
    ModelClass::class => [ObserverClass::class],
];

public function boot()
{
    Event::listen('wildcard.*', WildcardListener::class);
}
```

---

## 2. Event-Listener Mappings

### 2.1 User Registration Event

**Event**: `Illuminate\Auth\Events\Registered`

**Triggered**: After new user account created

**Listener**: `Illuminate\Auth\Listeners\SendEmailVerificationNotification`

**Registration**:
```php
protected $listen = [
    Registered::class => [
        SendEmailVerificationNotification::class,
    ],
];
```

**Purpose**: Sends email verification link to new users

**Flow**:
```
1. User submits registration form
   ↓
2. RegisterService::registerUser() creates user
   ↓
3. event(new Registered($user)) dispatched
   ↓
4. SendEmailVerificationNotification::handle($event)
   ↓
5. Email with verification link sent
```

**Email Template**: `registration` type in `email_templates` table

**Verification Link**: Temporary signed route valid for 24 hours

---

### 2.2 Chat Message Event

**Event**: `Amentotech\LaraGuppy\Events\GuppyChatPrivateEvent`

**Triggered**: When new private message sent

**Listener**: `App\Listeners\MessageReceivedListener`

**Registration**:
```php
protected $listen = [
    GuppyChatPrivateEvent::class => [
        MessageReceivedListener::class
    ]
];
```

**Purpose**: Notify offline users of new messages

**Listener Implementation**:

**File**: `app/Listeners/MessageReceivedListener.php`

```php
<?php

namespace App\Listeners;

use Amentotech\LaraGuppy\Services\ThreadsService;
use App\Jobs\SendDbNotificationJob;
use App\Jobs\SendNotificationJob;

class MessageReceivedListener
{
    public function handle(object $event): void
    {
        if ($event->eventName == 'message-received') {
            // Get all thread participants
            $threadUsers = (new ThreadsService())
                ->getThreadParticipants($event->message->thread_id);
            
            // Notify each participant (except sender)
            foreach($threadUsers as $participant) {
                if ($participant->participantable_id != $event->message->user_id 
                    && empty($participant->participantable->is_online)) {
                    
                    // Queue email notification
                    dispatch(new SendNotificationJob(
                        'newMessage', 
                        $participant->participantable, 
                        [
                            'userName' => $participant->participantable->profile->full_name,
                            'messageSender' => $event->message->messageable->profile->full_name
                        ]
                    ));

                    // Queue database notification
                    dispatch(new SendDbNotificationJob(
                        'newMessage', 
                        $participant->participantable, 
                        [
                            'messageSender' => $event->message->messageable->profile->full_name
                        ]
                    ));
                }
            }
        }
    }
}
```

**Key Features**:
1. **Offline Check**: Only notifies if user is offline (`is_online` check)
2. **Exclude Sender**: Doesn't notify the message sender
3. **Dual Notifications**: Sends both email and database notification
4. **Queued Jobs**: Uses async jobs for performance

**Flow**:
```
1. User A sends message to User B
   ↓
2. LaraGuppy package dispatches GuppyChatPrivateEvent
   ↓
3. MessageReceivedListener::handle() executes
   ↓
4. Check if User B is offline
   ↓
5. Dispatch SendNotificationJob (email)
   ↓
6. Dispatch SendDbNotificationJob (in-app)
   ↓
7. Both jobs queued for background processing
```

---

### 2.3 Module Management Events

**Event Pattern**: `modules.{module_name}.{action}`

**Examples**:
- `modules.subscriptions.enabled`
- `modules.subscriptions.disabled`
- `modules.subscriptions.deleted`
- `modules.coursebundles.enabled`
- `modules.coursebundles.disabled`
- `modules.coursebundles.deleted`

**Listener**: `App\Listeners\ModuleManagementListener`

**Registration** (wildcard):
```php
public function boot()
{
    Event::listen('modules.*.*', ModuleManagementListener::class);
}
```

**Purpose**: Sync addon status and run module-specific setup/teardown

**Listener Implementation**:

**File**: `app/Listeners/ModuleManagementListener.php`

```php
<?php

namespace App\Listeners;

use App\Models\Addon;
use Database\Seeders\SubscriptionsModuleManagementSeeder;
use Database\Seeders\CourseBundlesModuleManagementSeeder;
use Illuminate\Support\Facades\Log;

class ModuleManagementListener
{
    public function handle($event, $data): void
    {
        $module = $data[0] ?? null;
        if (empty($module)) {
            return;
        }

        // Update addon status in database
        $addon = Addon::whereSlug($module->getLowerName())->first();
        if ($addon) {
            if ($event == 'modules.'.$module->getLowerName().'.deleted') {
                $addon->delete();
            } else {
                $addon->update([
                    'status' => $module->isEnabled() ? 'enabled' : 'disabled'
                ]);
            }
        }

        // Subscriptions module enabled
        if ($event == 'modules.subscriptions.enabled' 
            && $module->getName() === 'Subscriptions') {
            Log::info('Subscriptions module enabled');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('enabled');
        } 
        
        // Subscriptions module disabled
        elseif ($event == 'modules.subscriptions.disabled' 
            && $module->getName() === 'Subscriptions') {
            Log::info('Subscriptions module disabled');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('disabled');
        }
        
        // Subscriptions module deleted
        elseif ($event == 'modules.subscriptions.deleted' 
            && $module->getName() === 'Subscriptions') {
            Log::info('Subscriptions module deleted');
            $seeder = new SubscriptionsModuleManagementSeeder();
            $seeder->run('deleted');
        }

        // CourseBundles module enabled
        if ($event == 'modules.coursebundles.enabled' 
            && $module->getName() === 'CourseBundles') {
            $seeder = new CourseBundlesModuleManagementSeeder();
            $seeder->run('enabled');
        } 
        
        // CourseBundles module disabled
        elseif ($event == 'modules.coursebundles.disabled' 
            && $module->getName() === 'CourseBundles') {
            $seeder = new CourseBundlesModuleManagementSeeder();
            $seeder->run('disabled');
        }
        
        // CourseBundles module deleted
        elseif ($event == 'modules.coursebundles.deleted' 
            && $module->getName() === 'CourseBundles') {
            $seeder = new CourseBundlesModuleManagementSeeder();
            $seeder->run('deleted');
        }
    }
}
```

**Responsibilities**:
1. **Database Sync**: Update `addons` table status
2. **Module Setup**: Run seeder when module enabled
3. **Module Teardown**: Clean up data when disabled/deleted
4. **Logging**: Track module state changes

**Example Flow** (Enable Subscriptions):
```
1. Admin enables Subscriptions module in UI
   ↓
2. Nwidart Modules package fires 'modules.subscriptions.enabled'
   ↓
3. ModuleManagementListener::handle() executes
   ↓
4. Update addons table: status = 'enabled'
   ↓
5. Run SubscriptionsModuleManagementSeeder('enabled')
   ↓
6. Seeder creates subscription plans, permissions, menu items
   ↓
7. Log: "Subscriptions module enabled"
```

**Module-Specific Seeders**:
- `SubscriptionsModuleManagementSeeder` - Subscription plans, credits
- `CourseBundlesModuleManagementSeeder` - Bundle configurations

---

### 2.4 Settings Updated Event

**Event Name**: `settings.updated`

**Triggered**: When admin saves settings via OptionBuilder

**Listener**: `App\Listeners\SettingsUpdatedListener`

**Registration**:
```php
public function boot()
{
    Event::listen('settings.updated', SettingsUpdatedListener::class);
}
```

**Purpose**: Auto-enable/disable Subscriptions module based on payment toggle

**Listener Implementation**:

**File**: `app/Listeners/SettingsUpdatedListener.php`

```php
<?php

namespace App\Listeners;

use Nwidart\Modules\Facades\Module;

class SettingsUpdatedListener
{
    public function handle(array $eventData): void
    {
        // Only process _lernen section settings
        if($eventData['section'] == '_lernen') {
            foreach($eventData['data'] as $key => $value) {
                
                // Payment enabled/disabled toggle
                if($key == 'payment_enabled') {
                    if($value == 'yes') {
                        // Enable Subscriptions module
                        if (Module::has('subscriptions') 
                            && Module::isDisabled('subscriptions')) {
                            Module::enable('subscriptions');
                        }
                    } else {
                        // Disable Subscriptions module
                        if (Module::has('subscriptions') 
                            && Module::isEnabled('subscriptions')) {
                            Module::disable('subscriptions');
                        }
                    }
                }
            }
        }
    }
}
```

**Business Logic**:
- When `payment_enabled = 'yes'` → Enable Subscriptions module
- When `payment_enabled = 'no'` → Disable Subscriptions module
- Prevents subscriptions in free-only tutoring mode

**Event Data Structure**:
```php
[
    'section' => '_lernen',
    'data' => [
        'payment_enabled' => 'yes', // or 'no'
        'phone_number_on_signup' => 'yes',
        'start_of_week' => '0',
        // ... other settings
    ]
]
```

**Flow**:
```
1. Admin toggles "Enable Paid System" setting
   ↓
2. OptionBuilder saves settings
   ↓
3. OptionBuilder dispatches Event::dispatch('settings.updated', $eventData)
   ↓
4. SettingsUpdatedListener::handle($eventData) executes
   ↓
5. Check if payment_enabled changed
   ↓
6. Enable/disable Subscriptions module accordingly
   ↓
7. Nwidart fires modules.subscriptions.{action} event
   ↓
8. ModuleManagementListener handles module state change
```

---

## 3. Model Observers

### 3.1 Observer Pattern

**Purpose**: React to Eloquent model lifecycle events

**Registered Observers**:
```php
protected $observers = [
    Profile::class => [ProfileObserver::class],
    Blog::class => [BlogObserver::class],
    BlogCategory::class => [BlogCategoryObserver::class],
];
```

**Observer Lifecycle Events**:
- `creating` - Before model created
- `created` - After model created
- `updating` - Before model updated
- `updated` - After model updated
- `saving` - Before model saved (create or update)
- `saved` - After model saved
- `deleting` - Before model deleted
- `deleted` - After model deleted

---

### 3.2 ProfileObserver

**Model**: `App\Models\Profile`

**Observer**: `App\Observers\ProfileObserver`

**Purpose**: Auto-generate unique slug for tutor/student profiles

**Implementation**:

**File**: `app/Observers/ProfileObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Profile;
use Illuminate\Support\Str;

class ProfileObserver {
    
    /**
     * Handle the Profile "saving" event.
     */
    public function saving(Profile $profile) {
        $slug = Str::slug($profile->first_name . '-' . $profile->last_name);
        $profile->slug = $this->uniqueSlug($slug, $profile->id);
    }
    
    /**
     * Generate unique slug with numeric suffix if needed
     */
    protected function uniqueSlug($slug, $profileId = null, $i = 0) {
        // Check if slug already exists (excluding current profile)
        if (Profile::whereSlug($slug)
            ->where('id', '!=', $profileId)
            ->exists()) {
            
            // Remove old suffix and add new incremented one
            $slug = Str::of($slug)->rtrim('-' . $i) . '-' . ++$i;
            
            // Recursive call to check uniqueness
            return $this->uniqueSlug($slug, $profileId, $i);
        }
        
        return $slug;
    }
}
```

**Slug Generation Logic**:
1. Convert `first_name + last_name` to slug: "John Doe" → "john-doe"
2. Check if slug exists in database
3. If exists, append counter: "john-doe-1", "john-doe-2", etc.
4. Recursively check until unique slug found

**Examples**:
- First John Doe: `john-doe`
- Second John Doe: `john-doe-1`
- Third John Doe: `john-doe-2`

**Triggered On**:
- Profile creation (first save)
- Profile update (name change)

**URL Usage**: `https://yourdomain.com/tutor/john-doe`

---

### 3.3 BlogObserver

**Model**: `App\Models\Blog`

**Observer**: `App\Observers\BlogObserver`

**Purpose**: Auto-generate unique slug for blog posts

**Implementation**:

**File**: `app/Observers/BlogObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Str;

class BlogObserver
{
    /**
     * Handle the Blog "created" event.
     */
    public function created(Blog $blog)
    {
        $slug = Str::slug($blog->title);
        $blog->slug = $this->uniqueSlug($slug, $blog);
        
        // Save again with slug (created event means model already saved)
        $blog->save();
    }

    /**
     * Create unique slug automatically
     */
    protected function uniqueSlug($slug, $blog)
    {
        // Check if slug exists (excluding current blog)
        if (Blog::whereSlug($slug)
            ->whereNot('id', $blog->id)
            ->exists()) {
            
            // Append blog ID to make unique
            $slug = $slug . "-" . $blog->id;
        }
        
        return $slug;
    }
}
```

**Slug Generation Logic**:
1. Convert blog title to slug: "10 Math Tips" → "10-math-tips"
2. Check if slug exists
3. If exists, append blog ID: "10-math-tips-456"
4. Guaranteed unique (ID is unique)

**Examples**:
- First "10 Math Tips": `10-math-tips`
- Second "10 Math Tips": `10-math-tips-457`

**Note**: Uses `created` event (not `saving`) because needs blog ID

---

### 3.4 BlogCategoryObserver

**Model**: `App\Models\BlogCategory`

**Observer**: `App\Observers\BlogCategoryObserver`

**Purpose**: Similar slug generation for blog categories

**Implementation**: Same pattern as BlogObserver

**Example Slugs**:
- "Mathematics" → `mathematics`
- "Test Prep" → `test-prep`
- Duplicate "Mathematics" → `mathematics-5`

---

## 4. Custom Event Listeners

### 4.1 Listener Structure

**All Custom Listeners**:
```
app/Listeners/
├── MessageReceivedListener.php
├── ModuleManagementListener.php
└── SettingsUpdatedListener.php
```

**Common Pattern**:
```php
namespace App\Listeners;

class ListenerName
{
    public function handle($event): void
    {
        // Listener logic
    }
}
```

**Registration Methods**:
1. **Class-based**: `$listen` array
2. **Wildcard**: `Event::listen('pattern.*', Listener::class)`
3. **Closure**: `Event::listen('event', function($event) { ... })`

---

### 4.2 Listener vs Observer

**When to Use Listener**:
- External package events (LaraGuppy)
- Laravel built-in events (Registered)
- Custom application events
- Cross-model operations

**When to Use Observer**:
- Single model lifecycle events
- Data transformation on save
- Slug generation
- Audit logging

**Example Decision**:
- ✅ Observer: Generate profile slug on save
- ✅ Listener: Send welcome email on user registration
- ✅ Listener: Log module enable/disable actions

---

## 5. Event Broadcasting

### 5.1 Broadcasting Configuration

**File**: `config/broadcasting.php`

**Supported Drivers**:
- **Reverb** (Laravel 11 native WebSocket server)
- **Pusher** (Third-party service)
- **Ably** (Third-party service)
- **Redis** (Self-hosted)
- **Log** (Development)
- **Null** (Disabled)

**Current Setup**:
```php
'default' => env('BROADCAST_CONNECTION', 'log'),

'connections' => [
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
    ],
    
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'host' => env('PUSHER_HOST'),
            'port' => env('PUSHER_PORT', 443),
            'scheme' => env('PUSHER_SCHEME', 'https'),
            'encrypted' => true,
            'useTLS' => true,
        ],
    ],
],
```

### 5.2 Broadcast Channels

**File**: `routes/channels.php`

**Private User Channel**:
```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

**Purpose**: Private channel for user-specific notifications

**Authorization**: Only authenticated user can subscribe to their own channel

**Usage**:
- Real-time notification updates
- Live booking status changes
- Instant message alerts

**Frontend Subscription** (if implemented):
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log(notification);
        // Display in-app notification
    });
```

### 5.3 LaraGuppy Chat Channels

**File**: `packages/laraguppy/routes/channels.php`

**Event Channel**:
```php
Broadcast::channel('events-{id}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

**Thread Channel**:
```php
Broadcast::channel('thread-{id}', function ($user, $threadId) {
    // Authorize if user is thread participant
    return true; // Actual logic in package
});
```

**Public Events Channel**:
```php
Broadcast::channel('events', function () {
    return true; // Public channel
});
```

**Purpose**: Real-time chat features

---

## 6. Event Dispatching

### 6.1 Manual Event Dispatching

**Method 1: event() Helper**:
```php
event(new Registered($user));
```

**Method 2: Event Facade**:
```php
use Illuminate\Support\Facades\Event;

Event::dispatch('settings.updated', [
    'section' => '_lernen',
    'data' => $settingsData
]);
```

**Method 3: Dispatchable Trait**:
```php
class CustomEvent
{
    use Dispatchable;
}

CustomEvent::dispatch($data);
```

### 6.2 Event Dispatching Examples

**User Registration** (in `RegisterService::registerUser()`):
```php
$user = User::create($data);

// Automatically dispatches Registered event
event(new Registered($user));
```

**Module Management** (in Nwidart Modules package):
```php
Event::dispatch('modules.subscriptions.enabled', [$module]);
```

**Settings Update** (in OptionBuilder):
```php
Event::dispatch('settings.updated', [
    'section' => $section,
    'data' => $newSettings
]);
```

**Chat Message** (in LaraGuppy package):
```php
GuppyChatPrivateEvent::dispatch([
    'eventName' => 'message-received',
    'message' => $message
]);
```

---

## 7. Queued Listeners

### 7.1 Queue Benefits

**Why Queue Listeners**:
- ✅ Faster response times (non-blocking)
- ✅ Handle slow operations (email sending)
- ✅ Retry failed operations
- ✅ Better scalability

**Current Implementation**: No listeners implement `ShouldQueue`

**Notification Jobs Are Queued**: `SendNotificationJob`, `SendDbNotificationJob`

### 7.2 How to Queue Listeners

**Add ShouldQueue Interface**:
```php
use Illuminate\Contracts\Queue\ShouldQueue;

class MessageReceivedListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(object $event): void
    {
        // Listener logic
    }
}
```

**Configuration**:
```php
public $connection = 'redis'; // Queue connection
public $queue = 'listeners';  // Specific queue
public $delay = 60;           // Delay in seconds
public $tries = 3;            // Retry attempts
```

### 7.3 Why Jobs Instead of Queued Listeners

**Current Pattern**: Listeners dispatch Jobs

**Example**:
```php
class MessageReceivedListener
{
    public function handle(object $event): void
    {
        // Listener executes synchronously
        
        // Dispatch jobs to queue
        dispatch(new SendNotificationJob(...));
        dispatch(new SendDbNotificationJob(...));
    }
}
```

**Benefits**:
1. **Listener stays fast**: Just dispatches jobs, doesn't do work
2. **Job isolation**: Each notification is separate job
3. **Better retry logic**: Jobs have more control than queued listeners
4. **Easier debugging**: Job status visible in queue monitor

---

## Summary

**Event System Architecture**:
- **3 Custom Listeners**: MessageReceived, ModuleManagement, SettingsUpdated
- **3 Model Observers**: Profile, Blog, BlogCategory
- **Built-in Events**: User registration, model lifecycle
- **Package Events**: LaraGuppy chat messages
- **Wildcard Events**: Module management, settings updates

**Key Patterns**:
1. **Observer Pattern**: Model lifecycle automation (slugs)
2. **Listener Pattern**: Cross-cutting concerns (notifications, logging)
3. **Job Dispatching**: Async work from listeners
4. **Event Broadcasting**: Real-time updates (chat, notifications)

**Event-Listener Mappings**:
```
Registered → SendEmailVerificationNotification
GuppyChatPrivateEvent → MessageReceivedListener → [SendNotificationJob, SendDbNotificationJob]
modules.*.* → ModuleManagementListener → Update addons, run seeders
settings.updated → SettingsUpdatedListener → Enable/disable modules
Profile saving → ProfileObserver → Generate unique slug
Blog created → BlogObserver → Generate unique slug
```

**Best Practices Implemented**:
✅ Decoupled architecture (events vs listeners)
✅ Single responsibility (one listener per concern)
✅ Queue-based notifications (non-blocking)
✅ Wildcard listeners (flexible module management)
✅ Model observers (automatic slug generation)
✅ Broadcast channels (real-time updates)
✅ Logging (module state changes)

**Not Implemented** (potential improvements):
- Custom domain events (BookingCompleted, PaymentProcessed)
- Event subscribers (group related listeners)
- Event discovery (auto-registration)
- More queued listeners (for performance)
- Event sourcing (audit trail)
