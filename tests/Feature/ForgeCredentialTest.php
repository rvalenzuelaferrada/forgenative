<?php

use App\Models\ForgeCredential;
use App\Services\ForgeTokenValidator;
use App\Services\ForgeTokenVault;
use App\Services\LocalePreference;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    app(LocalePreference::class)->set('en');
});

test('the onboarding page lists connections without exposing tokens', function () {
    $credential = ForgeCredential::factory()->create([
        'name' => 'team@example.com',
        'encrypted_token' => 'native:encrypted-secret',
        'token_fingerprint' => hash('sha256', 'plain-secret'),
    ]);

    $this->get(route('forge-credentials.index', [
        'connection' => $credential->id,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('copy.heading', 'Connect your Laravel Forge account')
            ->where('activeConnectionId', $credential->id)
            ->has('credentials', 1)
            ->where('credentials.0.name', 'team@example.com')
            ->missing('credentials.0.encrypted_token')
            ->missing('credentials.0.token_fingerprint'));
});

test('a validated forge token can be stored securely', function () {
    $validator = Mockery::mock(ForgeTokenValidator::class);
    $validator->shouldReceive('validate')
        ->once()
        ->with('forge-api-token-with-enough-length')
        ->andReturn([
            'id' => 123,
            'name' => 'Taylor',
            'email' => 'taylor@example.com',
        ]);

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('encrypt')
        ->once()
        ->with('forge-api-token-with-enough-length')
        ->andReturn('native:encrypted-token');

    app()->instance(ForgeTokenValidator::class, $validator);
    app()->instance(ForgeTokenVault::class, $vault);

    $response = $this->post(route('forge-credentials.store'), [
        'name' => 'taylor@example.com',
        'token' => 'forge-api-token-with-enough-length',
    ]);

    $credential = ForgeCredential::query()->sole();

    $response
        ->assertRedirectToRoute('sites.index', [
            'connection' => $credential->id,
        ])
        ->assertSessionHasNoErrors();

    expect($credential)
        ->name->toBe('taylor@example.com')
        ->encrypted_token->toBe('native:encrypted-token')
        ->token_fingerprint->toBe(hash('sha256', 'forge-api-token-with-enough-length'))
        ->forge_user_id->toBe(123)
        ->forge_email->toBe('taylor@example.com');

    expect((string) $credential->getRawOriginal('encrypted_token'))
        ->not->toContain('forge-api-token-with-enough-length');
});

test('a large forge jwt can be stored securely', function () {
    $token = 'header.'.str_repeat('a', 4096).'.signature';

    $validator = Mockery::mock(ForgeTokenValidator::class);
    $validator->shouldReceive('validate')
        ->once()
        ->with($token)
        ->andReturn([
            'id' => 123,
            'name' => 'Taylor',
            'email' => 'taylor@example.com',
        ]);

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('encrypt')
        ->once()
        ->with($token)
        ->andReturn('native:encrypted-large-token');

    app()->instance(ForgeTokenValidator::class, $validator);
    app()->instance(ForgeTokenVault::class, $vault);

    $response = $this->post(route('forge-credentials.store'), [
        'name' => 'large-token@example.com',
        'token' => $token,
    ]);

    $credential = ForgeCredential::query()->sole();

    $response
        ->assertRedirectToRoute('sites.index', [
            'connection' => $credential->id,
        ])
        ->assertSessionHasNoErrors();

    expect($credential->encrypted_token)->toBe('native:encrypted-large-token');
});

test('an unknown active connection is not exposed to the selector', function () {
    ForgeCredential::factory()->create();

    $this->get(route('forge-credentials.index', [
        'connection' => 999999,
    ]))->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('activeConnectionId', null));
});

test('an invalid forge token is rejected and never flashed to the session', function () {
    $validator = Mockery::mock(ForgeTokenValidator::class);
    $validator->shouldReceive('validate')
        ->once()
        ->andThrow(new RuntimeException('Unauthorized'));

    app()->instance(ForgeTokenValidator::class, $validator);

    $this->from(route('forge-credentials.index'))
        ->post(route('forge-credentials.store'), [
            'name' => 'invalid@example.com',
            'token' => 'invalid-forge-token-with-enough-length',
        ])
        ->assertRedirect(route('forge-credentials.index'))
        ->assertSessionHasErrors('token')
        ->assertSessionMissing('_old_input.token');

    expect(ForgeCredential::query()->exists())->toBeFalse();
});

test('the same token cannot be registered twice', function () {
    $token = 'duplicate-forge-token-with-enough-length';

    ForgeCredential::factory()->create([
        'token_fingerprint' => hash('sha256', $token),
    ]);

    $this->from(route('forge-credentials.index'))
        ->post(route('forge-credentials.store'), [
            'name' => 'another@example.com',
            'token' => $token,
        ])
        ->assertRedirect(route('forge-credentials.index'))
        ->assertSessionHasErrors('token');

    expect(ForgeCredential::query()->count())->toBe(1);
});
