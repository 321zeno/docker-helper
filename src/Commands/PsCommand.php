<?php
namespace DockerHelper\Commands;

use Closure;
use DockerHelper\ContainerInstance;
use InvalidArgumentException;

class PsCommand
{
    /**
     * @var array<ContainerInstance> $containers
     */
    protected array $containers = [];

    protected bool $all = false;

    /**
     * @var array<string> $psPlaceholders
     */
    protected array $psPlaceholders = [
        '.ID', // Container ID
        '.Image', // Image ID
        '.Command', // Quoted command
        '.CreatedAt', // Time when the container was created.
        '.RunningFor', // Elapsed time since the container was started.
        '.Ports', // Exposed ports.
        '.Status', // Container status.
        '.Size', // Container disk size.
        '.Names', // Container names.
        '.Labels', // All labels assigned to the container.
        '.Mounts', // Names of the volumes mounted in this container.
        '.Networks',
    ];

    /**
     * Returns the output of docker ps
     *
     * @return static
     */
    public function run() : self
    {
        $psCommand        = $this->psCommand();
        $output           = $this->psOutput($psCommand);
        $this->containers = $this->psOutputToContainersArray($output);

        return $this;
    }

    /**
     * @return array<ContainerInstance>
     */
    public function getContainers() : array
    {
        return $this->containers;
    }

    public function filter(Closure $filterBy) : self
    {
        $this->containers = array_filter($this->containers, $filterBy);

        return $this;
    }

    public function all() : self
    {
        $this->all = true;

        return $this;
    }

    /**
     * Returns the ps command as an array that can be supplied to Symfony\Process
     *
     * @return array<string>
     */
    public function psCommand() : array
    {
        $wrappedPsParams = array_map(fn($placeholder) => sprintf('{{%s}}', $placeholder), $this->psPlaceholders);
        $formatString = implode('||', $wrappedPsParams);
        $params = ['docker', 'ps', '--no-trunc', '--format', sprintf('"%s"', $formatString)];

        if ($this->all) {
            $params[] = '-a';
        }

        return $params;
    }

    /**
     * Retrieve the input of the `docker ps` command
     *
     * @param array<string> $psCommand
     * @return string
     */
    public function psOutput(array $psCommand) : string
    {
        return shell_exec(implode(' ', $psCommand));
    }

    /**
     * Converts the output of a ps command to an Array of containers
     *
     * @param string $psCommandOutput
     * @return array<ContainerInstance>
     */
    protected function psOutputToContainersArray(string $psCommandOutput) : array
    {
        $lines = explode("\n", $psCommandOutput);
        $lines = array_filter($lines);
        $keys  = $this->normalisedPsFormatParameters();
        
        $psArray = array_map(function ($line) use ($keys) {
            $lineArray = explode('||', $line);
            $attributes = array_combine($keys, $lineArray);

            return new ContainerInstance($attributes['id'], $attributes['names'], $attributes);
        }, $lines);

        return $psArray;
    }

    /**
     * Converts the PS format parameters into attribute names
     *
     * @return array<string>
     */
    protected function normalisedPsFormatParameters() : array
    {
        return array_map(function ($placeholder) {
            $placeholder = preg_replace('/\W/', '', $placeholder);
            $placeholder = strtolower($placeholder);
            return $placeholder;
        }, $this->psPlaceholders);
    }
}
