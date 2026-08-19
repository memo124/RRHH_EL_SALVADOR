<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:clean-ejercicio', function () {
    $this->call('db:seed', ['--class' => 'Database\\Seeders\\CleanTransactionalSeeder', '--force' => true]);
})->purpose('Vacía tablas transaccionales y conserva catálogos principales');
