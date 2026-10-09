<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\HtmlString;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    ViteFacade::clearResolvedInstance();
    $this->swap(Vite::class, new class extends Vite
    {
        public function __invoke($entrypoints, $buildDirectory = null): HtmlString
        {
            return new HtmlString('');
        }
    });

    $this->hotFile = sys_get_temp_dir().'/afor-vite-hot-'.Str::random(8);
    app(Vite::class)->useHotFile($this->hotFile);
});

afterEach(function () {
    if (is_file($this->hotFile)) {
        unlink($this->hotFile);
    }
});

/**
 * @return array<string, array<int, string>>
 */
function policyOf(TestResponse $response): array
{
    return collect(explode(';', $response->headers->get('Content-Security-Policy-Report-Only')))
        ->map(fn (string $directive) => explode(' ', trim($directive)))
        ->mapWithKeys(fn (array $parts) => [array_shift($parts) => $parts])
        ->all();
}

test('pages are sent with the security headers', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('Strict-Transport-Security');

    expect(policyOf($response))
        ->toMatchArray([
            'default-src'     => ["'self'"],
            'object-src'      => ["'none'"],
            'base-uri'        => ["'self'"],
            'form-action'     => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ]);
});

test('pages served over https tell browsers to keep using https', function () {
    $this->get(str_replace('http://', 'https://', route('home')))
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test("the page's own inline script carries the policy's nonce", function () {
    $response = $this->get(route('home'));

    $nonce = collect(policyOf($response)['script-src'])
        ->first(fn (string $source) => str_starts_with($source, "'nonce-"));

    expect($nonce)->not->toBeNull();
    $response->assertSee('<script nonce="'.substr($nonce, 7, -1).'">', false);
});

test('each page gets a nonce of its own', function () {
    $firstPolicy = policyOf($this->get(route('home')));
    $secondPolicy = policyOf($this->get(route('home')));

    expect($firstPolicy['script-src'])->not->toEqual($secondPolicy['script-src']);
});

test('the policy allows images from the storage disk uploads are served from', function () {
    config([
        'filesystems.default'       => 'r2',
        'filesystems.disks.r2.url'  => 'https://files.afor.app/uploads',
    ]);

    expect(policyOf($this->get(route('home')))['img-src'])->toContain('https://files.afor.app');
});

test('uploads stored on the server need no image source of their own', function () {
    config(['filesystems.default' => 'local']);

    expect(policyOf($this->get(route('home')))['img-src'])->toBe(["'self'", 'data:', 'blob:']);
});

test('the policy lets pages reach the Reverb server', function (string $scheme, string $socket) {
    config(['broadcasting.connections.reverb.options' => [
        'host'   => 'ws.afor.test',
        'port'   => 8080,
        'scheme' => $scheme,
    ]]);

    expect(policyOf($this->get(route('home')))['connect-src'])->toContain($socket);
})->with([
    'over https' => ['https', 'wss://ws.afor.test:8080'],
    'over http'  => ['http', 'ws://ws.afor.test:8080'],
]);

test("the policy allows Vite's dev server while it runs", function () {
    file_put_contents($this->hotFile, "http://127.0.0.1:5173\n");

    $policy = policyOf($this->get(route('home')));

    expect($policy['script-src'])->toContain('http://127.0.0.1:5173')
        ->and($policy['connect-src'])->toContain('http://127.0.0.1:5173', 'ws://127.0.0.1:5173');
});

test("the policy leaves Vite's dev server out once it stops", function () {
    $response = $this->get(route('home'));

    expect($response->headers->get('Content-Security-Policy-Report-Only'))->not->toContain('5173');
});
