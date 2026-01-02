# PHASE 13: TESTING STRATEGY

## Table of Contents
1. [Testing Overview](#testing-overview)
2. [PHPUnit Configuration](#phpunit-configuration)
3. [Test Structure](#test-structure)
4. [Feature Tests](#feature-tests)
5. [Unit Tests](#unit-tests)
6. [Database Testing](#database-testing)
7. [Livewire Component Testing](#livewire-component-testing)
8. [API Testing](#api-testing)
9. [Testing Best Practices](#testing-best-practices)
10. [Continuous Integration](#continuous-integration)

---

## 1. Testing Overview

### 1.1 Testing Framework

**Laravel Testing Stack**:
- **PHPUnit**: Primary testing framework (v11.x)
- **Laravel Testing Utilities**: Built-in testing helpers
- **Livewire Testing**: Volt component testing support
- **Factory Pattern**: Database seeding for tests
- **RefreshDatabase**: Database state management

### 1.2 Test Coverage Areas

**Current Test Coverage**:
```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── PasswordConfirmationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── PasswordUpdateTest.php
│   │   └── RegistrationTest.php
│   ├── ExampleTest.php
│   └── ProfileTest.php
├── Unit/
│   └── ExampleTest.php
└── TestCase.php
```

**Test Distribution**:
- ✅ **Authentication Tests**: 6 test files (Login, Registration, Password Reset, Email Verification)
- ✅ **Profile Tests**: User profile management
- ⚠️ **Booking Tests**: Not yet implemented
- ⚠️ **Payment Tests**: Not yet implemented
- ⚠️ **LMS Features**: Not yet implemented
- ⚠️ **API Tests**: Not yet implemented

---

## 2. PHPUnit Configuration

### 2.1 PHPUnit Configuration File

**File**: `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <!-- SQLite in-memory for fast tests -->
        <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
        <!-- <env name="DB_DATABASE" value=":memory:"/> -->
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

**Key Configuration Settings**:

1. **Test Suites**:
   - `Unit`: Isolated logic tests
   - `Feature`: Integration/HTTP tests

2. **Source Coverage**:
   - Includes `app/` directory for coverage reporting

3. **Testing Environment**:
   - `APP_ENV=testing`: Separate environment
   - `BCRYPT_ROUNDS=4`: Faster password hashing
   - `CACHE_STORE=array`: In-memory cache
   - `MAIL_MAILER=array`: Prevents actual email sending
   - `QUEUE_CONNECTION=sync`: Synchronous job execution
   - `SESSION_DRIVER=array`: In-memory sessions
   - `TELESCOPE_ENABLED=false`: Disable debugging overhead
   - `PULSE_ENABLED=false`: Disable monitoring

4. **Database Options**:
   - Commented SQLite in-memory option for ultra-fast tests
   - Can use dedicated test database

### 2.2 Running Tests

**Command Reference**:

```bash
# Run all tests
php artisan test

# Run all tests with PHPUnit
vendor/bin/phpunit

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Run specific test method
php artisan test --filter test_users_can_authenticate

# Run with coverage (requires xdebug)
php artisan test --coverage

# Run with coverage minimum threshold
php artisan test --coverage --min=80

# Parallel testing (faster)
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure
```

---

## 3. Test Structure

### 3.1 Base TestCase

**File**: `tests/TestCase.php`

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

**Extended Base TestCase** (Recommended Enhancement):

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup logic
        $this->withoutVite();
    }
    
    /**
     * Create authenticated user for testing
     */
    protected function actingAsUser($role = 'student')
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        
        return $this->actingAs($user);
    }
    
    /**
     * Create tutor user
     */
    protected function actingAsTutor()
    {
        return $this->actingAsUser('tutor');
    }
    
    /**
     * Create admin user
     */
    protected function actingAsAdmin()
    {
        return $this->actingAsUser('admin');
    }
    
    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure($response, array $structure)
    {
        $response->assertJsonStructure($structure);
    }
}
```

### 3.2 Test Organization

**Naming Conventions**:

```php
// Feature Test Example
class BookingTest extends TestCase
{
    // Convention: test_<what>_<condition>
    public function test_user_can_create_booking(): void
    {
        // Arrange, Act, Assert
    }
    
    public function test_booking_requires_valid_time_slot(): void
    {
        // Test
    }
    
    public function test_user_cannot_double_book(): void
    {
        // Test
    }
}

// Unit Test Example
class BookingServiceTest extends TestCase
{
    public function test_calculates_booking_price_correctly(): void
    {
        // Test
    }
}
```

---

## 4. Feature Tests

### 4.1 Authentication Tests

**File**: `tests/Feature/Auth/AuthenticationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
```

### 4.2 Registration Tests

**File**: `tests/Feature/Auth/RegistrationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
```

### 4.3 Profile Tests

**File**: `tests/Feature/ProfileTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com');

        $component->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email);

        $component->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password');

        $component->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password');

        $component->call('deleteUser');

        $component->assertHasErrors('password');
        $this->assertNotNull($user->fresh());
    }
}
```

### 4.4 Recommended Feature Tests (To Be Implemented)

**Booking Tests**:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_available_slots(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');
        
        Slot::factory()->count(5)->create([
            'user_id' => $tutor->id,
            'status' => 'available'
        ]);

        $this->actingAs($student);
        
        $response = $this->get('/tutors/' . $tutor->id);

        $response->assertOk();
        $response->assertSee('Available Slots');
    }

    public function test_student_can_book_available_slot(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');
        
        $slot = Slot::factory()->create([
            'user_id' => $tutor->id,
            'status' => 'available'
        ]);

        $this->actingAs($student);

        $response = $this->post('/bookings', [
            'slot_id' => $slot->id,
            'tutor_id' => $tutor->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('slot_bookings', [
            'slot_id' => $slot->id,
            'booker_id' => $student->id,
            'status' => 'pending'
        ]);
    }

    public function test_student_cannot_book_unavailable_slot(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        
        $slot = Slot::factory()->create([
            'status' => 'booked'
        ]);

        $this->actingAs($student);

        $response = $this->post('/bookings', [
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_tutor_can_accept_booking(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');
        
        $booking = SlotBooking::factory()->create([
            'tutor_id' => $tutor->id,
            'status' => 'pending'
        ]);

        $this->actingAs($tutor);

        $response = $this->patch("/bookings/{$booking->id}/accept");

        $response->assertRedirect();
        $this->assertDatabaseHas('slot_bookings', [
            'id' => $booking->id,
            'status' => 'accepted'
        ]);
    }
}
```

**Payment Tests**:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_checkout_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/checkout');

        $response->assertOk();
        $response->assertSee('Payment Method');
    }

    public function test_order_is_created_with_correct_amount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/orders', [
            'amount' => 100.00,
            'payment_method' => 'stripe',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => 0 // pending
        ]);
    }

    public function test_user_can_apply_wallet_balance(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 50.00]);
        
        $this->actingAs($user);

        $response = $this->post('/orders', [
            'amount' => 100.00,
            'use_wallet' => true,
            'payment_method' => 'stripe',
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'wallet_amount' => 50.00,
        ]);
    }
}
```

---

## 5. Unit Tests

### 5.1 Example Unit Test

**File**: `tests/Unit/ExampleTest.php`

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

### 5.2 Recommended Unit Tests

**Service Layer Tests**:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\BookingService;
use App\Models\SlotBooking;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    public function test_calculates_booking_duration_correctly(): void
    {
        $startTime = '2025-01-10 10:00:00';
        $endTime = '2025-01-10 11:30:00';

        $duration = $this->bookingService->calculateDuration($startTime, $endTime);

        $this->assertEquals(90, $duration); // 90 minutes
    }

    public function test_calculates_booking_price_with_discount(): void
    {
        $basePrice = 100;
        $discountPercent = 20;

        $finalPrice = $this->bookingService->calculatePrice($basePrice, $discountPercent);

        $this->assertEquals(80, $finalPrice);
    }

    public function test_validates_booking_time_overlap(): void
    {
        $existingBooking = SlotBooking::factory()->create([
            'start_datetime' => '2025-01-10 10:00:00',
            'end_datetime' => '2025-01-10 11:00:00',
        ]);

        $newStart = '2025-01-10 10:30:00';
        $newEnd = '2025-01-10 11:30:00';

        $hasOverlap = $this->bookingService->hasTimeOverlap(
            $existingBooking->tutor_id,
            $newStart,
            $newEnd
        );

        $this->assertTrue($hasOverlap);
    }
}
```

**Helper Function Tests**:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_format_currency_helper(): void
    {
        $result = formatCurrency(1234.56);
        $this->assertEquals('$1,234.56', $result);
    }

    public function test_get_user_role_helper(): void
    {
        $user = User::factory()->create();
        $user->assignRole('tutor');

        $this->actingAs($user);

        $roleData = getUserRole();
        
        $this->assertEquals('tutor', $roleData['roleName']);
        $this->assertNotNull($roleData['roleId']);
    }

    public function test_generate_password_helper(): void
    {
        $password = generatePassword(12);
        
        $this->assertEquals(12, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Za-z0-9]/', $password);
    }
}
```

---

## 6. Database Testing

### 6.1 Database Factories

**User Factory**: `database/factories/UserFactory.php`

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    
    /**
     * Create user with specific role
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole($role);
        });
    }
}
```

**Recommended Additional Factories**:

```php
// SlotFactory.php
class SlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'start_time' => fake()->dateTimeBetween('now', '+1 week'),
            'end_time' => fake()->dateTimeBetween('+1 hour', '+2 hours'),
            'status' => 'available',
            'price' => fake()->randomFloat(2, 20, 100),
        ];
    }

    public function booked(): static
    {
        return $this->state(['status' => 'booked']);
    }
}

// SlotBookingFactory.php
class SlotBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slot_id' => Slot::factory(),
            'booker_id' => User::factory(),
            'tutor_id' => User::factory()->withRole('tutor'),
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, 20, 100),
            'start_datetime' => fake()->dateTimeBetween('now', '+1 week'),
            'end_datetime' => fake()->dateTimeBetween('+1 hour', '+2 hours'),
        ];
    }
}

// OrderFactory.php
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 10, 500),
            'status' => 0, // pending
            'payment_method' => fake()->randomElement(['stripe', 'razorpay', 'wallet']),
        ];
    }
}
```

### 6.2 RefreshDatabase Trait

**Usage**:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        // Database is automatically migrated and rolled back after each test
        User::factory()->create();
        
        $this->assertDatabaseCount('users', 1);
    }
}
```

### 6.3 Database Assertions

**Available Assertions**:

```php
// Assert record exists
$this->assertDatabaseHas('users', [
    'email' => 'test@example.com',
]);

// Assert record does not exist
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com',
]);

// Assert table has exact count
$this->assertDatabaseCount('users', 10);

// Assert model exists in database
$this->assertModelExists($user);

// Assert model was deleted
$this->assertModelMissing($user);

// Assert soft-deleted model exists
$this->assertSoftDeleted('users', [
    'id' => $user->id,
]);
```

---

## 7. Livewire Component Testing

### 7.1 Volt Component Testing

**Testing Livewire Volt Components**:

```php
use Livewire\Volt\Volt;

public function test_search_tutor_component_filters_results(): void
{
    $tutor1 = User::factory()->create(['name' => 'John Doe']);
    $tutor1->assignRole('tutor');
    
    $tutor2 = User::factory()->create(['name' => 'Jane Smith']);
    $tutor2->assignRole('tutor');

    Volt::test('components.search-tutor')
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
}

public function test_checkout_component_calculates_total(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('frontend.checkout')
        ->set('form.amount', 100)
        ->set('form.discount', 10)
        ->call('calculateTotal')
        ->assertSet('totalAmount', 90);
}
```

### 7.2 Full Livewire Component Testing

**Testing Class-Based Livewire Components**:

```php
use Livewire\Livewire;
use App\Livewire\Components\SearchTutor;

public function test_search_tutor_pagination(): void
{
    User::factory()->count(20)->create()->each(fn($u) => $u->assignRole('tutor'));

    Livewire::test(SearchTutor::class)
        ->assertSee('tutors')
        ->call('nextPage')
        ->assertSet('page', 2);
}

public function test_toggle_favourite_dispatches_event(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $tutor = User::factory()->create();
    $tutor->assignRole('tutor');

    Livewire::test(SearchTutor::class)
        ->call('toggleFavourite', $tutor->id)
        ->assertDispatched('toggleFavIcon');
}
```

### 7.3 Component Event Testing

```php
public function test_component_emits_event(): void
{
    Livewire::test(MyComponent::class)
        ->call('submit')
        ->assertDispatched('form-submitted');
}

public function test_component_listens_to_event(): void
{
    Livewire::test(MyComponent::class)
        ->dispatch('refresh-data')
        ->assertSet('refreshed', true);
}
```

---

## 8. API Testing

### 8.1 API Authentication Testing

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_api(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email'],
            ]);
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    public function test_api_accepts_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/notifications');

        $response->assertOk();
    }
}
```

### 8.2 API CRUD Testing

```php
public function test_api_can_list_bookings(): void
{
    $user = User::factory()->create();
    $user->assignRole('student');
    
    SlotBooking::factory()->count(5)->create([
        'booker_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/bookings');

    $response
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'tutor', 'start_datetime', 'status']
            ]
        ]);
}

public function test_api_validates_booking_creation(): void
{
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)
        ->postJson('/api/bookings', [
            'slot_id' => null, // Invalid
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['slot_id']);
}
```

---

## 9. Testing Best Practices

### 9.1 AAA Pattern

**Arrange-Act-Assert**:

```php
public function test_user_can_create_booking(): void
{
    // Arrange: Setup test data
    $student = User::factory()->create();
    $student->assignRole('student');
    
    $slot = Slot::factory()->create([
        'status' => 'available'
    ]);

    // Act: Perform the action
    $this->actingAs($student);
    $response = $this->post('/bookings', [
        'slot_id' => $slot->id,
    ]);

    // Assert: Verify the outcome
    $response->assertRedirect();
    $this->assertDatabaseHas('slot_bookings', [
        'slot_id' => $slot->id,
        'booker_id' => $student->id,
    ]);
}
```

### 9.2 Test Isolation

**Each test should be independent**:

```php
// ✅ Good: Each test creates its own data
public function test_feature_a(): void
{
    $user = User::factory()->create();
    // Test feature A
}

public function test_feature_b(): void
{
    $user = User::factory()->create();
    // Test feature B
}

// ❌ Bad: Tests depend on shared state
protected $sharedUser;

public function setUp(): void
{
    parent::setUp();
    $this->sharedUser = User::factory()->create(); // Don't do this
}
```

### 9.3 Descriptive Test Names

```php
// ✅ Good: Clear what's being tested
public function test_student_cannot_book_slot_outside_business_hours(): void

// ✅ Good: Uses convention
public function test_tutor_can_view_own_earnings(): void

// ❌ Bad: Vague
public function test_booking(): void

// ❌ Bad: Not descriptive
public function test_user_stuff(): void
```

### 9.4 Test Data Builders

**Using Factory States**:

```php
// Create specific test scenarios easily
$availableSlot = Slot::factory()->available()->create();
$bookedSlot = Slot::factory()->booked()->create();
$tutor = User::factory()->withRole('tutor')->create();
$verifiedUser = User::factory()->verified()->create();
$unverifiedUser = User::factory()->unverified()->create();
```

### 9.5 Avoiding Over-Testing

```php
// ✅ Good: Test business logic
public function test_booking_calculates_correct_price(): void
{
    $booking = new BookingService();
    $price = $booking->calculatePrice(100, 20);
    $this->assertEquals(80, $price);
}

// ❌ Bad: Testing framework functionality
public function test_eloquent_saves_to_database(): void
{
    $user = new User(['email' => 'test@test.com']);
    $user->save();
    $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
}
```

### 9.6 Testing Exceptions

```php
use Illuminate\Validation\ValidationException;

public function test_booking_throws_exception_for_invalid_slot(): void
{
    $this->expectException(ValidationException::class);
    
    $bookingService = new BookingService();
    $bookingService->createBooking(['slot_id' => 999]);
}
```

### 9.7 Time-Based Testing

```php
use Illuminate\Support\Facades\Date;

public function test_expired_bookings_are_marked_as_past(): void
{
    // Travel to the past
    Date::setTestNow('2025-01-01 10:00:00');
    
    $booking = SlotBooking::factory()->create([
        'start_datetime' => '2025-01-01 09:00:00',
    ]);
    
    // Travel to the future
    Date::setTestNow('2025-01-01 11:00:00');
    
    $this->assertTrue($booking->fresh()->isPast());
}
```

---

## 10. Continuous Integration

### 10.1 GitHub Actions Workflow

**File**: `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, dom, fileinfo, mysql
          coverage: xdebug

      - name: Install Composer Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Copy Environment File
        run: cp .env.example .env

      - name: Generate Application Key
        run: php artisan key:generate

      - name: Run Migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run Tests
        run: php artisan test --parallel --coverage --min=70
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Upload Coverage Reports
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

### 10.2 Local Testing Script

**File**: `scripts/test.sh`

```bash
#!/bin/bash

# Run all tests
echo "Running all tests..."
php artisan test

# Run with coverage
echo "Running tests with coverage..."
php artisan test --coverage --min=70

# Run parallel tests
echo "Running parallel tests..."
php artisan test --parallel

# Run specific suite
echo "Running feature tests..."
php artisan test --testsuite=Feature
```

---

## Summary

**Current Testing Status**:
- ✅ PHPUnit configured with Laravel 11
- ✅ Authentication tests implemented (6 test files)
- ✅ Profile management tests
- ✅ Factory patterns established
- ✅ Test environment configured
- ⚠️ Limited coverage of core LMS features
- ⚠️ No API endpoint tests
- ⚠️ No booking/payment feature tests

**Recommended Next Steps**:
1. Implement booking feature tests
2. Add payment gateway tests
3. Create comprehensive API tests
4. Add service layer unit tests
5. Implement integration tests for third-party services
6. Set up CI/CD pipeline
7. Achieve 70%+ code coverage
8. Add browser tests with Laravel Dusk (optional)

**Testing Commands**:
```bash
# Basic
php artisan test

# With coverage
php artisan test --coverage

# Parallel execution
php artisan test --parallel

# Specific suite
php artisan test --testsuite=Feature

# Stop on failure
php artisan test --stop-on-failure
```

**Test Organization**:
- `tests/Feature/` - HTTP/integration tests
- `tests/Unit/` - Isolated logic tests
- `database/factories/` - Test data factories
- `phpunit.xml` - PHPUnit configuration
