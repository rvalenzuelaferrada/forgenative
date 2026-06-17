<?php

test('the native macos bundle identifier belongs to forge native', function () {
    expect(config('nativephp.app_id'))
        ->toBe('com.ricardovalenzuela.forgenative')
        ->not->toBe('com.nativephp.app');
});
