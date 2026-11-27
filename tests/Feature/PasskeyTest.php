<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use MarcelWeidum\Passkeys\Livewire\Passkeys;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;

uses(RefreshDatabase::class);

it('offers passkey sign in beside the password form', function () {
    $this->get(route('filament.admin.auth.login'))
        ->assertSuccessful()
        ->assertSee(route('passkeys.login'), false)
        ->assertSee('type="password"', false);
});

it('issues a sign in challenge for this site only', function () {
    $options = $this->get(route('passkeys.authentication_options'))->assertSuccessful()->getContent();

    expect(json_decode($options, true))
        ->toHaveKey('challenge')
        ->and(json_decode($options, true)['rpId'])->toBe(parse_url(config('app.url'), PHP_URL_HOST));
});

it('does not sign anyone in without a valid passkey', function () {
    User::factory()->create();

    $this->post(route('passkeys.login'), ['start_authentication_response' => '{}'])->assertRedirect();

    $this->assertGuest();
});

it('lets a signed in user manage passkeys on their profile', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(HasPasskeys::class);

    $this->actingAs($user)->get(route('filament.admin.auth.profile'))
        ->assertSuccessful()
        ->assertSeeLivewire(Passkeys::class);
});

it('keeps the profile page from guests', function () {
    $this->get(route('filament.admin.auth.profile'))->assertRedirect(route('filament.admin.auth.login'));
});

it('only deletes passkeys belonging to the signed in user', function () {
    [$owner, $other] = User::factory()->count(2)->create();
    DB::table('passkeys')->insert([
        'authenticatable_id' => $owner->id, 'name' => 'Laptop', 'credential_id' => 'abc', 'data' => '{}',
    ]);
    $id = DB::table('passkeys')->value('id');

    Livewire::actingAs($other)->test(Passkeys::class)->call('deletePasskey', $id);
    expect(DB::table('passkeys')->count())->toBe(1);

    Livewire::actingAs($owner)->test(Passkeys::class)->call('deletePasskey', $id);
    expect(DB::table('passkeys')->count())->toBe(0);
});
