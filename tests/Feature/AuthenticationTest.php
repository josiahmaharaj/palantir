<?php

use App\Models\DownloadLink;
use App\Models\User;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('sends guests to the login page', function (string $path) {
    $this->get($path)->assertRedirect(route('filament.admin.auth.login'));
})->with(['/', '/video-logs', '/contacts', '/users', '/upload-files', '/upload']);

it('refuses chunk uploads from guests', function () {
    $this->postJson(route('api.upload.chunk'))->assertUnauthorized();
});

it('lets a signed in user reach the panel and the upload page', function (string $path) {
    $this->actingAs(User::factory()->create())->get($path)->assertSuccessful();
})->with(['/', '/upload']);

it('lets a user sign in outside the local environment', function () {
    $user = User::factory()->create();
    app()->detectEnvironment(fn () => 'production');

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'wrong')
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertHasNoErrors(['data.password']);

    $this->assertGuest();
});

it('keeps download links open to recipients', function () {
    $this->get(route('download-link.show', 'unknown'))->assertNotFound();
    $this->post(route('download-link.file', 'unknown'))->assertNotFound();

    expect(DownloadLink::count())->toBe(0);
});
