<?php

namespace DockerHelper;

use DateTime;

/**
 * Instance of a Docker container
 */
class ContainerInstance
{
    protected string $id;

    protected string $name;

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string>
     */
    private array $arrayable = [
        'labels',
        'mounts',
        'networks',
    ];

    /**
     * @var array<string>
     */
    private array $timestamps = [
        'createdat',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(string $id, string $name = null, array $attributes = [])
    {
        $this->id = $id;
        $this->name = (string) $name;

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function getHostPortByContainerPort(int $containerPort): ?int
    {
        if (! is_array($this->attributes['ports'])) {
            return null;
        }

        $found = array_filter($this->attributes['ports'], function ($mapping) use ($containerPort) {
            return isset($mapping['host']) &&
                isset($mapping['docker']) &&
                $mapping['docker'] === $containerPort
            ;
        });

        if (! $found) {
            return null;
        }

        return current($found)['host'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set an attribute
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    protected function setAttribute(string $key, $value): void
    {
        if (in_array($key, $this->arrayable, true)) {
            $this->attributes[$key] = explode(',', $value);
            return;
        }

        if (in_array($key, $this->timestamps, true)) {
            $this->attributes[$key] = DateTime::createFromFormat('Y-m-d H:i:s P T', $value);
            return;
        }

        if ($key === 'ports') {
            $this->attributes['ports'] = $this->setPorts($value);
            return;
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Parses the ports string into an array
     *
     * @return array<array|null>
     */
    protected function setPorts(string $portsValue): array
    {
        $mappings = array_filter(explode(',', $portsValue));
        $ports = [];

        foreach ($mappings as $mapping) {
            $ports[] = $this->parsePortMapping($mapping);
        }

        return $ports;
    }

    /**
     * @return array<string|int>|null
     */
    protected function parsePortMapping(string $portMapping): ?array
    {
        $portMapping = preg_replace('/\s/', '', $portMapping);

        if (preg_match('/([\d\.]+):(\d+)->(\d+)\/([a-z]+)/', (string) $portMapping, $matches)) {
            return [
                'bind' => $matches[1],
                'host' => (int) $matches[2],
                'docker' => (int) $matches[3],
                'protocol' => $matches[4],
            ];
        }

        if (preg_match('/(\d+)->(\d+)\/([a-z]+)/', (string) $portMapping, $matches)) {
            return [
                'host' => (int) $matches[1],
                'docker' => (int) $matches[2],
                'protocol' => $matches[3],
            ];
        }

        if (preg_match('/(\d+)\/([a-z]+)/', (string) $portMapping, $matches)) {
            return [
                'docker' => $matches[1],
                'protocol' => $matches[2],
            ];
        }

        return null;
    }
}
