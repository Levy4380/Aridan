<?php

use App\Models\User;

/*
| The app can be added to the home screen as an app (mainly iOS): web
| manifest, icons and the Apple meta tags. No offline support yet.
*/

test('every page declares the manifest and the iOS home screen tags', function (bool $loggedIn) {
    if ($loggedIn) {
        $this->actingAs(User::factory()->create());
    }

    $this->get($loggedIn ? route('calendar') : route('login'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', escape: false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', escape: false)
        ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', escape: false)
        ->assertSee('<meta name="apple-mobile-web-app-title" content="'.config('app.name').'">', escape: false);
})->with(['login' => [false], 'app' => [true]]);

test('the manifest opens the calendar standalone, with icons that exist', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->display->toBe('standalone')
        ->start_url->toBe(parse_url(route('calendar'), PHP_URL_PATH))
        ->and(collect($manifest['icons'])->pluck('purpose')->all())->toContain('any', 'maskable');

    foreach ([...collect($manifest['icons'])->pluck('src'), '/apple-touch-icon.png', '/favicon.ico'] as $icon) {
        expect(file_exists(public_path(ltrim($icon, '/'))))->toBeTrue("Missing {$icon}");
    }

    [$width, $height] = getimagesize(public_path('apple-touch-icon.png'));
    expect([$width, $height])->toBe([180, 180]);
});

test('the account menu offers installing the app, with the steps drawer for iOS', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('calendar'))
        ->assertOk()
        ->assertSee(__('nav.install.button'))
        ->assertSee('data-test="install-app-drawer"', escape: false)
        ->assertSee(__('nav.install.ios_add'), escape: false)
        ->assertSee(__('nav.install.other_add'), escape: false);
});
