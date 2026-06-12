<?php

use App\Services\LocalePreference;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

test('the language screen is shown before the application on first run', function () {
    $this->get(route('sites.index'))
        ->assertRedirectToRoute('language-preference.create');

    $this->get(route('language-preference.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LanguagePreference'));
});

test('english can be selected and is used by the interface', function () {
    $this->post(route('language-preference.store'), [
        'locale' => 'en',
    ])->assertRedirectToRoute('sites.index');

    expect(app(LocalePreference::class)->get())->toBe('en');

    $this->get(route('sites.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('copy.heading', 'Connect your Laravel Forge account')
            ->where('copy.submit', 'Add connection'));
});

test('spanish can be selected and is used by the interface', function () {
    $this->post(route('language-preference.store'), [
        'locale' => 'es',
    ])->assertRedirectToRoute('sites.index');

    expect(app(LocalePreference::class)->get())->toBe('es');

    $this->get(route('sites.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('copy.heading', 'Conecta tu cuenta de Laravel Forge')
            ->where('copy.submit', 'Agregar conexión'));
});

test('the language screen is skipped after a preference is stored', function () {
    app(LocalePreference::class)->set('en');

    $this->get(route('language-preference.create'))
        ->assertRedirectToRoute('sites.index');
});

test('unsupported languages are rejected', function () {
    $this->post(route('language-preference.store'), [
        'locale' => 'fr',
    ])->assertSessionHasErrors('locale');

    expect(app(LocalePreference::class)->get())->toBeNull();
});

test('the language can be changed from connections', function () {
    app(LocalePreference::class)->set('en');

    $connectionsUrl = route('forge-credentials.index', [
        'connection' => 123,
    ]);

    $this->from($connectionsUrl)
        ->post(route('language-preference.update'), [
            'locale' => 'es',
        ])
        ->assertRedirect($connectionsUrl);

    expect(app(LocalePreference::class)->get())->toBe('es');

    $this->get(route('forge-credentials.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('currentLocale', 'es')
            ->where('copy.language', 'Idioma')
            ->where('copy.selected', 'Seleccionado'));
});
