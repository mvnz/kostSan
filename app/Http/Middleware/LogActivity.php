<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogActivity
{
    // Route yang tidak perlu dicatat (mis. halaman log itu sendiri).
    protected array $skipRouteNames = [
        'activity-logs.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName || in_array($routeName, $this->skipRouteNames, true)) {
            return $next($request);
        }

        $userBefore = $request->user();
        $exception = null;
        $response = null;

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $actingUser = $request->user() ?: $userBefore;
        [$module, $action] = $this->resolveModuleAction($routeName);

        $status = 'berhasil';
        $description = null;

        if ($exception) {
            $status = 'gagal';
            $description = 'Terjadi error: ' . $exception->getMessage();
        } elseif ($response) {
            if ($response->getStatusCode() >= 400) {
                $status = 'gagal';
            }

            if ($request->hasSession()) {
                $session = $request->session();
                $errorBag = $session->get('errors');

                if ($errorBag && method_exists($errorBag, 'any') && $errorBag->any()) {
                    $status = 'gagal';
                    $description = implode(' ', $errorBag->getBag('default')->all());
                } elseif ($session->has('error')) {
                    $status = 'gagal';
                    $description = (string) $session->get('error');
                } elseif ($session->has('success')) {
                    $description = (string) $session->get('success');
                }
            }
        }

        if (!$description) {
            $description = $this->describeRoute($routeName, $request);
        }

        $guestLabel = $routeName === 'login.attempt'
            ? (string) $request->input('email')
            : 'Publik';

        ActivityLog::create([
            'user_id' => $actingUser?->id,
            'user_name' => $actingUser?->name ?? $guestLabel,
            'module' => $module,
            'action' => $action,
            'status' => $status,
            'description' => $description ? Str::limit($description, 1000) : null,
            'method' => $request->method(),
            'route_name' => $routeName,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($exception) {
            throw $exception;
        }

        return $response;
    }

    private function resolveModuleAction(string $routeName): array
    {
        if (str_contains($routeName, '.')) {
            [$module, $action] = explode('.', $routeName, 2);

            return [$module, $action];
        }

        return [$routeName, '-'];
    }

    private function describeRoute(string $routeName, Request $request): string
    {
        return match (true) {
            $routeName === 'login.attempt' => 'Percobaan login untuk email: ' . (string) $request->input('email'),
            $routeName === 'logout' => 'Logout dari sistem.',
            default => strtoupper($request->method()) . ' pada ' . $routeName,
        };
    }
}
