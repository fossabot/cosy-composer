<?php

namespace eiriksm\CosyComposerTest\integration\issues;

use eiriksm\CosyComposerTest\integration\ComposerUpdateIntegrationBase;

/**
 * Test for issue 164.
 */
class Issue164Test extends ComposerUpdateIntegrationBase
{
    protected $packageForUpdateOutput = 'psr/log';
    protected $packageVersionForFromUpdateOutput = '1.0.0';
    protected $packageVersionForToUpdateOutput = '1.1.3';
    protected $composerAssetFiles = 'composer164';

    public function testRequireDevAdded()
    {
        $this->runtestExpectedOutput();
        $this->assertOutputContainsMessage(
            'Creating command composer require --dev -n --no-ansi psr/log:1.1.3 --update-with-dependencies',
            $this->cosy
        );
    }
}
