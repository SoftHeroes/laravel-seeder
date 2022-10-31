<?php

namespace Eighty8\LaravelSeeder\Migration;

use Eighty8\LaravelSeeder\Repository\SeederRepositoryInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\ClassString;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionClass;

class SeederMigrator extends Migrator implements SeederMigratorInterface
{
    /**
     * The migration repository implementation.
     *
     * @var SeederRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The paths to all of the migration files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Create a new migrator instance.
     *
     * @param SeederRepositoryInterface   $repository
     * @param ConnectionResolverInterface $resolver
     * @param Filesystem                  $files
     */
    public function __construct(
        SeederRepositoryInterface $repository,
        ConnectionResolverInterface $resolver,
        Filesystem $files
    ) {
        $this->repository = $repository;
        $this->resolver = $resolver;
        $this->files = $files;
    }

    /**
     * Gets the environment the seeds are ran against.
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->repository->getEnvironment();
    }

    /**
     * Determines whether an environment has been set.
     *
     * @return bool
     */
    public function hasEnvironment(): bool
    {
        return $this->repository->hasEnvironment();
    }

    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnvironment(string $env): void
    {
        $this->repository->setEnvironment($env);
    }

    /**
     * Run "up" a seeder instance.
     *
     * @param string $file
     * @param int    $batch
     * @param bool   $pretend
     */
    protected function runUp($file, $batch, $pretend): void
    {
        // First we will resolve a "real" instance of the seeder class from this
        // seeder file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $seeder = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        $this->note("<comment>Seeding:</comment> {$name}");

        if ($pretend) {
            $this->pretendToRun($seeder, 'run');

            return;
        }

        $seeder->run();

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a seeder
        // in the application. A seeder repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Seeded:</info> $name");
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param string $file
     *
     * @return MigratableSeeder
     */
    public function resolve($file): MigratableSeeder
    {
        return parent::resolve($file);
    }

    /**
     * Reset the given migrations.
     *
     * @param array $migrations
     * @param array $paths
     * @param bool  $pretend
     *
     * @return array
     */
    protected function resetMigrations(array $migrations, array $paths, $pretend = false)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        //$resolvedMigrations = $this->resolvePath();
        $migrations = collect($migrations)->map(function ($m) {
            return (object) ['seed' => $m];
        })->all();

        return $this->rollbackMigrations(
            $migrations, $paths, compact('pretend')
        );
    }

    /**
     * Rollback the given migrations.
     *
     * @param array        $migrations
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        $rolledBack = $this->repository->getRan(); // TODO: populate the list in the foreach loop, figure out how to get the full file name
        foreach ($migrations as $migration) {
            // $rolledBack[] = $files[$migration->seed];
            $this->runMigration($migration, 'down');
        }
        return $rolledBack;
    }

    /**
     * Run "down" a seeder instance.
     *
     * @param string $file
     * @param object $migration
     * @param bool   $pretend
     *
     * @return void
     */
    protected function runDown($file, $migration, $pretend): void
    {
        // First we will get the file name of the seeder so we can resolve out an
        // instance of the seeder. Once we get an instance we can either run a
        // pretend execution of the seeder or we can run the real seeder.
        $seeder = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        $this->note("<comment>Rolling back:</comment> {$name}");

        if ($pretend) {
            $this->pretendToRun($seeder, 'down');

            return;
        }

        // Run "down" the seeder
        $seeder->down();

        // Once we have successfully run the seeder "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info>  {$name}");
    }

    /**
     * Resolve a migration instance from a migration path.
     *
     * @param  string  $path
     * @return object
     */
    public function resolvePath(string $path)
    {
        $test = $this->getMigrationName($path);
        $className = $this->getMigrationClass($test);
        $class = $this->getMigrationClass($this->getMigrationName($path));

        if (class_exists($class) && realpath($path) == (new ReflectionClass($class))->getFileName()) {
            return new $class;
        }

        $migration = $this->files->getRequire($path);

        return is_object($migration) ? $migration : new $class;
    }

    /**
     * Get the name of the migration.
     *
     * @param  string  $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }
    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param  object  $migration
     * @param  string  $method
     * @return void
     */
    public function runMigration($migration, $method)
    {
        $connection = $this->resolveConnection(
            $migration->getConnection()
        );

        $callback = function () use ($connection, $migration, $method) {
            if (method_exists($migration, $method)) {
                $this->fireMigrationEvent(new MigrationStarted($migration, $method));

                $this->runMethod($connection, $migration, $method);

                $this->fireMigrationEvent(new MigrationEnded($migration, $method));
            }
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
        && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int  $batch
     * @param  bool  $pretend
     * @return void
     */
    public function runNewUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

        if ($pretend) {
            return $this->pretendToRun($migration, 'run');
        }

        $this->note("<comment>Seeding:</comment> {$name}");

        $startTime = microtime(true);

        $this->runMigration($migration, 'run');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Seeded:</info>  {$name} ({$runTime}ms)");
    }

    public function runNewDown($file, $batch, $pretend){
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

        if ($pretend) {
            return $this->pretendToRun($migration, 'down');
        }

        $this->note("<comment>Rolling Back:</comment> {$name}");

        $startTime = microtime(true);

        $this->runMigration($migration, 'down');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $migration_for_rollback = $this->repository->getCurrent($name);

        $this->repository->delete($migration_for_rollback[0]);

        $this->note("<info>Seeded:</info>  {$name} ({$runTime}ms)");
    }
    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param  array|string  $paths
     * @param  bool  $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        $migrations = array_reverse($this->repository->getRan());
        foreach($this->files as $migration){
            $migr_objects[]=$this->resolvePath($migration);
        }
        $dummy='test';
        if (count($migrations) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->resetMigrations($migrations, $paths, $pretend);
    }
    public function newReset($files=[]){
        foreach($files as $file){
            $migration_objects[] = $this->resolvePath($file);
        }

        $this->rollbackMigrations($migration_objects, [],[]);
    }

}

