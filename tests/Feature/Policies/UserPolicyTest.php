<?php

use App\Enums\UserRole;
use App\Models\User;

it('lets only a superadmin invite or appoint a superadmin', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($admin->can('inviteAdmin', [User::class, UserRole::Admin]))->toBeTrue()
        ->and($admin->can('inviteAdmin', [User::class, UserRole::Superadmin]))->toBeFalse()
        ->and($superadmin->can('inviteAdmin', [User::class, UserRole::Superadmin]))->toBeTrue();
});

it('shows superadmins read-only to admins', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($admin->can('updateAdmin', $superadmin))->toBeFalse()
        ->and($admin->can('deactivate', $superadmin))->toBeFalse()
        ->and($admin->can('resetPassword', $superadmin))->toBeFalse();
});

it('forbids deactivating or resetting yourself', function () {
    $superadmin = User::factory()->superadmin()->create();
    User::factory()->superadmin()->create();

    expect($superadmin->can('deactivate', $superadmin))->toBeFalse()
        ->and($superadmin->can('resetPassword', $superadmin))->toBeFalse();
});

it('keeps the last active superadmin', function () {
    $lastSuperadmin = User::factory()->superadmin()->create();
    User::factory()->superadmin()->deactivated()->create();
    $otherSuperadminActor = User::factory()->superadmin()->deactivated()->create();

    expect($otherSuperadminActor->can('deactivate', $lastSuperadmin))->toBeFalse()
        ->and($otherSuperadminActor->can('assignRole', [$lastSuperadmin, UserRole::Admin]))->toBeFalse();
});

it('lets a superadmin demote another superadmin while one stays', function () {
    $superadmin = User::factory()->superadmin()->create();
    $other = User::factory()->superadmin()->create();

    expect($superadmin->can('assignRole', [$other, UserRole::Admin]))->toBeTrue()
        ->and($superadmin->can('deactivate', $other))->toBeTrue();
});

it('forbids demoting yourself', function () {
    $superadmin = User::factory()->superadmin()->create();
    User::factory()->superadmin()->create();

    expect($superadmin->can('assignRole', [$superadmin, UserRole::Admin]))->toBeFalse()
        ->and($superadmin->can('assignRole', [$superadmin, UserRole::Superadmin]))->toBeTrue();
});

it('lets only a superadmin change their own or a customer e-mail', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    expect($admin->can('changeEmail', $admin))->toBeFalse()
        ->and($admin->can('changeEmail', $customer))->toBeFalse()
        ->and($admin->can('changeEmail', $otherAdmin))->toBeTrue()
        ->and($superadmin->can('changeEmail', $superadmin))->toBeTrue()
        ->and($superadmin->can('changeEmail', $customer))->toBeTrue();
});

it('allows working in the name of active customers only', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('impersonate', User::factory()->create()))->toBeTrue()
        ->and($admin->can('impersonate', User::factory()->admin()->create()))->toBeFalse()
        ->and($admin->can('impersonate', User::factory()->deactivated()->create()))->toBeFalse()
        ->and($admin->can('impersonate', User::factory()->invited()->create()))->toBeFalse();
});

it('offers a new invitation only to admins who never set a password', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('resendInvitation', User::factory()->admin()->invited()->create()))->toBeTrue()
        ->and($admin->can('resendInvitation', User::factory()->admin()->create()))->toBeFalse();
});
