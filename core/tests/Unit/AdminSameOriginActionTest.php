<?php

use App\Http\Middleware\AdminSameOriginAction;

function sameOriginRouteCheck(AdminSameOriginAction $middleware, string $routeName): bool
{
    $method = new ReflectionMethod($middleware, 'isStateChangingGetRoute');
    $method->setAccessible(true);

    return $method->invoke($middleware, $routeName);
}

test('same origin middleware allows legacy admin logout links without referer requirement', function () {
    $middleware = new AdminSameOriginAction();

    expect(sameOriginRouteCheck($middleware, 'adminLogout'))->toBeFalse();
});

test('same origin middleware still classifies protected destructive get routes', function () {
    $middleware = new AdminSameOriginAction();

    expect(sameOriginRouteCheck($middleware, 'BannersDestroy'))->toBeTrue()
        ->and(sameOriginRouteCheck($middleware, 'topicsClone'))->toBeTrue()
        ->and(sameOriginRouteCheck($middleware, 'cacheClear'))->toBeTrue();
});
