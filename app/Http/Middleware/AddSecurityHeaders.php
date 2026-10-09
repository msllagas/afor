<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private const FONT_ORIGIN = 'https://fonts.bunny.net';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options'              => 'nosniff',
            'X-Frame-Options'                     => 'DENY',
            'Referrer-Policy'                     => 'strict-origin-when-cross-origin',
            'Permissions-Policy'                  => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy-Report-Only' => $this->contentSecurityPolicy(),
        ]);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $devServer = $this->viteDevServer();

        $directives = [
            'default-src'     => ["'self'"],
            'script-src'      => ["'self'", "'nonce-".Vite::cspNonce()."'", $devServer],
            'style-src'       => ["'self'", "'unsafe-inline'", self::FONT_ORIGIN, $devServer],
            'font-src'        => ["'self'", 'data:', self::FONT_ORIGIN, $devServer],
            'img-src'         => ["'self'", 'data:', 'blob:', $this->storageOrigin(), $devServer],
            'connect-src'     => ["'self'", $this->reverbSocket(), $devServer, $devServer ? preg_replace('/^http/', 'ws', $devServer) : null],
            'object-src'      => ["'none'"],
            'base-uri'        => ["'self'"],
            'form-action'     => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $directive) => implode(' ', [$directive, ...array_filter($sources)]))
            ->implode('; ');
    }

    private function viteDevServer(): ?string
    {
        return Vite::isRunningHot() ? rtrim(file_get_contents(Vite::hotFile())) : null;
    }

    private function storageOrigin(): ?string
    {
        $url = parse_url((string) config('filesystems.disks.'.config('filesystems.default').'.url'));

        if (!isset($url['scheme'], $url['host'])) {
            return null;
        }

        return "{$url['scheme']}://{$url['host']}".(isset($url['port']) ? ":{$url['port']}" : '');
    }

    private function reverbSocket(): ?string
    {
        $options = config('broadcasting.connections.reverb.options');

        if (blank($options['host'] ?? null)) {
            return null;
        }

        $scheme = $options['scheme'] === 'https' ? 'wss' : 'ws';

        return "{$scheme}://{$options['host']}:{$options['port']}";
    }
}
