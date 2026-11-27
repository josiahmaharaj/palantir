<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a guest on the home page to login', function () {
    $this->get('/')->assertRedirect(route('filament.admin.auth.login'));
});
