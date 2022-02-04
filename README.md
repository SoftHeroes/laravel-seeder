# LaravelSeeder

Seeding as it is currently done in Laravel is intended only for dev builds, but what if you're iteratively creating your 
database and want to constantly flush it and repopulate it during development? 

What if you want to seed a production database with different data from what you use in development? What if you want to 
seed a table you've added to a database that is currently in production with new data?

LaravelSeeder takes the database migration features in Laravel and extends them to database seeders, making them "migratable". 
All of the functionality you have grown accustomed to with Laravel migrations have been mirrored and behave similarly for seeders.

Requirements
============

- Laravel >= 5.4
- PHP >= 7.1

Installation
============

- Run ```composer require softheroes/laravel-seeder```
- Add ```Eighty8\LaravelSeeder\SeederServiceProvider::class``` to your providers array in ```app/config/app.php```
- Run ```php artisan vendor:publish``` to push config files to your config folder if you want to override the name of the seeds folder or the name of the table where seeds are stored


Features
============

- Allows you to seed databases in different environments with different values.
- Allows you to "version" seeds the same way that Laravel currently handles migrations. Running ```php artisan seed``` will only run seeds that haven't already been run.
- Allows you to run multiple seeds of the same model/table
- Prompts you if your database is in production

Usage
============
When you install LaravelSeeder, various artisan commands are made available to you which use the same methodology you're used to using with Migrations.

<table>
<tr><td>seed</td><td>Runs all the seeds in the "seeders" directory that haven't been run yet.</td></tr>
<tr><td>seed:rollback</td><td>Rollback doesn't undo seeding (which would be impossible with an auto-incrementing primary key). It just allows you to re-run the last batch of seeds.</td></tr>
<tr><td>seed:reset</td><td>Resets all the seeds.</td></tr>
<tr><td>seed:refresh</td><td>Resets and re-runs all seeds.</td></tr>
<tr><td>seed:status</td><td>Gets the status of each migratable seeder.</td></tr>
<tr><td>seed:make</td><td>Makes a new seed class in the environment you specify.</td></tr>
<tr><td>seed:install</td><td>You don't have to use this... it will be run automatically when you call "seed"</td></tr>
</table>

Local Development
============
A Dockerfile with PHP 7.2, XDebug and Composer installed is bundled with the project to facilitate local development.

To easily bring up the local development environment, use the Docker Compose configuration:

```
docker-compose up -d --build
```

By default, the entrypoint script will install the Composer dependencies for you.

To run the test suite, execute the following:

```
docker-compose exec laravel-seeder test.sh
```

To run the code coverage suite, execute the following:
```
docker-compose exec laravel-seeder code-coverage.sh
```

Happy testing!
