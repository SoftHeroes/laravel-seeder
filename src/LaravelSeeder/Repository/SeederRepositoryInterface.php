<?php

namespace Eighty8\LaravelSeeder\Repository;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;

interface SeederRepositoryInterface extends MigrationRepositoryInterface
{
    /**
     * Set the environment to run the seeds against.
     *
     * @param string $env
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

    public function getCurrent($name): array;

}
