# PHASE 11: FRONTEND ARCHITECTURE

## Table of Contents
1. [Frontend Stack Overview](#frontend-stack-overview)
2. [Livewire Components](#livewire-components)
3. [Alpine.js Integration](#alpinejs-integration)
4. [Blade Components](#blade-components)
5. [Asset Pipeline](#asset-pipeline)
6. [JavaScript Architecture](#javascript-architecture)
7. [CSS & Styling](#css--styling)
8. [Layout System](#layout-system)
9. [Real-time Features](#real-time-features)

---

## 1. Frontend Stack Overview

### 1.1 Technology Stack

**Core Technologies**:
- **Livewire 3.5** - Full-stack reactive framework
- **Alpine.js 3.x** - Lightweight JavaScript framework (included with Livewire)
- **Tailwind CSS 3.1** - Utility-first CSS framework
- **jQuery 3.7.1** - Legacy JavaScript library (gradual migration)
- **Vite 5.0** - Modern build tool and dev server
- **Bootstrap 5.x** - CSS framework (admin/legacy sections)

**Additional Libraries**:
- **Select2** - Enhanced select dropdowns
- **Chart.js** - Data visualization
- **Croppie** - Image cropping
- **Flatpickr** - Date/time picker
- **Splide** - Carousel/slider
- **VenoBox** - Lightbox
- **Video.js** - Video player
- **Summernote** - WYSIWYG editor
- **AOS** - Animate on scroll
- **Sortable.js** - Drag and drop sorting

### 1.2 Architecture Pattern

**TALL Stack** (modified):
- **T**ailwind CSS
- **A**lpine.js
- **L**aravel
- **L**ivewire

**Hybrid Approach**:
- Livewire for dynamic server-rendered components
- Alpine.js for client-side interactivity
- jQuery for legacy features (gradually being replaced)
- Vanilla JavaScript for modern features

**Component-First Architecture**:
```
Frontend
├── Livewire Components (78+ files)
│   ├── Pages/ (Admin, Student, Tutor, Common)
│   ├── Forms/ (Form objects with validation)
│   ├── Components/ (Reusable UI components)
│   └── Frontend/ (Public-facing pages)
├── Blade Components (100+ files)
│   ├── Layouts (app, admin-app, frontend-app, guest)
│   ├── UI Components (buttons, modals, cards)
│   └── Frontend Components (headers, menus, footers)
└── Alpine.js Directives (inline x-data components)
```

---

## 2. Livewire Components

### 2.1 Livewire Configuration

**File**: `config/livewire.php`

```php
return [
    'class_namespace' => 'App\\Livewire',
    'view_path' => resource_path('views/livewire'),
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => null,
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['max:500000'], // 500MB max
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'jpg', 'jpeg'],
        'max_upload_time' => 30, // minutes
        'cleanup' => true,
    ],
    'render_on_redirect' => false,
];
```

### 2.2 Component Structure

**Directory Organization**:
```
app/Livewire/
├── Actions/
│   └── Logout.php
├── Components/
│   ├── Courses.php
│   ├── SearchTutor.php (main tutor search)
│   ├── SimilarTutors.php
│   ├── StudentsReviews.php
│   ├── TutorResume.php
│   └── TutorSessions.php
├── Forms/
│   ├── Frontend/OrderForm.php
│   └── ...
├── Frontend/
│   ├── BlogDetails.php
│   ├── Blogs.php
│   ├── Checkout.php
│   └── ThankYou.php
├── Pages/
│   ├── Admin/ (35+ components)
│   ├── Common/ (12+ components)
│   ├── Student/ (15+ components)
│   └── Tutor/ (16+ components)
├── ExperiencedTutors.php
└── Payouts.php
```

### 2.3 Example Components

**SearchTutor Component**:

**File**: `app/Livewire/Components/SearchTutor.php`

```php
<?php

namespace App\Livewire\Components;

use App\Services\SiteService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class SearchTutor extends Component
{
    use WithPagination;

    public $filters;
    public $isLoadPage = false;
    protected $paginationTheme = 'bootstrap';
    public $allowFavAction = false;
    public $repeatItems = 10;
    
    private $siteService;
    private $userService;

    public function boot(SiteService $siteService) {
        $this->siteService = $siteService;
        $user = Auth::user();
        $this->userService = new UserService($user);
    }

    // Lazy loading placeholder
    public function placeholder()
    {
        $repeatItems = $this->filters['per_page'] ?? 10;
        return view('skeletons.tutor-fullpage-list', compact('repeatItems'));
    }

    public function render()
    {
        $favouriteTutors = [];
        $tutors = [];
        
        if($this->isLoadPage){
            $tutors = $this->siteService->getTutors($this->filters);
            
            if ($this->allowFavAction){
                $favouriteTutors = $this->userService
                    ->getFavouriteUsers()
                    ->pluck('favourite_user_id')
                    ->toArray();
            }
        }
        
        $this->dispatch('initVideoJs');
        return view('livewire.components.search-tutor', compact('tutors', 'favouriteTutors'));
    }

    public function loadPage()
    {
        $this->isLoadPage = true;
    }

    public function mount($filters = [])
    {
        $this->filters = $filters;
        
        if(Auth::user()?->role == 'student'){
            $this->allowFavAction = true;
        }
    }

    #[On('tutorFilters')]
    public function applyFilter($filters)
    {
        $this->resetPage();
        $this->filters = $filters;
    }

    public function updatingPage()
    {
        $this->dispatch('initVideoJs', timeout: 1000);
    }

    #[Renderless]
    public function toggleFavourite($userId)
    {
        if ($this->allowFavAction){
            $isFavourite = $this->userService->isFavouriteUser($userId);
            
            if($isFavourite){
                $this->userService->removeFromFavourite($userId);
            } else {
                $this->userService->addToFavourite($userId);
            }
            
            $this->dispatch('toggleFavIcon', userId: $userId);
        }
    }
}
```

**Key Livewire Features Used**:
1. **Lazy Loading**: `#[Lazy]` attribute with placeholder
2. **WithPagination**: Built-in pagination trait
3. **Event Listeners**: `#[On('eventName')]` attribute
4. **Renderless Methods**: `#[Renderless]` for non-rendering actions
5. **Service Injection**: Dependency injection in boot()
6. **Reactive Properties**: Public properties auto-sync with frontend

**Checkout Component**:

**File**: `app/Livewire/Frontend/Checkout.php` (396 lines)

```php
<?php

namespace App\Livewire\Frontend;

use Modules\LaraPayease\Facades\PaymentDriver;
use App\Livewire\Forms\Frontend\OrderForm;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Checkout extends Component
{
    public OrderForm $form;
    
    public $methods = [];
    public $countries = [];
    public $walletBalance = '';
    public $useWalletBalance = false;
    public $available_payment_methods = [];
    
    private ?OrderService $orderService = null;
    private ?WalletService $walletService = null;
    private ?BillingService $billingService = null;

    public function boot()
    {
        $this->orderService = new OrderService();
        $this->walletService = new WalletService();
        $this->billingService = new BillingService(Auth::user());
    }

    public function mount()
    {
        $this->dispatch('initSelect2', target: '.am-select2');
        
        // Load payment gateways
        $gateways = PaymentDriver::supportedGateways();
        $this->methods = array_merge($this->methods, $gateways);
        
        // Get wallet balance
        $this->walletBalance = $this->walletService->getWalletAmount(Auth::user()->id);
        
        // Load billing details
        $this->billingDetail = $this->billingService->getBillingDetail();
        
        // Populate form
        $this->form->setInfo($this->billingDetail);
        
        $this->prepareCartAmount();
        $this->getAvailablePaymentMethods();
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        $this->form->walletBalance = $this->walletBalance;
        $this->form->useWalletBalance = $this->useWalletBalance;
        
        return view('livewire.frontend.checkout');
    }
    
    // ... more methods
}
```

**Form Objects**:

**File**: `app/Livewire/Forms/Frontend/OrderForm.php`

```php
<?php

namespace App\Livewire\Forms\Frontend;

use Livewire\Attributes\Validate;
use Livewire\Form;

class OrderForm extends Form
{
    #[Validate('required')]
    public $firstName = '';

    #[Validate('required')]
    public $lastName = '';

    #[Validate('required|email')]
    public $email = '';

    #[Validate('required')]
    public $phone = '';

    #[Validate('required')]
    public $countryId = '';

    #[Validate('required')]
    public $city = '';

    #[Validate('required')]
    public $paymentMethod = '';

    public $walletBalance = 0;
    public $useWalletBalance = false;

    public function setInfo($data)
    {
        $this->firstName = $data->first_name ?? '';
        $this->lastName = $data->last_name ?? '';
        $this->email = $data->email ?? '';
        $this->phone = $data->phone ?? '';
    }
}
```

### 2.4 Livewire Page Components

**Admin Dashboard Structure**:
```
app/Livewire/Pages/Admin/
├── Blogs/
│   ├── Blogs.php
│   ├── CreateBlog.php
│   ├── UpdateBlog.php
│   └── BlogCategories.php
├── Bookings/
│   └── Bookings.php
├── Dispute/
│   ├── Dispute.php
│   └── ManageDispute.php
├── EmailTemplates/
│   └── EmailTemplates.php
├── IdentityVerification/
│   └── IdentityVerification.php
├── Insights/
│   └── Insights.php
├── Invoices/
│   └── Invoices.php
├── LanguageTranslator/
│   └── LanguageTranslator.php
├── ManageAdminUsers/
│   └── ManageAdminUsers.php
├── Menu/
│   └── ManageMenu.php
├── NotificationTemplates/
│   └── NotificationTemplates.php
├── Packages/
│   ├── InstalledPackages.php
│   └── ManagePackages.php
├── Payments/
│   ├── CommissionSettings.php
│   ├── PaymentMethods.php
│   └── WithdrawRequest.php
├── Profile/
│   └── AdminProfile.php
├── Reviews/
│   └── Reviews.php
├── Taxonomy/
│   ├── Languages.php
│   ├── SubjectGroups.php
│   └── Subjects.php
├── Upgrade/
│   └── Upgrade.php
└── Users/
    └── Users.php
```

**Common Components** (Shared between roles):
```
app/Livewire/Pages/Common/
├── Bookings/
│   └── UserBooking.php
├── Dispute/
│   ├── Dispute.php
│   └── ManageDispute.php
├── ProfileSettings/
│   ├── AccountSettings.php
│   ├── IdentityVerification.php
│   ├── PersonalDetails.php
│   ├── Resume.php
│   └── Resume/
│       ├── Certificate.php
│       ├── Education.php
│       └── Experience.php
├── Navigation.php
└── Notifications.php
```

### 2.5 Livewire Events & Communication

**Event Dispatching**:
```php
// Component to component
$this->dispatch('eventName', param1: $value1);

// Component to JavaScript
$this->dispatch('showAlertMessage', 
    type: 'success', 
    title: 'Success',
    message: 'Operation completed'
);

// JavaScript to Component
Livewire.dispatch('tutorFilters', { filters: filterData });
```

**Event Listeners**:
```php
#[On('reload-balances')]
public function reload() {
    // Refresh wallet balance
}

#[On('cart-updated')]
public function updateCart($cartData) {
    // Update cart display
}
```

**Browser Events** (JavaScript):
```javascript
// Listen to Livewire events
Livewire.on('remove-cart', (event) => {
    const { index, cartable_id, cartable_type } = event.params;
    // Handle cart removal
});

// Dispatch custom events
window.dispatchEvent(new CustomEvent('cart-updated', {
    detail: {
        cart_data: data.cart_data,
        total: data.total
    }
}));
```

---

## 3. Alpine.js Integration

### 3.1 Alpine.js Usage Patterns

**Inline Components** (x-data):

**Cart Management**:
```html
<div
    class="am-orderwrap"
    x-data="{
        showCart: false,
        cartData: @js(App\Facades\Cart::content()),
        total: @js(formatAmount(App\Facades\Cart::total(), true)),
        subTotal: @js(formatAmount(App\Facades\Cart::subtotal(), true)),
        discount: @js(formatAmount(App\Facades\Cart::discount(), true)),
        
        removeItem(index, cartable_id, cartable_type){
            this.cartData.splice(index, 1);
            jQuery('.am-ordersummary').addClass('am-bookcartopen');
            Livewire.dispatch('remove-cart', { 
                params: {index, cartable_id, cartable_type}
            });
        }
    }"
    x-on:cart-updated.window="
        cartData = $event.detail.cart_data;
        total = $event.detail.total;
        subTotal = $event.detail.subTotal;
        discount = $event.detail.discount;
        jQuery('.am-ordersummary').addClass('am-bookcartopen');
    ">
    <a href="javascript:void(0);" class="am-header_user_noti cart-bag">
        <template x-if="cartData.length > 0">
            <em x-text="cartData.length"></em>
        </template>
        <i class="am-icon-shopping-basket-04"></i>
    </a>
    
    <div class="am-ordersummary" :class="{'am-emptyorder': cartData.length == 0}">
        <template x-if="cartData.length > 0">
            <div class="am-ordersummary_title">
                <h3>Order Summary</h3>
                <a href="javascript:void(0);" 
                   class="am-ordersummary_close" 
                   @click="jQuery('.am-ordersummary').removeClass('am-bookcartopen');">
                    <i class="am-icon-multiply-02"></i>
                </a>
            </div>
        </template>
        
        <ul class="am-ordersummary_list">
            <template x-for="(item, index) in cartData">
                <li>
                    <div class="am-ordersummary_list_title">
                        <h3><a href="#" x-text="item.name"></a></h3>
                        <span x-text="item.price"></span>
                    </div>
                    <a href="javascript:void(0);" 
                       @click.prevent="removeItem(index, item.cartable_id, item.cartable_type)">
                        Remove
                    </a>
                </li>
            </template>
        </ul>
    </div>
</div>
```

**Notification Counter**:
```html
<div x-data="{ 
    userNotificationCount: @js(auth()->user()->unreadNotifications()->count()) 
}">
    <a href="{{ route('student.notifications') }}">
        <template x-if="userNotificationCount > 0">
            <em x-text="userNotificationCount"></em>
        </template>
        <i class="am-icon-notification"></i>
    </a>
</div>
```

**Copy to Clipboard**:
```html
<div x-data="{ 
    linkToCopy: '{{ route('session-detail', ['id' => encrypt($booking->slot->id)]) }}', 
    linkCopied: false 
}">
    <button class="am-white-btn" 
            @click="
                navigator.clipboard.writeText(linkToCopy);
                linkCopied = true;
                setTimeout(() => linkCopied = false, 2000);
            ">
        <template x-if="!linkCopied">
            <span>Copy Link</span>
        </template>
        <template x-if="linkCopied">
            <span>Copied!</span>
        </template>
    </button>
</div>
```

**Delete Confirmation**:
```html
<a href="javascript:;" 
   @click="$wire.dispatch('showConfirm', { 
       id: {{ $subject->id }}, 
       action: 'delete-subject',
       title: '{{ __('general.delete_subject') }}'
   })">
    <i class="icon-trash-2"></i>
</a>
```

### 3.2 Alpine.js Directives

**Common Directives Used**:

| Directive | Usage | Example |
|-----------|-------|---------|
| `x-data` | Initialize component state | `x-data="{ open: false }"` |
| `x-show` | Toggle visibility (display) | `x-show="open"` |
| `x-if` | Conditional rendering (DOM removal) | `<template x-if="cartData.length > 0">` |
| `x-for` | Loop rendering | `<template x-for="item in items">` |
| `x-text` | Set text content | `x-text="item.name"` |
| `x-html` | Set HTML content | `x-html="item.description"` |
| `@click` | Click event listener | `@click="open = !open"` |
| `@submit` | Form submit listener | `@submit.prevent="submitForm()"` |
| `:class` | Dynamic classes | `:class="{'active': isActive}"` |
| `x-on:eventname.window` | Listen to window events | `x-on:cart-updated.window="..."` |

### 3.3 Alpine + Livewire Integration

**Best Practices**:
1. **Use `$wire`** for Livewire property/method access from Alpine
2. **Use `@this`** for Livewire component instance
3. **Use `$dispatch`** for Livewire events from Alpine

**Examples**:
```html
<!-- Access Livewire property -->
<div x-data="{ count: $wire.entangle('count') }">
    <span x-text="count"></span>
</div>

<!-- Call Livewire method -->
<button @click="$wire.incrementCounter()">
    Increment
</button>

<!-- Dispatch Livewire event -->
<button @click="$wire.dispatch('refresh-data')">
    Refresh
</button>

<!-- Open Livewire modal -->
<button @click="$nextTick(() => 
    $wire.dispatch('toggleModel', {id:'review-modal', action:'show'})
)">
    Open Modal
</button>
```

---

## 4. Blade Components

### 4.1 Component Organization

**Blade Component Structure**:
```
resources/views/components/
├── frontend/
│   ├── user-menu.blade.php (cart, notifications)
│   ├── header.blade.php
│   ├── footer.blade.php
│   └── ... (50+ components)
├── admin/
│   ├── sidebar.blade.php
│   ├── topbar.blade.php
│   └── ... (30+ components)
├── booking-detail-modal.blade.php
├── single-booking.blade.php
├── favicon.blade.php
├── multi-currency.blade.php
├── multi-lingual.blade.php
├── open_ai.blade.php (AI writer integration)
├── popups.blade.php
└── ... (100+ total components)
```

### 4.2 Layout Components

**Main Layouts**:

**1. App Layout** (`resources/views/layouts/app.blade.php`):
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(setting('_general.enable_rtl')) dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $siteTitle }} {{ $title ?? '' }}</title>
    
    <x-favicon />
    
    @vite([
        'public/css/bootstrap.min.css',
        'public/css/fonts.css',
        'public/css/icomoon/style.css',
        'public/css/select2.min.css',
    ])
    
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    
    @stack('styles')
    
    @if(setting('_general.enable_rtl'))
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif
    
    @livewire('livewire-ui-spotlight')
    @livewireStyles()
</head>
<body class="font-sans antialiased"
      x-data="{ isDragging: false }"
      x-on:dragover.prevent="isDragging = true"
      x-on:drop="isDragging = false">
    
    <div class="am-dashboardwrap">
        <livewire:pages.common.navigation />
        
        <div class="am-mainwrap">
            <livewire:header.header />
            
            <main class="am-main">
                <div class="am-dashboard_box">
                    <div class="am-dashboard_box_wrap">
                        @yield('content')
                        {{ $slot ?? '' }}
                        
                        @if(setting('_api.active_conference') == 'google_meet' 
                            && empty(isCalendarConnected(Auth::user())))
                            <div class="am-connect_google_calendar">
                                <h4>Connect Google Calendar</h4>
                                <a href="{{ route(auth()->user()->role.'.profile.account-settings') }}">
                                    Connect
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
        
        @if(session('impersonated_name'))
            <div class="am-impersonation-bar">
                <span>Impersonating <strong>{{ session('impersonated_name') }}</strong></span>
                <a href="{{ route('exit-impersonate') }}">Exit</a>
            </div>
        @endif
    </div>
    
    <x-popups />
    
    @livewireScripts()
    
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script defer src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script defer src="{{ asset('js/select2.min.js') }}"></script>
    <script defer src="{{ asset('js/main.js') }}"></script>
    
    @stack('scripts')
    
    @if(showAIWriter())
        <x-open_ai />
    @endif
</body>
</html>
```

**2. Admin App Layout** (`resources/views/layouts/admin-app.blade.php`):
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <!-- Similar head structure -->
    @vite([
        'public/admin/css/themify-icons.css',
        'public/admin/css/feather-icons.css',
        'public/admin/css/fontawesome/all.min.css',
        'public/admin/css/main.css',
    ])
    @livewireStyles
</head>
<body>
    <livewire:admin.sidebar />
    
    <main class="tb-main">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
    
    @livewireScripts
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            Livewire.dispatch('showAlertMessage', {
                type: 'success',
                message: 'Action completed'
            });
        });
    </script>
</body>
</html>
```

**3. Frontend App Layout** (`resources/views/layouts/frontend-app.blade.php`):
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @vite([
        'public/css/bootstrap.min.css',
        'public/css/main.css',
        'public/css/splide.min.css',
        'public/css/flatpicker.css',
        'public/css/aos.min.css',
    ])
</head>
<body>
    @include('frontend.header')
    
    <main>
        @yield('content')
    </main>
    
    @include('frontend.footer')
    
    @livewireScripts()
    
    <script>
        Livewire.on('remove-cart', (event) => {
            // Handle cart removal
        });
    </script>
</body>
</html>
```

**4. Guest Layout** (`resources/views/layouts/guest.blade.php`):
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <!-- Minimal head for guest pages -->
    @livewireStyles()
    @livewireScripts()
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### 4.3 Reusable Blade Components

**Popups Component** (`resources/views/components/popups.blade.php`):
```blade
<!-- Success/Error Messages -->
<div id="alertMessage" class="am-alert" style="display:none;">
    <div class="am-alert-content">
        <span id="alertIcon"></span>
        <div class="am-alert-text">
            <h4 id="alertTitle"></h4>
            <p id="alertText"></p>
        </div>
    </div>
</div>

<!-- Confirmation Dialog -->
<div id="confirmDialog" class="am-modal" style="display:none;">
    <div class="am-modal-content">
        <h3 id="confirmTitle"></h3>
        <p id="confirmMessage"></p>
        <div class="am-modal-actions">
            <button id="confirmYes">Yes</button>
            <button id="confirmNo">No</button>
        </div>
    </div>
</div>
```

**Favicon Component** (`resources/views/components/favicon.blade.php`):
```blade
@php
    $favicon = setting('_general.site_favicon');
    $faviconUrl = $favicon 
        ? url(Storage::url($favicon[0]['path'])) 
        : asset('images/favicon.ico');
@endphp
<link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
```

**Multi-Currency Component** (`resources/views/components/multi-currency.blade.php`):
```blade
<div class="am-currency-selector">
    <select id="currencySelect" onchange="changeCurrency(this.value)">
        @foreach(availableCurrencies() as $currency)
            <option value="{{ $currency->code }}" 
                    {{ session('currency') == $currency->code ? 'selected' : '' }}>
                {{ $currency->symbol }} {{ $currency->code }}
            </option>
        @endforeach
    </select>
</div>
```

### 4.4 Component Props & Slots

**Component with Props**:
```blade
{{-- Usage --}}
<x-booking-detail-modal :booking="$currentBooking" :show="true" />

{{-- Component definition --}}
@props(['booking', 'show' => false])

<div class="modal" @if($show) style="display:block;" @endif>
    <h3>{{ $booking->subject }}</h3>
    <p>{{ $booking->formatted_date }}</p>
</div>
```

**Component with Named Slots**:
```blade
{{-- Usage --}}
<x-card>
    <x-slot:header>
        <h2>Card Title</h2>
    </x-slot>
    
    <x-slot:body>
        <p>Card content goes here</p>
    </x-slot>
    
    <x-slot:footer>
        <button>Action</button>
    </x-slot>
</x-card>

{{-- Component definition --}}
<div class="card">
    <div class="card-header">
        {{ $header }}
    </div>
    <div class="card-body">
        {{ $body }}
    </div>
    <div class="card-footer">
        {{ $footer }}
    </div>
</div>
```

---

## 5. Asset Pipeline

### 5.1 Vite Configuration

**File**: `vite.config.js`

```javascript
import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import collectModuleAssetsPaths from './vite-module-loader.js';

async function getConfig() {
    const paths = [
        'public/css/fonts.css',
        'resources/css/app.css',
        'public/css/select2.min.css',
        'public/css/bootstrap.min.css',
        'public/css/mCustomScrollbar.min.css',
        'public/admin/css/themify-icons.css',
        'public/admin/css/feather-icons.css',
        'public/admin/css/main.css',
        'public/css/fontawesome.min.css',
        'public/css/main.css',
        'public/css/croppie.css',
        'public/summernote/summernote-lite.min.css',
        'public/css/venobox.min.css',
        'public/css/flags.css',
        'public/css/videojs.css',
        'public/css/icomoon/style.css',
        'public/css/splide.min.css',
        'public/css/flatpicker.css',
        'public/css/aos.min.css',
        'public/css/combotree.css',
        
        // Home page variations
        'public/css/colors-variation/home-two.css',
        'public/css/colors-variation/home-three.css',
        'public/css/colors-variation/home-four.css',
        'public/css/colors-variation/home-five.css',
        'public/css/colors-variation/home-six.css',
        'public/css/colors-variation/home-seven.css',
        'public/css/colors-variation/home-nine.css',

        // JavaScript files
        'public/js/video.min.js',
        'public/js/main.js',
        'public/js/jquery.min.js',
        'public/js/select2.min.js',
        'public/js/bootstrap.min.js',
        'public/js/chart.js',
        'public/js/sortable.js',
        'public/js/jquery.nestable.min.js',
    ];
    
    // Collect module assets dynamically
    const allPaths = await collectModuleAssetsPaths(paths, 'Modules');

    return defineConfig({
        plugins: [
            laravel({
                input: allPaths,
                refresh: true,
            })
        ]
    });
}

export default getConfig();
```

**Module Asset Loader** (`vite-module-loader.js`):
```javascript
// Dynamically loads CSS/JS from enabled modules
import fs from 'fs';
import path from 'path';

export default async function collectModuleAssetsPaths(basePaths, modulesDir) {
    const modulePath = path.resolve(modulesDir);
    const modules = fs.readdirSync(modulePath);
    
    const modulePaths = [];
    
    modules.forEach(module => {
        const moduleAssetsPath = path.join(modulePath, module, 'public');
        if (fs.existsSync(moduleAssetsPath)) {
            // Add module CSS
            const cssFiles = fs.readdirSync(path.join(moduleAssetsPath, 'css'));
            cssFiles.forEach(file => {
                modulePaths.push(`Modules/${module}/public/css/${file}`);
            });
            
            // Add module JS
            const jsFiles = fs.readdirSync(path.join(moduleAssetsPath, 'js'));
            jsFiles.forEach(file => {
                modulePaths.push(`Modules/${module}/public/js/${file}`);
            });
        }
    });
    
    return [...basePaths, ...modulePaths];
}
```

### 5.2 Tailwind Configuration

**File**: `tailwind.config.js`

```javascript
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
        screens: {
            'laptop': '992px',
        },
    },

    plugins: [forms],
};
```

**PostCSS Configuration** (`postcss.config.js`):
```javascript
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

**App CSS** (`resources/css/app.css`):
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 5.3 Asset Compilation

**NPM Scripts** (`package.json`):
```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "@tailwindcss/forms": "^0.5.2",
        "autoprefixer": "^10.4.2",
        "axios": "^1.6.4",
        "laravel-vite-plugin": "^1.0",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.1.0",
        "vite": "^5.0"
    },
    "dependencies": {
        "jquery": "^3.7.1",
        "puppeteer": "^23.5.1",
        "puppeteer-core": "^23.5.2"
    }
}
```

**Build Commands**:
```bash
# Development (watch mode)
npm run dev

# Production build
npm run build

# Run all services (Laravel dev script)
composer dev
# Runs: Laravel server + Queue worker + Reverb + Vite
```

---

## 6. JavaScript Architecture

### 6.1 JavaScript Organization

**Main JavaScript File** (`public/js/main.js`):
```javascript
// jQuery initialization
jQuery(document).ready(function($) {
    
    // Initialize Select2
    $('.am-select2').select2({
        placeholder: 'Select an option',
        allowClear: true
    });
    
    // Initialize datepicker
    $('.am-datepicker').flatpickr({
        dateFormat: 'Y-m-d',
        enableTime: false
    });
    
    // Initialize video player
    initVideoJs();
    
    // Cart handling
    handleCartActions();
    
    // Notification polling
    pollNotifications();
});

// Video.js initialization
function initVideoJs(timeout = 0) {
    setTimeout(() => {
        videojs.getAllPlayers().forEach(player => player.dispose());
        
        document.querySelectorAll('.video-js').forEach(element => {
            videojs(element, {
                controls: true,
                autoplay: false,
                preload: 'auto'
            });
        });
    }, timeout);
}

// Cart actions
function handleCartActions() {
    $('.cart-bag').on('click', function() {
        $('.am-ordersummary').toggleClass('am-bookcartopen');
    });
}

// Poll for new notifications
function pollNotifications() {
    if (window.isAuthenticated) {
        setInterval(async () => {
            const response = await fetch('/api/notifications/unread-count');
            const data = await response.json();
            updateNotificationBadge(data.count);
        }, 30000); // Every 30 seconds
    }
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}
```

**Admin JavaScript** (`public/js/admin-app.js`):
```javascript
jQuery(document).ready(function($) {
    
    // Preloader
    jQuery(window).on('load', function() {
        jQuery(".preloader").delay(1500).fadeOut();
        jQuery(".preloader__bar").delay(1000).fadeOut("slow");
    });
    
    // Sidebar toggle
    if(jQuery(window).width() >= 320){
        jQuery('.tb-btnmenutoggle a').on('click', function() {
            var _this = jQuery(this);
            jQuery('body').toggleClass('tb-sidebar-active');
        });
    }
    
    // Nested menu
    jQuery('.tb-navbar ul li.menu-item-has-children').prepend(
        '<span class="tk-dropdowarrow"><i class="icon-chevron-right"></i></span>'
    );
    
    jQuery('.tb-navbar ul li.menu-item-has-children span').on('click', function() {
        jQuery(this).parent('li').toggleClass('tk-open');
        jQuery(this).next().next().slideToggle(300);
    });
    
    // Chart initialization
    initializeCharts();
});

function initializeCharts() {
    // Revenue chart
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: chartOptions
        });
    }
}
```

### 6.2 Livewire JavaScript Integration

**Global Livewire Events**:
```javascript
// Listen to all Livewire requests
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
        console.log('Livewire request:', uri);
    });
});

// Component initialized
document.addEventListener('livewire:initialized', () => {
    console.log('Livewire initialized');
    initThirdPartyLibraries();
});

// After component updates
document.addEventListener('livewire:update', () => {
    console.log('Livewire updated');
    reinitializePlugins();
});
```

**Custom Livewire Events**:
```javascript
// Show alert message
Livewire.on('showAlertMessage', (event) => {
    const { type, title, message } = event;
    showAlert(type, title, message);
});

// Toggle modal
Livewire.on('toggleModel', (event) => {
    const { id, action } = event;
    const modal = document.getElementById(id);
    
    if (action === 'show') {
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
});

// Show confirmation
Livewire.on('showConfirm', (event) => {
    const { id, action, title, content } = event;
    
    if (confirm(title)) {
        Livewire.dispatch(action, { id });
    }
});
```

### 6.3 Third-Party Library Integration

**Select2 Initialization**:
```javascript
Livewire.on('initSelect2', (event) => {
    const target = event.target || '.am-select2';
    
    jQuery(target).select2({
        placeholder: 'Select an option',
        allowClear: true,
        width: '100%'
    });
    
    // Sync with Livewire
    jQuery(target).on('change', function() {
        @this.set(jQuery(this).attr('wire:model'), jQuery(this).val());
    });
});
```

**Flatpickr Initialization**:
```javascript
document.addEventListener('livewire:init', () => {
    Livewire.hook('element.updated', (el, component) => {
        if (el.classList.contains('flatpickr-input')) {
            flatpickr(el, {
                dateFormat: 'Y-m-d',
                enableTime: false,
                onChange: function(selectedDates, dateStr) {
                    @this.set(el.getAttribute('wire:model'), dateStr);
                }
            });
        }
    });
});
```

**Summernote WYSIWYG**:
```javascript
jQuery('.summernote').summernote({
    height: 300,
    toolbar: [
        ['style', ['bold', 'italic', 'underline']],
        ['para', ['ul', 'ol']],
        ['insert', ['link', 'picture']],
    ],
    callbacks: {
        onChange: function(contents) {
            @this.set('content', contents);
        }
    }
});
```

---

## 7. CSS & Styling

### 7.1 CSS Architecture

**CSS File Organization**:
```
public/css/
├── main.css (primary styles - 15,000+ lines)
├── rtl.css (right-to-left support)
├── fonts.css (web fonts)
├── bootstrap.min.css
├── fontawesome.min.css
├── select2.min.css
├── croppie.css
├── venobox.min.css
├── flags.css
├── videojs.css
├── splide.min.css
├── flatpicker.css
├── aos.min.css (animate on scroll)
├── combotree.css
├── colors-variation/
│   ├── home-two.css
│   ├── home-three.css
│   ├── home-four.css
│   └── ... (9 variations)
└── icomoon/
    └── style.css (icon font)
```

### 7.2 Styling Conventions

**BEM-like Naming**:
```css
/* Block */
.am-dashboard { }

/* Block__Element */
.am-dashboard__header { }
.am-dashboard__content { }

/* Block__Element--Modifier */
.am-dashboard__header--fixed { }

/* Common prefixes:
 * am- = AmentoTech (main namespace)
 * tb- = Table/Toolbar
 * tk- = Theme Kit
 */
```

**Utility Classes**:
```css
/* Spacing */
.am-mt-10 { margin-top: 10px; }
.am-mb-20 { margin-bottom: 20px; }
.am-p-15 { padding: 15px; }

/* Display */
.am-flex { display: flex; }
.am-block { display: block; }
.am-hidden { display: none; }

/* Width */
.am-w-full { width: 100%; }
.am-w-50 { width: 50%; }

/* Colors */
.am-text-primary { color: #007bff; }
.am-bg-light { background-color: #f8f9fa; }
```

### 7.3 Responsive Design

**Breakpoints**:
```css
/* Mobile first approach */

/* Small devices (phones) */
@media (min-width: 576px) { }

/* Medium devices (tablets) */
@media (min-width: 768px) { }

/* Large devices (desktops) */
@media (min-width: 992px) { }

/* Extra large devices */
@media (min-width: 1200px) { }
```

**Responsive Utilities**:
```css
/* Hide on mobile */
@media (max-width: 767px) {
    .am-hide-mobile { display: none; }
}

/* Show only on mobile */
.am-show-mobile { display: none; }
@media (max-width: 767px) {
    .am-show-mobile { display: block; }
}
```

### 7.4 RTL Support

**RTL CSS** (`public/css/rtl.css`):
```css
[dir="rtl"] body {
    direction: rtl;
    text-align: right;
}

[dir="rtl"] .am-header {
    flex-direction: row-reverse;
}

[dir="rtl"] .am-sidebar {
    right: 0;
    left: auto;
}

[dir="rtl"] .am-ml-10 {
    margin-right: 10px;
    margin-left: 0;
}
```

**Dynamic RTL Loading**:
```blade
@if(setting('_general.enable_rtl') || session()->get('rtl'))
    <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
@endif
```

---

## 8. Layout System

### 8.1 Layout Hierarchy

```
Layouts/
├── app.blade.php (Main dashboard layout)
│   ├── Navigation Component (Livewire)
│   ├── Header Component (Livewire)
│   └── Main Content Area
│       └── @yield('content') or {{ $slot }}
│
├── admin-app.blade.php (Admin panel layout)
│   ├── Admin Sidebar (Livewire)
│   ├── Admin Topbar
│   └── Admin Content Area
│
├── frontend-app.blade.php (Public pages)
│   ├── Frontend Header
│   ├── Public Content
│   └── Frontend Footer
│
└── guest.blade.php (Minimal - auth pages)
    └── Guest Content
```

### 8.2 Nested Layouts

**Extending Layouts**:
```blade
{{-- Using @extends --}}
@extends('layouts.app')

@section('content')
    <h1>Page Content</h1>
@endsection

{{-- Using #[Layout] attribute in Livewire --}}
#[Layout('layouts.guest')]
public function render()
{
    return view('livewire.frontend.checkout');
}
```

### 8.3 Section Management

**Defining Sections**:
```blade
{{-- Layout: layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    @yield('head')
    @stack('styles')
</head>
<body>
    @yield('content')
    
    @stack('modals')
    @stack('scripts')
</body>
</html>
```

**Using Sections**:
```blade
{{-- Page: resources/views/tutor/dashboard.blade.php --}}
@extends('layouts.app')

@section('head')
    <title>Tutor Dashboard</title>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <h1>Dashboard</h1>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
```

---

## 9. Real-time Features

### 9.1 Laravel Echo Setup

**Echo Configuration** (if implemented):
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});
```

### 9.2 Real-time Notifications

**Listening to Private Channels**:
```javascript
if (window.isAuthenticated) {
    Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            console.log('New notification:', notification);
            
            // Update notification badge
            const badge = document.querySelector('.notification-badge');
            const currentCount = parseInt(badge.textContent) || 0;
            badge.textContent = currentCount + 1;
            
            // Show toast notification
            showToast(notification.title, notification.message);
            
            // Play notification sound
            playNotificationSound();
        });
}
```

### 9.3 LaraGuppy Chat Integration

**Chat Events**:
```javascript
// Listen to thread events
Echo.private(`thread-${threadId}`)
    .listen('MessageSent', (e) => {
        console.log('New message:', e.message);
        appendMessageToChat(e.message);
        playMessageSound();
    })
    .listen('UserTyping', (e) => {
        showTypingIndicator(e.user.name);
    });

// Listen to user events
Echo.private(`events-${userId}`)
    .listen('ThreadCreated', (e) => {
        addThreadToList(e.thread);
    })
    .listen('MessageRead', (e) => {
        markMessageAsRead(e.messageId);
    });
```

### 9.4 Livewire Polling

**Automatic Polling**:
```blade
{{-- Poll every 30 seconds --}}
<div wire:poll.30s="refreshNotifications">
    @foreach($notifications as $notification)
        <x-notification :data="$notification" />
    @endforeach
</div>

{{-- Poll only when visible --}}
<div wire:poll.keep-alive.30s="checkForUpdates">
    Last updated: {{ $lastUpdate }}
</div>
```

**Conditional Polling**:
```php
class Notifications extends Component
{
    public $polling = true;
    
    public function render()
    {
        if (!$this->polling) {
            return view('livewire.notifications');
        }
        
        return view('livewire.notifications')
            ->extends('layouts.app')
            ->section('content');
    }
    
    public function disablePolling()
    {
        $this->polling = false;
    }
}
```

---

## Summary

**Frontend Architecture Highlights**:

**Tech Stack**:
- ✅ Livewire 3.5 for full-stack reactivity
- ✅ Alpine.js for lightweight client-side interactions
- ✅ Tailwind CSS + Bootstrap hybrid approach
- ✅ Vite for modern asset bundling
- ✅ jQuery for legacy compatibility

**Component System**:
- ✅ 78+ Livewire components (Admin, Student, Tutor, Common)
- ✅ 100+ Blade components (layouts, UI, frontend)
- ✅ Form objects for validation
- ✅ Lazy loading with placeholders
- ✅ Event-driven communication

**Asset Pipeline**:
- ✅ Vite with hot module replacement
- ✅ Dynamic module asset loading
- ✅ Tailwind CSS compilation
- ✅ PostCSS with autoprefixer

**JavaScript Architecture**:
- ✅ jQuery for legacy features
- ✅ Alpine.js for reactive UI
- ✅ Livewire for server-rendered interactivity
- ✅ Third-party library integrations (Select2, Flatpickr, Chart.js)

**Styling**:
- ✅ BEM-like naming convention
- ✅ Utility-first with Tailwind
- ✅ Component-scoped styles
- ✅ RTL support
- ✅ Responsive design (mobile-first)

**Real-time Features**:
- ✅ Laravel Echo (configured)
- ✅ LaraGuppy chat integration
- ✅ Livewire polling
- ✅ Browser notifications

**Best Practices**:
✅ Component-first architecture
✅ Separation of concerns (Livewire for logic, Alpine for UI)
✅ Progressive enhancement
✅ Accessibility considerations
✅ Performance optimization (lazy loading, code splitting)
✅ Module-based organization
