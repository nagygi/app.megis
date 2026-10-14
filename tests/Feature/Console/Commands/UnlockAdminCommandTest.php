<?php

use App\Models\User;

it('unlocks a locked admin and resets the counter', function () {
    $admin = User::factory()->admin()->locked()->create(['email' => 'zarolt@megis.hu']);

    $this->artisan('megis:admin:unlock', ['email' => 'Zarolt@megis.hu'])->assertSuccessful();

    expect($admin->fresh())
        ->locked_at->toBeNull()
        ->failed_login_attempts->toBe(0);
});

it('fails for an address that is not an admin', function () {
    User::factory()->create(['email' => 'ugyfel@example.com']);

    $this->artisan('megis:admin:unlock', ['email' => 'ugyfel@example.com'])->assertFailed();
});
