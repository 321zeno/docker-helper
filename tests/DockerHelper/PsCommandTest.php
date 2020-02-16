<?php

namespace DockerHelper;

use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPsCommand()
    {
        $psCommand = new \DockerHelper\Commands\PsCommand;
        $command = $psCommand->psCommand();
        $this->assertEquals([
            'docker',
            'ps',
            '--no-trunc',
            '--format',
            '"{{.ID}}||{{.Image}}||{{.Command}}||{{.CreatedAt}}||{{.RunningFor}}||{{.Ports}}||{{.Status}}||{{.Size}}||{{.Names}}||{{.Labels}}||{{.Mounts}}||{{.Networks}}"',
        ], $command, 'Class outputs the expected command');
        
        $allCommand = $psCommand->all()->psCommand();
        $this->assertEquals([
            'docker',
            'ps',
            '--no-trunc',
            '--format',
            '"{{.ID}}||{{.Image}}||{{.Command}}||{{.CreatedAt}}||{{.RunningFor}}||{{.Ports}}||{{.Status}}||{{.Size}}||{{.Names}}||{{.Labels}}||{{.Mounts}}||{{.Networks}}"',
            '-a',
        ], $allCommand, 'Class outputs the expected command');
    }

    public function testPsCommandRun()
    {
        $psCommandMock = $this->getMockBuilder(\DockerHelper\Commands\PsCommand::class)
                              ->setMethods(['psOutput'])
                              ->getMock();
        $psCommandMock->method('psOutput')
                      ->willReturn(file_get_contents(__DIR__ . '/../stubs/docker_ps.txt'));
        $containers = $psCommandMock->run()->getContainers();

        $this->assertEquals('laradock_laravel-horizon_1', $containers[0]->getName(), 'Container Name OK');
        $this->assertEquals('2b60df10327d743d6cc50f5821156fd2ad31645b66b38aed087ce2c0606f1833', $containers[0]->getId(), 'Container Id OK');
    }
}