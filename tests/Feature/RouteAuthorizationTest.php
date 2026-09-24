<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards against someone adding a route and forgetting to authorize it.
 */
class RouteAuthorizationTest extends TestCase
{
    /** Routes that only need a signed-in user: the dashboard and the user's own account. */
    private const AUTH_ONLY = [
        'dashboard',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'password.confirm',
        'password.update',
        'logout',
    ];

    #[Test]
    public function every_application_route_requires_authentication_and_a_policy_check(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith($route->getActionName(), 'App\\Http\\Controllers\\')
                && ! Str::startsWith($route->getActionName(), 'App\\Http\\Controllers\\Auth\\'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $name = $route->getName() ?? $route->uri();

            $this->assertContains('auth', $middleware, "Route [{$name}] is missing the auth middleware.");

            if (in_array($name, self::AUTH_ONLY, true)) {
                continue;
            }

            $this->assertTrue(
                collect($middleware)->contains(fn ($m) => is_string($m) && Str::startsWith($m, 'can:')),
                "Route [{$name}] has no policy (can:) middleware.",
            );
        }
    }
}
