<?php

namespace Eighty8\LaravelSeeder\Migration;

use Eighty8\LaravelSeeder\Repository\SeederRepositoryInterface;
use Illuminate\Console\OutputStyle;
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
use Psy\Readline\Hoa\ConsoleInput;
use ReflectionClass;
use Symfony\Component\Console\Output\ConsoleOutput;

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
     * @param SeederRepositoryInterface $repository
     * @param ConnectionResolverInterface $resolver
     * @param Filesystem $files
     */
    public function __construct(
        SeederRepositoryInterface   $repository,
        ConnectionResolverInterface $resolver,
        Filesystem                  $files
    )
    {
        $this->repository = $repository;
        $this->resolver = $resolver;
        $this->files = $files;
        $this->output = new ConsoleOutput();
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
     * @param int $batch
     * @param bool $pretend
     */
    public function runUp($file, $batch, $pretend): void
    {
        // First we will resolve a "real" instance of the seeder class from this
        // seeder file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.

        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

        if ($pretend) {
            $this->pretendToRun($migration, 'run');
        }
        $this->output->writeln("<comment>Seeding:</comment> {$name}");

        //$this->note("<comment>Seeding:</comment> {$name}");

        $startTime = microtime(true);

        $this->runMigration($migration, 'run');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.

        $this->repository->log($name, $batch);
        $this->output->writeln("<info>Seeded:</info>  {$name} ({$runTime}ms)");
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
     * Rollback the given migrations.
     *
     * @param array $migrations
     * @param array|string $paths
     * @param array $options
     *
     * @return array
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        $rolledBack = $this->repository->getRan(); // TODO: populate the list in the foreach loop, figure out how to get the full file name
        foreach ($migrations as $migration) {
            $class_basename = class_basename($migration);
            $reflection_class = new ReflectionClass($class_basename);
            $migration_filename = $reflection_class->getFileName();
            $this->output->writeln("<comment>Rolling Back:</comment> {$migration_filename}");
            $this->runMigration($migration, 'down');
        }
        return $rolledBack;
    }

    /**
     * Run "down" a seeder instance.
     *
     * @param string $file
     * @param object $migration
     * @param bool $pretend
     *
     * @return void
     */
    public function runDown($file, $migration, $pretend): void
    {
        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

        if ($pretend) {
            $this->pretendToRun($migration, 'down');
        }

        $this->output->writeln("<comment>Rolling Back:</comment> {$name}");

        $startTime = microtime(true);

        $this->runMigration($migration, 'down');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        $migration_for_rollback = $this->repository->getCurrent($name);

        $this->repository->delete($migration_for_rollback[0]);

        $this->output->writeln("<info>Seeded:</info>  {$name} ({$runTime}ms)");
    }

    /**
     * Resolve a migration instance from a migration path.
     *
     * @param string $path
     * @return object
     */
    public function resolvePath(string $path)
    {
        $test = $this->getMigrationName($path);
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
     * @param string $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param object $migration
     * @param string $method
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
     * Resets the currently applied migrations back.
     *
     * @param array|string $paths
     * @param bool $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $files = $paths;
        foreach ($files as $file) {
            $migration_objects[] = $this->resolvePath($file);
        }

        $this->rollbackMigrations($migration_objects, [], []);
    }
}

