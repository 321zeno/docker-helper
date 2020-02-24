![Build and test](https://github.com/321zeno/docker-helper/workflows/Build%20and%20test/badge.svg)

# Docker Helper

Utility classes for working with Docker,

Only `docker ps` is available currently. It can be used to create a page that displays the containers available on the host.

Example usage:

```php
$dockerPs = new \DockerHelper\Commands\PsCommand;
// select nginx containers
$containers = $dockerPs->run()->filter(function($container) {
    return (preg_match('/nginx:/', $container->image));
})->getContainers();
```

Display the nginx containers
```php
<?php foreach ($containers as $container) : ?>
    Name: <?= $container->getName(); ?>
    Id: <?= $container->getId(); ?>
    HTTP: <?= $container->getHostPortByContainerPort(80); ?>
    HTTPS: <?= $container->getHostPortByContainerPort(443); ?>
<?php endforeach; ?>
```