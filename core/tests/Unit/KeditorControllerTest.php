<?php

use App\Http\Controllers\Dashboard\TopicsController;

test('keditor topic id parameter remains optional for optional route', function () {
    $method = new ReflectionMethod(TopicsController::class, 'keditor');
    $parameter = $method->getParameters()[0];

    expect($parameter->isOptional())->toBeTrue()
        ->and($parameter->getDefaultValue())->toBeNull();
});
