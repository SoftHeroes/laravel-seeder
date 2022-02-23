<?php

use Eighty8\LaravelSeeder\Command\SeedRun;
use Eighty8\LaravelSeeder\Command\SeedMake;
use Eighty8\LaravelSeeder\Command\SeedReset;
use Eighty8\LaravelSeeder\Command\SeedStatus;
use Eighty8\LaravelSeeder\Command\SeedInstall;
use Eighty8\LaravelSeeder\Command\SeedRefresh;
use Eighty8\LaravelSeeder\Command\SeedRollback;

return [

    /*
    |--------------------------------------------------------------------------
    | Default seeders table
    |--------------------------------------------------------------------------
    |
    | Do not change this! Unless you also change the included migration, since
    | this references the actual table in your database
    |
    */

    'table' => 'seeders',

    /*
    |--------------------------------------------------------------------------
    | Default seeders environment
    |--------------------------------------------------------------------------
    |
    | This option controls the default seeds environment.
    |
    */

    'env' => env('APP_ENV'),

    /*
    |--------------------------------------------------------------------------
    | Default seeders folder
    |--------------------------------------------------------------------------
    |
    | This option controls the default seeds folder.
    | We always use 1st path for seed:make command
    |
    */

    'dir' => [
        database_path('seeders')
    ],

    'commands' => [
        SeedInstall::class,
        SeedMake::class,
        SeedRefresh::class,
        SeedReset::class,
        SeedRollback::class,
        SeedRun::class,
        SeedStatus::class,
    ],
];
