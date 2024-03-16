<?php

namespace eiriksm\CosyComposerTest\integration;

use eiriksm\CosyComposer\CommandExecuter;

class OutputFromUpdateTest extends Base
{
    /**
     * Test that when we invoke the updater, the message log gets populated with the commands that are run.
     */
    public function testUpdateOutput()
    {
        $c = $this->cosy;
        $dir = $this->dir;
        $this->getMockOutputWithUpdate('eirik/private-pack', '1.0.0', '1.0.2');
        $this->placeComposerContentsFromFixture('composer-json-private.json', $dir);
        $mock_executer = $this->createMock(CommandExecuter::class);
        $mock_executer->method('executeCommand')
            ->willReturnCallback(function ($cmd) {
                $this->lastCommand = $cmd;
                return 0;
            });
        $this->ensureMockExecuterProvidesLastOutput($mock_executer);
        $c->setExecuter($mock_executer);
        $this->registerProviderFactory($c);
        $this->placeComposerLockContentsFromFixture('composer-lock-private.lock', $dir);
        $c->run();
        $this->assertOutputContainsMessage('Creating command composer update -n --no-ansi eirik/private-pack --with-dependencies', $c);
        $this->assertEquals(true, true);
    }
}
