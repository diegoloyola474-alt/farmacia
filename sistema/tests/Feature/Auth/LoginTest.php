<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Iniciar sesión')
            ->assertSee('css/bootstrap-login.css', false)
            ->assertDontSee('id="loginForm"', false)
            ->assertDontSee('js/app.js', false);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser('Administrador');

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_honors_the_remember_checkbox(): void
    {
        $user = $this->createUser('Administrador');

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertNotNull($user->fresh()->getRememberToken());
    }

    public function test_invalid_credentials_return_a_generic_error_and_only_old_email(): void
    {
        $user = $this->createUser('Administrador');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'El correo o la contraseña no son correctos.',
            ])
            ->assertSessionHas('_old_input.email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_login_requires_an_email_and_password_with_spanish_messages(): void
    {
        $this->from(route('login'))->post(route('login.store'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'El correo electrónico es obligatorio.',
                'password' => 'La contraseña es obligatoria.',
            ]);
    }

    public function test_logout_invalidates_the_session_and_redirects_to_login(): void
    {
        $user = $this->createUser('Administrador');

        $this->actingAs($user)
            ->withSession(['private-marker' => 'present'])
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('ok', 'Cerraste sesión correctamente.')
            ->assertSessionMissing('private-marker');

        $this->assertGuest();
        $this->get(route('login'))->assertOk()->assertSee('Cerraste sesión correctamente.');
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_vendedor_receives_cajero_permissions_and_cannot_access_medicines(): void
    {
        $user = $this->createUser('Vendedor');

        $response = $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));

        $this->get(route('medicines.index'))->assertForbidden();
    }

    public function test_quimico_farmaceutico_can_access_medicines_but_not_role_management(): void
    {
        $this->actingAs($this->createUser('Químico farmacéutico'))
            ->get(route('medicines.index'))
            ->assertOk();

        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_only_administrators_can_manage_users(): void
    {
        $this->actingAs($this->createUser('Administrador'))
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($this->createUser('Cajero'))
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_sales_always_belong_to_the_authenticated_user(): void
    {
        $seller = $this->createUser('Vendedor');
        $otherUser = $this->createUser('Administrador');
        $category = Category::create(['name' => 'Prueba']);
        $customer = Customer::create(['name' => 'Cliente', 'dni' => '12345678']);
        $medicine = Medicine::create([
            'category_id' => $category->id,
            'name' => 'Medicamento de prueba',
            'price' => 10,
            'stock' => 5,
        ]);

        $this->actingAs($seller)->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'user_id' => $otherUser->id,
            'date' => now()->toDateTimeString(),
            'items' => [
                ['medicine_id' => $medicine->id, 'amount' => 1],
            ],
        ])->assertRedirect();

        $this->assertSame($seller->id, Sale::query()->sole()->user_id);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => 'Rol para pruebas']
        );

        return User::create([
            'role_id' => $role->id,
            'name' => 'Usuario de prueba',
            'dni' => (string) random_int(10000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret-password',
        ]);
    }
}
