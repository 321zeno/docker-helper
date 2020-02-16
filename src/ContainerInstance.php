<?php

namespace DockerHelper;

/**
 * Instance of a Docker container
 */
class ContainerInstance
{
    protected $id;

    protected $name;

    protected $attributes = [];

    private $arrayable = [
        'labels',
        'mounts',
        'networks',
    ];

    public function __construct(string $id, string $name = null, array $attributes = [])
    {
        $this->id = $id;
        $this->name = $name;

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function getHostPortByContainerPort(int $containerPort) : ?int
    {
        if (!is_array($this->attributes['ports'])) {
            return null;
        }

        $found = array_filter($this->attributes['ports'], function($mapping) use ($containerPort) {
            return (
                isset($mapping['host']) &&
                isset($mapping['docker']) &&
                $mapping['docker'] === $containerPort
            );
        });

        if (!$found) {
            return null;
        }

        return current($found)['host'];
    }

    public function __get($key)
    {
        if (!$key) {
            return;
        }
    
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getId() : string
    {
        return $this->id;
    }

    protected function setAttribute($key, $value) : void
    {
        if (in_array($key, $this->arrayable)) {
            $this->attributes[$key] = explode(',', $value);
            return;
        }

        if ($key === 'ports') {
            $this->attributes['ports'] = $this->setPorts($value);
            return;
        }

        $this->attributes[$key] = $value;
    }

    protected function setPorts($portsValue) : array
    {
        $mappings = array_filter(explode(',', $portsValue));
        $ports = [];

        foreach ($mappings as $mapping) {
            $ports[] = $this->parsePortMapping($mapping);
        }

        return $ports;
    }

    protected function parsePortMapping(string $portMapping) : ?array
    {
        $portMapping = preg_replace('/\s/', '', $portMapping);

        if (preg_match('/([\d\.]+):(\d+)->(\d+)\/([a-z]+)/', $portMapping, $matches)) {
            return [
                'bind'     => $matches[1],
                'host'     => (int) $matches[2],
                'docker'   => (int) $matches[3],
                'protocol' => $matches[4],
            ];
        }

        if (preg_match('/(\d+)->(\d+)\/([a-z]+)/', $portMapping, $matches)) {
            return [
                'host'     => (int) $matches[1],
                'docker'   => (int) $matches[2],
                'protocol' => $matches[3],
            ];
        }

        if (preg_match('/(\d+)\/([a-z]+)/', $portMapping, $matches)) {
            return [
                'docker'   => $matches[1],
                'protocol' => $matches[2],
            ];
        }

        return null;
    }
}