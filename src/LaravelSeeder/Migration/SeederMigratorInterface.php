<?php

namespace Eighty8\LaravelSeeder\Migration;

use Eighty8\LaravelSeeder\Repository\SeederRepositoryInterface;

interface SeederMigratorInterface
{
    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnvironment(string $env): void;

    /**
     * Gets the environment the seeds are ran against.
     *
     * @return string|null
     */
    public function getEnvironment(): ?string;

    /**
     * Determines whether an environment has been set.
     *
     * @return bool
     */
    public function hasEnvironment(): bool;

    /**
     * Run the pending migrations at a given path.
     *
     * @param array $paths
     * @param array $options
     *
     * @return array
     */
    public function run($paths = [], array $options = []);

    /**
     * Run an array of migrations.
     *
     * @param array $migrations
     * @param array $options
     */
    public function runPending(array $migrations, array $options = []);

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param array|string $paths
     * @param bool         $pretend
     *
     * @return array
     */
    public function reset($paths = [], $pretend = false);

    /**
     * Rollback the last migration operation.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     */
    public function rollback($paths = [], array $options = []);

    /**
     * Resolve a migration instance from a file.
     *
     * @param string $file
     *
     * @return MigratableSeeder
     */
    public function resolve($file): MigratableSeeder;

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setConnection($name);

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists();

    /**
     * Get the seeder repository instance.
     *
     * @return SeederRepositoryInterface
     */
    public function getRepository();

    /**
     * Get the name of the migration.
     *
     * @param string $path
     *
     * @return string
     */
    public function getMigrationName($path);

    /**
     * Get all of the migration files in a given path.
     *
     * @param string|array $paths
     *
     * @return array
     */
    public function getMigrationFiles($paths);
}
