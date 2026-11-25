<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Las pruebas de tests/Feature usan la clase Tests\TestCase.
|
*/

pest()->extend(Tests\TestCase::class)
    ->in('Feature');
