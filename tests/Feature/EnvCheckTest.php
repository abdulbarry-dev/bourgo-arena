<?php

test('is running unit tests', function () {
    expect(app()->runningUnitTests())->toBeTrue();
});
