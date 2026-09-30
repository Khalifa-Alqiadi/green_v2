<?php

use Illuminate\Routing\Route;

test('public route names methods uris and actions retain the frontend contract', function () {
    $expected = [
        'lang' => [['POST'], 'lang', 'App\\Http\\Controllers\\LanguageController@index'],
        'langChange' => [['GET', 'HEAD'], 'lang/{lang}', 'App\\Http\\Controllers\\LanguageController@change'],
        'localeChange' => [['GET', 'HEAD'], 'locale/{lang}', 'App\\Http\\Controllers\\LanguageController@locale'],
        'NotFound' => [['GET', 'HEAD'], '{lang?}/404', 'App\\Http\\Controllers\\HomeController@page_404'],
        'fileView' => [['GET', 'HEAD'], 'files/{path?}', 'App\\Http\\Controllers\\Dashboard\\FileController@view'],
        'fileDownload' => [['GET', 'HEAD'], 'downloads/{path?}', 'App\\Http\\Controllers\\Dashboard\\FileController@download'],
        'siteMap' => [['GET', 'HEAD'], 'sitemap.xml', 'App\\Http\\Controllers\\SiteMapController@siteMap'],
        'siteMapByLang' => [['GET', 'HEAD'], '{lang}/sitemap', 'App\\Http\\Controllers\\SiteMapController@siteMap'],
        'formSubmit' => [['POST'], 'form-submit', 'App\\Http\\Controllers\\HomeController@form_submit'],
        'subscribeSubmit' => [['POST'], 'subscribe', 'App\\Http\\Controllers\\HomeController@subscribe_submit'],
        'commentSubmit' => [['POST'], 'comment', 'App\\Http\\Controllers\\HomeController@comment_submit'],
        'orderSubmit' => [['POST'], 'order', 'App\\Http\\Controllers\\HomeController@order_submit'],
        'contactPageSubmit' => [['POST'], 'contact-submit', 'App\\Http\\Controllers\\HomeController@contact_submit'],
        'tagsListPage' => [['GET', 'HEAD'], 'tags', 'App\\Http\\Controllers\\HomeController@tags'],
        'tag' => [['GET', 'HEAD'], 'tag/{tag_slug?}', 'App\\Http\\Controllers\\HomeController@tag'],
        'HomeTopicsList' => [['GET', 'HEAD'], 'topics-list/{id?}/{cat?}/{count?}', 'App\\Http\\Controllers\\HomeController@topics_list'],
        'frontendRoute' => [['GET', 'HEAD'], '{part1?}/{part2?}/{part3?}/{part4?}/{part5?}/{part6?}', 'App\\Http\\Controllers\\HomeController@seo'],
    ];

    $routes = collect(app('router')->getRoutes()->getRoutes())->keyBy(fn (Route $route): ?string => $route->getName());

    foreach ($expected as $name => [$methods, $uri, $action]) {
        $route = $routes->get($name);

        expect($route)->toBeInstanceOf(Route::class)
            ->and($route->methods())->toBe($methods)
            ->and($route->uri())->toBe($uri)
            ->and($route->getActionName())->toBe($action);
    }
});

test('specific public routes remain registered before the frontend catch-all', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())->values();
    $catchAllIndex = $routes->search(fn (Route $route): bool => $route->getName() === 'frontendRoute');

    foreach (['siteMap', 'siteMapByLang', 'formSubmit', 'subscribeSubmit', 'commentSubmit', 'orderSubmit', 'contactPageSubmit', 'tagsListPage', 'tag', 'HomeTopicsList'] as $routeName) {
        $routeIndex = $routes->search(fn (Route $route): bool => $route->getName() === $routeName);

        expect($routeIndex)->toBeInt()->toBeLessThan($catchAllIndex);
    }
});
