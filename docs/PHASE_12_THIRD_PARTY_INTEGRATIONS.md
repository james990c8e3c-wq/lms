# PHASE 12: THIRD-PARTY INTEGRATIONS

## Table of Contents
1. [Integration Overview](#integration-overview)
2. [Google Services](#google-services)
3. [Zoom Integration](#zoom-integration)
4. [Payment Gateways](#payment-gateways)
5. [Chat System (LaraGuppy)](#chat-system-laraguppy)
6. [Broadcasting (Reverb & Pusher)](#broadcasting-reverb--pusher)
7. [AI Services (OpenAI)](#ai-services-openai)
8. [Cloud Storage](#cloud-storage)
9. [Other Integrations](#other-integrations)

---

## 1. Integration Overview

### 1.1 Integrated Services

**Core Integrations**:

| Service | Purpose | Package/SDK | Configuration |
|---------|---------|-------------|---------------|
| **Google Calendar** | Calendar sync, event management | `google/apiclient` | OAuth 2.0 |
| **Google Meet** | Video conferencing | Google Calendar API | OAuth 2.0 |
| **Google Translate** | Language translation | `stichoza/google-translate-php` | API Key |
| **Google OAuth** | Social login | `socialiteproviders/google` | OAuth 2.0 |
| **Zoom** | Video conferencing | Custom service | Server-to-Server OAuth |
| **Stripe** | Payment processing | `stripe/stripe-php` | API Keys |
| **Razorpay** | Payment processing (India) | `razorpay/razorpay` | API Keys |
| **Paytm** | Payment processing (India) | `paytm/paytmchecksum` | Merchant credentials |
| **Iyzico** | Payment processing (Turkey) | `iyzico/iyzipay-php` | API Keys |
| **LaraGuppy** | Real-time chat | Custom package | Reverb |
| **Reverb** | WebSocket server | `laravel/reverb` | Built-in |
| **Pusher** | Broadcasting (alternative) | Pusher SDK | API credentials |
| **OpenAI** | AI content generation | `openai-php/laravel` | API Key |
| **AWS S3** | Cloud storage | `league/flysystem-aws-s3-v3` | IAM credentials |
| **Puppeteer** | PDF generation | `puppeteer` | Chromium |

### 1.2 Integration Architecture

**Service Layer Pattern**:
```
app/Services/
├── GoogleCalender.php (Google Calendar API)
├── ZoomService.php (Zoom API)
├── NotificationService.php (Email services)
├── BookingService.php (Coordinates video conferencing)
├── PaymentService.php (Payment gateway abstraction)
└── ... (35+ service classes)
```

**Module-Based Extensions**:
```
Modules/
├── LaraPayease/ (Payment gateway module)
│   ├── Drivers/
│   │   └── Stripe.php
│   └── Facades/
│       └── PaymentDriver.php
├── MeetFusion/ (Google Meet integration)
└── Subscriptions/ (Subscription management)
```

---

## 2. Google Services

### 2.1 Google Calendar Integration

**Service Class**: `app/Services/GoogleCalender.php` (200+ lines)

**Configuration**:

**File**: `config/services.php`

```php
return [
    'google' => [
        'client_id'         => env('GOOGLE_CLIENT_ID'),
        'client_secret'     => env('GOOGLE_CLIENT_SECRET'),
        'redirect'          => env('GOOGLE_REDIRECT_URI'),
        'redirect_uri'      => env('GOOGLE_CALENDAR_REDIRECT_URI'),
    ],
];
```

**Environment Variables**:
```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
GOOGLE_CALENDAR_REDIRECT_URI=https://yourdomain.com/calendar/oauth/callback
```

**GoogleCalender Service Implementation**:

```php
<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GoogleCalender {

    protected $clientCredentials;
    protected $userAccountSettings = null;
    protected $userService;

    public function __construct($user = null) {
        $this->clientCredentials = [
            'client_id'     => setting('_api.google_client_id'),
            'client_secret' => setting('_api.google_client_secret'),
            'redirect_uri'  => config('services.google.redirect_uri'),
            'scopes'        => [Calendar::CALENDAR]
        ];
        $this->userService = new UserService($user);
        $this->userAccountSettings = $this->userService->getAccountSetting();
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl() {
        try {
            if (empty($this->clientCredentials['client_id']) || 
                empty($this->clientCredentials['client_secret'])) {
                return [
                    'status' => Response::HTTP_BAD_REQUEST, 
                    'message' => __('passwords.keys_missing')
                ];
            }
            
            $client = new Client($this->clientCredentials);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            $auth_url = $client->createAuthUrl();
            
            return ['status' => Response::HTTP_OK, 'url' => $auth_url];
        } catch (Exception $ex) {
            return ['status' => $ex->getCode(), 'message' => $ex->getMessage()];
        }
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessTokenInfo($code) {
        $client = new Client($this->clientCredentials);
        return $client->fetchAccessTokenWithAuthCode($code);
    }

    /**
     * Verify and refresh token if expired
     */
    protected function verifyToken() {
        $isTokenExpired = $this->isTokenExpired(
            $this->userAccountSettings['google_access_token']
        );
        
        if($isTokenExpired){
            $this->userAccountSettings['google_access_token'] = 
                $this->refreshAccessToken(
                    $this->userAccountSettings['google_access_token']['refresh_token']
                );
            
            $this->userService->setAccountSetting(
                'google_access_token',
                $this->userAccountSettings['google_access_token']
            );
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken($refreshToken) {
        $client = new Client($this->clientCredentials);
        return $client->fetchAccessTokenWithRefreshToken($refreshToken);
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired($tokenArray) {
        $client = new Client();
        $client->setAccessToken($tokenArray);
        return $client->isAccessTokenExpired();
    }

    /**
     * Get user's primary calendar
     */
    public function getUserPrimaryCalendar($token) {
        try {
            $client = new Client();
            $client->setAccessToken($token);
            $service = new Calendar($client);
            
            $calendar = $service->calendars->get('primary');
            
            return [
                'status' => Response::HTTP_OK, 
                'data' => [
                    'id'            => $calendar->getId(),
                    'summary'       => $calendar->getSummary(),
                    'description'   => $calendar->getDescription(),
                    'timezone'      => $calendar->getTimeZone(),
                ]
            ];
        } catch (GoogleServiceException $ex) {
            Log::info($ex);
            return ['status' => $ex->getCode(), 'message' => $ex->getMessage()];
        }
    }

    /**
     * Update calendar notification settings
     */
    public function updateCalendarNotificationSettings($minutes) {
        try {
            $this->verifyToken();
            $client = new Client();
            $client->setAccessToken($this->userAccountSettings['google_access_token']);
            $service = new Calendar($client);
            
            $calendars = $service->calendarList->listCalendarList();
            $updatedCalendar = null;
            
            foreach ($calendars as $calendar) {
                if ($calendar->getPrimary()) {
                    if (!empty($minutes)) {
                        $calendar->setDefaultReminders([
                            ['method' => 'email', 'minutes' => $minutes],
                            ['method' => 'popup', 'minutes' => $minutes],
                        ]);
                    } else {
                        $calendar->setDefaultReminders([]);
                    }
                    $updatedCalendar = $service->calendarList->update(
                        $calendar->getId(), 
                        $calendar
                    );
                    break;
                }
            }
            
            return ['status' => Response::HTTP_OK, 'data' => $updatedCalendar];
        } catch (GoogleServiceException $ex) {
            Log::info($ex);
            return ['status' => $ex->getCode(), 'message' => $ex->getMessage()];
        }
    }

    /**
     * Create calendar event
     * 
     * @param array $eventData [
     *      'title',
     *      'description',
     *      'start_time',
     *      'end_time',
     *      'timezone'
     * ]
     */
    public function createEvent($eventData) {
        try {
            if (!empty($this->userAccountSettings['google_calendar_info']['id'])) {
                $this->verifyToken();
                $client = new Client();
                $client->setAccessToken(
                    $this->userAccountSettings['google_access_token']
                );
                $service = new Calendar($client);
                
                $event = new Event([
                    'summary'     => $eventData['title'],
                    'description' => $eventData['description'],
                    'start'  => [
                        'dateTime' => $eventData['start_time'],
                        'timeZone' => $eventData['timezone']
                    ],
                    'end'  => [
                        'dateTime' => $eventData['end_time'],
                        'timeZone' => $eventData['timezone']
                    ]
                ]);
                
                $event = $service->events->insert(
                    $this->userAccountSettings['google_calendar_info']['id'], 
                    $event
                );
                
                return ['status' => Response::HTTP_OK, 'data' => $event];
            }
            
            return [
                'status' => Response::HTTP_BAD_REQUEST, 
                'message' => __('passwords.no_calendar')
            ];
        } catch (Exception $ex) {
            Log::info($ex);
            return ['status' => $ex->getCode(), 'message' => $ex->getMessage()];
        }
    }

    /**
     * Delete calendar event
     */
    public function deleteEvent($eventId) {
        try {
            if (!empty($this->userAccountSettings['google_calendar_info']['id'])) {
                $this->verifyToken();
                $client = new Client();
                $client->setAccessToken(
                    $this->userAccountSettings['google_access_token']
                );
                $service = new Calendar($client);
                
                $service->events->delete(
                    $this->userAccountSettings['google_calendar_info']['id'], 
                    $eventId
                );
                
                return [
                    'status' => Response::HTTP_OK, 
                    'message' => __('passwords.event_deleted')
                ];
            }
            
            return [
                'status' => Response::HTTP_BAD_REQUEST, 
                'message' => __('passwords.no_calendar')
            ];
        } catch (Exception $ex) {
            Log::info($ex);
            return ['status' => $ex->getCode(), 'message' => $ex->getMessage()];
        }
    }
}
```

**Usage in BookingService**:

```php
public function createBookingEventGoogleCalendar($booking)
{
    $eventResponse = (new GoogleCalender($booking->booker))->createEvent([
        'title' => "Session: {$booking->subject}",
        'description' => "Tutor: {$booking->tutor->name}\nStudent: {$booking->student->name}",
        'start_time' => $booking->start_datetime,
        'end_time' => $booking->end_datetime,
        'timezone' => $booking->timezone
    ]);
    
    if ($eventResponse['status'] == Response::HTTP_OK) {
        $booking->update([
            'google_event_id' => $eventResponse['data']['id']
        ]);
    }
    
    return $eventResponse;
}
```

**Job for Async Event Creation**:

```php
<?php

namespace App\Jobs;

use App\Models\SlotBooking;
use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateGoogleCalendarEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $booking;

    public function __construct(SlotBooking $booking)
    {
        $this->booking = $booking;
    }

    public function handle(BookingService $bookingService): void
    {
        $bookingService->createBookingEventGoogleCalendar($this->booking);
    }
}
```

### 2.2 Google Translate Integration

**Package**: `stichoza/google-translate-php`

**Usage Example**:
```php
use Stichoza\GoogleTranslate\GoogleTranslate;

$translator = new GoogleTranslate();
$translator->setSource('en');
$translator->setTarget('es');

$translated = $translator->translate('Hello World');
// Output: "Hola Mundo"
```

**Language Translation Service**:
```php
public function translateContent($text, $fromLang, $toLang)
{
    try {
        $translator = new GoogleTranslate();
        $translator->setSource($fromLang);
        $translator->setTarget($toLang);
        
        return $translator->translate($text);
    } catch (\Exception $e) {
        Log::error('Translation failed: ' . $e->getMessage());
        return $text; // Return original on failure
    }
}
```

### 2.3 Google OAuth (Social Login)

**Package**: `laravel/socialite` + `socialiteproviders/google`

**Configuration**:
```php
// config/services.php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

**Controller Implementation**:
```php
public function redirectToGoogle()
{
    return Socialite::driver('google')
        ->scopes(['email', 'profile'])
        ->redirect();
}

public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->user();
        
        $user = User::updateOrCreate([
            'email' => $googleUser->email,
        ], [
            'name' => $googleUser->name,
            'google_id' => $googleUser->id,
            'avatar' => $googleUser->avatar,
        ]);
        
        Auth::login($user);
        
        return redirect()->route('dashboard');
    } catch (\Exception $e) {
        return redirect()->route('login')->withErrors(['error' => 'Google login failed']);
    }
}
```

---

## 3. Zoom Integration

### 3.1 Zoom Configuration

**Configuration File**: `config/zoom.php`

```php
<?php

return [
    'client_id' => env('ZOOM_CLIENT_KEY'),
    'client_secret' => env('ZOOM_CLIENT_SECRET'),
    'account_id' => env('ZOOM_ACCOUNT_ID'),
    'base_url' => 'https://api.zoom.us/v2/',
];
```

**Environment Variables**:
```env
ZOOM_CLIENT_KEY=your-zoom-client-id
ZOOM_CLIENT_SECRET=your-zoom-client-secret
ZOOM_ACCOUNT_ID=your-zoom-account-id
```

### 3.2 ZoomService Implementation

**File**: `app/Services/ZoomService.php`

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;

class ZoomService {

    protected string $accessToken;
    protected $client;
    protected $account_id;
    protected $client_id;
    protected $client_secret;

    public function __construct()
    {
        $this->client_id = setting('_api.zoom_client_id');
        $this->client_secret = setting('_api.zoom_client_secret');
        $this->account_id = setting('_api.zoom_account_id');

        $this->accessToken = $this->getAccessToken();

        $this->client = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Get OAuth access token using Server-to-Server OAuth
     */
    protected function getAccessToken()
    {
        $client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    $this->client_id . ':' . $this->client_secret
                ),
                'Host' => 'zoom.us',
            ],
        ]);

        $response = $client->request('POST', "https://zoom.us/oauth/token", [
            'form_params' => [
                'grant_type' => 'account_credentials',
                'account_id' => $this->account_id,
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return $responseBody['access_token'];
    }

    /**
     * Create Zoom meeting
     * 
     * @param array $data [
     *     'host_email',
     *     'topic',
     *     'agenda',
     *     'duration',
     *     'timezone',
     *     'start_time',
     *     'schedule_for'
     * ]
     */
    public function createMeeting(array $data)
    {
        try {
            $response = $this->client->request('POST', 'users/me/meetings', [
                'json' => $this->getMeetingData($data),
            ]);
            
            $res = json_decode($response->getBody(), true);
            
            return [
                'status' => true,
                'data' => $res,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    /**
     * Prepare meeting data with default settings
     */
    protected function getMeetingData($params) {
        return array_merge($params, [
            "type"          => 2, // 1=instant, 2=scheduled, 3=recurring no fixed time, 8=recurring fixed time
            "password"      => generatePassword(),
            "settings"      => [
                'join_before_host'  => true,
                'host_video'        => true,
                'participant_video' => true,
                'mute_upon_entry'   => false,
                'waiting_room'      => true,
                'audio'             => 'both', // 'both', 'telephony', 'voip'
                'auto_recording'    => 'none', // 'none', 'local', 'cloud'
                'approval_type'     => 1, // 0=Auto, 1=Manual, 2=No registration
            ]
        ]);
    }

    /**
     * Get meeting details
     */
    public function getMeeting($meetingId)
    {
        try {
            $response = $this->client->request('GET', "meetings/{$meetingId}");
            $data = json_decode($response->getBody(), true);
            
            return [
                'status' => true,
                'data' => $data,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    /**
     * Delete meeting
     */
    public function deleteMeeting($meetingId)
    {
        try {
            $this->client->request('DELETE', "meetings/{$meetingId}");
            
            return [
                'status' => true,
                'message' => 'Meeting deleted successfully',
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    /**
     * Update meeting
     */
    public function updateMeeting($meetingId, array $data)
    {
        try {
            $response = $this->client->request('PATCH', "meetings/{$meetingId}", [
                'json' => $data,
            ]);
            
            return [
                'status' => true,
                'message' => 'Meeting updated successfully',
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
```

### 3.3 Zoom Meeting Creation Flow

**BookingService Integration**:

```php
public function createZoomMeeting($booking)
{
    $zoomService = new ZoomService();
    
    $meetingData = [
        'topic' => "Session: {$booking->subject}",
        'agenda' => "Tutor: {$booking->tutor->name}, Student: {$booking->student->name}",
        'start_time' => $booking->start_datetime,
        'duration' => $booking->duration, // minutes
        'timezone' => $booking->timezone,
        'schedule_for' => $booking->tutor->email,
    ];
    
    $response = $zoomService->createMeeting($meetingData);
    
    if ($response['status']) {
        $booking->update([
            'meeting_id' => $response['data']['id'],
            'meeting_password' => $response['data']['password'],
            'meeting_join_url' => $response['data']['join_url'],
            'meeting_start_url' => $response['data']['start_url'],
        ]);
        
        return $response['data'];
    }
    
    throw new \Exception($response['message']);
}
```

---

## 4. Payment Gateways

### 4.1 LaraPayease Module

**Module Structure**:
```
Modules/LaraPayease/
├── BasePaymentDriver.php
├── Contracts/
├── Drivers/
│   └── Stripe.php
├── Facades/
│   └── PaymentDriver.php
├── Factories/
├── Traits/
│   └── Currency.php
└── config/
    └── larapayease.php
```

**Configuration**: `Modules/LaraPayease/config/larapayease.php`

```php
return [
    'supported_gateways' => [
        'stripe' => [
            'name' => 'Stripe',
            'driver' => \Modules\LaraPayease\Drivers\Stripe::class,
        ],
        'razorpay' => [
            'name' => 'Razorpay',
            'driver' => \Modules\LaraPayease\Drivers\Razorpay::class,
        ],
        'paytm' => [
            'name' => 'Paytm',
            'driver' => \Modules\LaraPayease\Drivers\Paytm::class,
        ],
        'iyzico' => [
            'name' => 'Iyzico',
            'driver' => \Modules\LaraPayease\Drivers\Iyzico::class,
        ],
    ],
];
```

### 4.2 Stripe Integration

**Driver**: `Modules/LaraPayease/Drivers/Stripe.php`

```php
<?php

namespace Modules\LaraPayease\Drivers;

use Modules\LaraPayease\BasePaymentDriver;
use Modules\LaraPayease\Traits\Currency;
use Stripe\Checkout\Session;
use Stripe\Stripe as StripeSdk;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

class Stripe extends BasePaymentDriver
{
    use Currency;

    /**
     * Charge customer (returns checkout view)
     */
    public function chargeCustomer(array $params){
        if(empty($this->getKeys()['stripe_key']) || 
           empty($this->getKeys()['stripe_secret'])){
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => __('Missing Stripe key or secret')
            ];
        }
        
        return view('larapayease::stripe', [
            'stripe_data' => array_merge($params, [
                'stripe_key' => $this->getKeys()['stripe_key'],
                'currency' => $this->getCurrency(),
                'stripe_secret' => base64_encode($this->getKeys()['stripe_secret']),
                'charge_amount' => $this->chargeableAmount($params['amount']),
            ])
        ]);
    }

    public function driverName() : string{
        return 'stripe';
    }

    /**
     * Handle payment response after redirect
     */
    public function paymentResponse(array $params = [])
    {
        $stripeSessionId = session()->get('stripe_session_id');
        session()->forget('stripe_session_id');
        $orderId = session()->get('order_id');
        session()->forget('order_id');

        if (empty($stripeSessionId)) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => __('Missing Session Id')
            ];
        }

        $stripe = new StripeClient($this->getKeys()['stripe_secret']);
        $response = $stripe->checkout->sessions->retrieve($stripeSessionId, []);
        $paymentIntent = $response['payment_intent'] ?? '';
        $paymentStatus = $response['payment_status'] ?? '';

        $capture = $stripe->paymentIntents->retrieve($paymentIntent);
        
        if (!empty($paymentStatus) && 
            $paymentStatus === 'paid' && 
            $capture->status === 'succeeded') {
            $transaction_id = $paymentIntent;
            
            if (!empty($transaction_id)) {
                return [
                    'status' => Response::HTTP_OK,
                    'data'   => [
                        'transaction_id' => $transaction_id,
                        'order_id' => $orderId
                    ]
                ];
            }
        }

        return [
            'status' => Response::HTTP_BAD_REQUEST,
            'order_id' => $orderId
        ];
    }

    /**
     * Prepare Stripe Checkout Session
     */
    public function prepareCharge(array $params){
        StripeSdk::setApiKey(base64_decode($params['stripe_secret']));

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => $params['currency'],
                    'product_data' => [
                        'name' => $params['title'],
                        'description' => $params['description']
                    ],
                    'unit_amount' => $params['charge_amount'], // Amount in cents
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'customer_email' => $params['email'],
            'success_url' => $params['ipn_url'],
            'cancel_url' => $params['cancel_url'],
        ]);

        session()->put('stripe_session_id', $session->id);
        session()->put('order_id', $params['order_id']);

        return ['id' => $session->id];
    }
}
```

**Stripe Checkout View**: `Modules/LaraPayease/resources/views/stripe.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Stripe Checkout</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <h3>Redirecting to Stripe Checkout...</h3>
    
    <script>
        const stripe = Stripe('{{ $stripe_data['stripe_key'] }}');
        
        fetch('/larapayease/stripe/prepare-charge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(@json($stripe_data))
        })
        .then(response => response.json())
        .then(data => {
            return stripe.redirectToCheckout({ sessionId: data.id });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Payment initialization failed');
        });
    </script>
</body>
</html>
```

### 4.3 Payment Gateway Facade

**Usage in Checkout**:

```php
use Modules\LaraPayease\Facades\PaymentDriver;

class Checkout extends Component
{
    public function mount()
    {
        // Get all supported gateways
        $gateways = PaymentDriver::supportedGateways();
        $this->methods = array_merge($this->methods, $gateways);
        
        // Get enabled payment methods from settings
        $payment_methods = setting('admin_settings.payment_method');
        
        foreach ($payment_methods as $type => $value) {
            if (array_key_exists($type, $this->methods)) {
                $this->available_payment_methods[$type] = $value;
            }
        }
    }
    
    public function processPayment()
    {
        $driver = PaymentDriver::driver($this->form->paymentMethod);
        
        $paymentData = [
            'amount' => $this->totalAmount,
            'currency' => setting('_currency.default_currency'),
            'title' => 'Booking Payment',
            'description' => 'Session booking payment',
            'email' => $this->form->email,
            'ipn_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
            'order_id' => $this->order->id,
        ];
        
        return $driver->chargeCustomer($paymentData);
    }
}
```

### 4.4 Other Payment Gateways

**Razorpay** (India):
```php
use Razorpay\Api\Api;

$api = new Api($keyId, $keySecret);

$order = $api->order->create([
    'receipt' => 'order_' . time(),
    'amount' => $amount * 100, // Amount in paise
    'currency' => 'INR',
]);
```

**Paytm** (India):
```php
use Paytm\PaytmChecksum;

$paytmParams = [
    'ORDER_ID' => $orderId,
    'TXN_AMOUNT' => $amount,
    'CUST_ID' => $customerId,
];

$checksum = PaytmChecksum::generateSignature(
    json_encode($paytmParams), 
    $merchantKey
);
```

**Iyzico** (Turkey):
```php
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;

$options = new Options();
$options->setApiKey($apiKey);
$options->setSecretKey($secretKey);
$options->setBaseUrl("https://api.iyzipay.com");

$request = new CreateCheckoutFormInitializeRequest();
$request->setPrice($price);
$request->setBasketId($basketId);
// ... more configuration

$checkoutFormInitialize = CheckoutFormInitialize::create($request, $options);
```

---

## 5. Chat System (LaraGuppy)

### 5.1 LaraGuppy Package

**Location**: `packages/laraguppy/`

**Composer**: `packages/laraguppy/composer.json`

```json
{
    "name": "amentotech/laraguppy",
    "description": "LaraGuppy - A chat plugin",
    "version": "1.0.1",
    "autoload": {
        "psr-4": {
            "Amentotech\\LaraGuppy\\": "src/"
        }
    },
    "require": {
        "laravel/reverb": "@beta"
    },
    "extra": {
        "laravel": {
            "providers": [
                "Amentotech\\LaraGuppy\\LaraGuppyServiceProvider"
            ]
        }
    }
}
```

### 5.2 Chat Events

**Event**: `Amentotech\LaraGuppy\Events\GuppyChatPrivateEvent`

**Listener**: `App\Listeners\MessageReceivedListener`

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
            $threadUsers = (new ThreadsService())
                ->getThreadParticipants($event->message->thread_id);
            
            foreach($threadUsers as $participant) {
                // Skip sender and online users
                if ($participant->participantable_id != $event->message->user_id 
                    && empty($participant->participantable->is_online)) {
                    
                    // Send email notification
                    dispatch(new SendNotificationJob(
                        'newMessage', 
                        $participant->participantable, 
                        [
                            'userName' => $participant->participantable->profile->full_name,
                            'messageSender' => $event->message->messageable->profile->full_name
                        ]
                    ));

                    // Send database notification
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

### 5.3 Chat Channels

**File**: `packages/laraguppy/routes/channels.php`

```php
use Illuminate\Support\Facades\Broadcast;

// User-specific events
Broadcast::channel('events-{id}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Thread-specific messages
Broadcast::channel('thread-{id}', function ($user, $threadId) {
    // Check if user is participant in thread
    return true; // Actual authorization logic in package
});

// Public chat events
Broadcast::channel('events', function () {
    return true;
});
```

### 5.4 Chat Frontend Integration

**Livewire Component** (if exposed):
```php
public function sendMessage()
{
    $threadInfo = sendMessage(
        $this->recepientId, 
        Auth::user()->id, 
        $this->message
    );
    
    $this->threadId = $threadInfo->getData(true)['data']['message']['threadId'] ?? null;
    
    if($threadInfo){
        $this->reset('message');
    }
}
```

**JavaScript** (listening to chat events):
```javascript
Echo.private(`thread-${threadId}`)
    .listen('.message-received', (e) => {
        appendMessage(e.message);
    })
    .listen('.user-typing', (e) => {
        showTypingIndicator(e.user);
    });
```

---

## 6. Broadcasting (Reverb & Pusher)

### 6.1 Reverb Configuration

**Package**: `laravel/reverb` (Laravel 11 native WebSocket server)

**Configuration**: `config/reverb.php`

```php
<?php

return [
    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],
    ],

    'apps' => [
        [
            'app_id' => env('REVERB_APP_ID'),
            'app_key' => env('REVERB_APP_KEY'),
            'app_secret' => env('REVERB_APP_SECRET'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
            ],
            'allowed_origins' => ['*'],
            'ping_interval' => env('REVERB_PING_INTERVAL', 30),
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
        ],
    ],
];
```

**Environment Variables**:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

**Starting Reverb Server**:
```bash
# Development
php artisan reverb:start

# Production (with SSL)
php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=your-domain.com
```

### 6.2 Pusher Configuration (Alternative)

**Configuration**: `config/broadcasting.php`

```php
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
```

**Environment Variables**:
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=us2
```

### 6.3 Laravel Echo (Frontend)

**Installation**:
```bash
npm install --save laravel-echo pusher-js
```

**Configuration** (if implemented):
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

// Subscribe to private user channel
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log('Notification:', notification);
    });

// Subscribe to chat thread
Echo.private(`thread-${threadId}`)
    .listen('.message-received', (e) => {
        console.log('New message:', e);
    });
```

---

## 7. AI Services (OpenAI)

### 7.1 OpenAI Configuration

**Package**: `openai-php/laravel`

**Configuration**: `config/openai.php`

```php
<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
];
```

**Environment Variables**:
```env
OPENAI_API_KEY=sk-your-api-key
OPENAI_ORGANIZATION=org-your-organization-id
```

### 7.2 AI Writer Integration

**Component**: `resources/views/components/open_ai.blade.php`

**Usage Example**:
```php
use OpenAI\Laravel\Facades\OpenAI;

public function generateContent($prompt)
{
    $result = OpenAI::chat()->create([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful tutor content assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_tokens' => 500,
        'temperature' => 0.7,
    ]);

    return $result->choices[0]->message->content;
}
```

**AI-Powered Features**:
1. **Content Generation**: Auto-generate course descriptions
2. **Title Suggestions**: Generate SEO-friendly titles
3. **Email Templates**: AI-assisted email writing
4. **Translation**: Content translation assistance
5. **Summarization**: Summarize long text content

---

## 8. Cloud Storage

### 8.1 AWS S3 Integration

**Package**: `league/flysystem-aws-s3-v3`

**Configuration**: `config/filesystems.php`

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
],
```

**Environment Variables**:
```env
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
```

**Usage**:
```php
use Illuminate\Support\Facades\Storage;

// Store file to S3
Storage::disk('s3')->put('avatars/user-123.jpg', $fileContents);

// Get file URL
$url = Storage::disk('s3')->url('avatars/user-123.jpg');

// Check existence
$exists = Storage::disk('s3')->exists('avatars/user-123.jpg');

// Delete file
Storage::disk('s3')->delete('avatars/user-123.jpg');
```

---

## 9. Other Integrations

### 9.1 PDF Generation (Puppeteer)

**Package**: `spatie/browsershot` (uses Puppeteer)

**Dependencies**:
```json
{
    "dependencies": {
        "puppeteer": "^23.5.1",
        "puppeteer-core": "^23.5.2"
    }
}
```

**Usage**:
```php
use Spatie\Browsershot\Browsershot;

public function generateInvoicePDF($invoice)
{
    $html = view('pdf.invoice', compact('invoice'))->render();
    
    Browsershot::html($html)
        ->format('A4')
        ->margins(10, 10, 10, 10)
        ->save(storage_path("app/invoices/invoice-{$invoice->id}.pdf"));
    
    return storage_path("app/invoices/invoice-{$invoice->id}.pdf");
}
```

### 9.2 Email Services

**Supported Drivers**:
- SMTP (Generic)
- Mailgun
- SendGrid
- Amazon SES
- Postmark

**Configuration**: `config/mail.php`

```php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],
    
    'mailgun' => [
        'transport' => 'mailgun',
    ],
    
    'ses' => [
        'transport' => 'ses',
    ],
],
```

### 9.3 Laravel Telescope (Dev Tool)

**Package**: `laravel/telescope`

**Purpose**: Application debugging and monitoring

**Features**:
- Request monitoring
- Exception tracking
- Log viewer
- Database queries
- Cache operations
- Queue monitoring
- Mail preview

**Access**: `http://yourdomain.com/telescope`

---

## Summary

**Third-Party Integrations Overview**:

**Video Conferencing**:
- ✅ Google Meet (OAuth 2.0, Calendar API)
- ✅ Zoom (Server-to-Server OAuth)

**Payments** (LaraPayease Module):
- ✅ Stripe (International)
- ✅ Razorpay (India)
- ✅ Paytm (India)
- ✅ Iyzico (Turkey)

**Communication**:
- ✅ LaraGuppy Chat (Real-time messaging)
- ✅ Email services (SMTP, Mailgun, SendGrid, SES)
- ✅ Notifications (Email + Database + Broadcasting)

**Broadcasting**:
- ✅ Laravel Reverb (Native WebSocket)
- ✅ Pusher (Alternative SaaS)

**AI Services**:
- ✅ OpenAI GPT-4 (Content generation)

**Storage**:
- ✅ AWS S3 (Cloud storage)
- ✅ Local storage

**Other**:
- ✅ Google Translate (Language translation)
- ✅ Google OAuth (Social login)
- ✅ Puppeteer (PDF generation)
- ✅ Laravel Telescope (Debugging)

**Integration Patterns**:
✅ Service layer abstraction
✅ Facade pattern for gateway access
✅ Module-based extensions
✅ Queue-based async processing
✅ Event-driven architecture
✅ OAuth 2.0 authentication
✅ Webhook handling
✅ API key management via settings

**Environment Configuration**:
All integrations configured via `.env` file and admin settings panel for easy management without code changes.
