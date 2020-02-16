<?php
namespace DockerHelper\Commands;

use Closure;
use DockerHelper\ContainerInstance;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PsCommand
{
    protected $containers = [];

    protected $all = false;

    protected $psPlaceholders = [
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
     * @return array
     */
    public function run() : self
    {
        $psCommand        = $this->psCommand();
        $output           = $this->psOutput($psCommand);
        $this->containers = $this->psOutputToContainersArray($output);

        return $this;
    }

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
     * @return array
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

    public function psOutput(array $psCommand) : string
    {
        $process   = new Process($psCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Converts the output of a ps command to an Array of containers
     *
     * @param string $psCommandOutput
     * @return array
     */
    protected function psOutputToContainersArray(string $psCommandOutput) : array
    {
        $lines = explode(PHP_EOL, $psCommandOutput);
        // var_dump($lines); die();
        $lines = array_filter($lines);
        $keys  = $this->normalisedPsFormatParameters();
        
        $psArray = array_map(function ($line) use ($keys) {
            $lineArray = explode('||', $line);
            $attributes = array_combine($keys, $lineArray);

            return new ContainerInstance($attributes['id'], $attributes['names'], $attributes);
        }, $lines);

        return $psArray;
    }

    protected function normalisedPsFormatParameters() : array
    {
        return array_map(function ($placeholder) {
            $placeholder = preg_replace('/\W/', '', $placeholder);
            $placeholder = strtolower($placeholder);
            return $placeholder;
        }, $this->psPlaceholders);
    }
}
