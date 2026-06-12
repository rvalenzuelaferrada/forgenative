<?php

use App\Services\LocalePreference;
use Illuminate\Support\Facades\Storage;

test('returns a successful response', function () {
    Storage::fake('local');
    app(LocalePreference::class)->set('en');

    $response = $this->get(route('sites.index'));

    $response->assertOk();
});
