<?php

namespace eiriksm\CosyComposerTest\integration;

use Http\Adapter\Guzzle7\Client;
use Violinist\Slug\Slug;

class DrupalRuntimeSecUpdateTest extends ComposerUpdateIntegrationBase
{

    public function setUp() : void
    {
        parent::setUp();
        $this->cosy->setHttpClient(new Client());
    }

    /**
     * @dataProvider getDrupalUpdatesAndSec
     */
    public function testDrupalSecUpdates($version, $package, $sec)
    {
        $composer_contents = json_encode([
            'require' => [
                $package => $version,
            ],
        ]);
        $dir = $this->dir;
        $composer_file = "$dir/composer.json";
        file_put_contents($composer_file, $composer_contents);
        $this->getMockOutputWithUpdates($package, $version);
        $this->placeComposerLock($version, $package);
        $mock_executer = $this->getMockExecuterWithReturnCallback(
            function ($cmd) use ($package, $version) {
                $return = 0;
                $new_version = $this->incrementVersion($version);
                $expected_command = $this->createExpectedCommandForRequiredPackage($package, $new_version);
                if ($cmd == $expected_command) {
                    $this->placeUpdatedLockFile($version, $package);
                }
                $this->handleExecutorReturnCallback($cmd, $return);
                $this->lastCommand = $cmd;
                return $return;
            }
        );
        $this->ensureMockExecuterProvidesLastOutput($mock_executer);
        $this->cosy->setExecuter($mock_executer);
        $this->mockProvider->method('createPullRequest')
            ->willReturnCallback(function (Slug $slug, array $params) {
                $this->prParams = $params;
                return [
                    'html_url' => $this->fakePrUrl,
                ];
            });
        $this->runtestExpectedOutput();
        $output = $this->cosy->getOutput();
        self::assertEquals($sec, strpos($this->prParams["title"], '[SECURITY]') === 0);
    }

    protected function createExpectedCommandForRequiredPackage($package, $new_version)
    {
        return ["composer", "require", '-n', '--no-ansi', "$package:$new_version", '--update-with-dependencies'];
    }

    public function placeUpdatedLockFile($version, $package)
    {
        $new_version = $this->incrementVersion($version);
        $this->placeComposerLock($new_version, $package);
    }

    public function placeComposerLock($version, $package)
    {
        $lock_contents = json_encode([
            'packages-dev' => [],
            'packages' => [
                [
                    'name' => $package,
                    'version' => $version,
                ],
                [
                    'name' => 'drupal/core',
                    'version' => $version,
                ],
            ],
        ]);
        $dir = $this->dir;
        $lock_file = "$dir/composer.lock";
        file_put_contents($lock_file, $lock_contents);
    }

    protected function incrementVersion($version)
    {
        $version_array = explode('.', $version);
        $key = 2;
        if (empty($version_array[$key])) {
            $key = 1;
        }
        $version_array[$key] = (int) $version_array[$key] + 1;
        $new_version = implode('.', $version_array);
        return $new_version;
    }

    protected function getMockOutputWithUpdates($package, $version)
    {
        $new_version = $this->incrementVersion($version);
        $this->getMockOutputWithUpdate($package, $version, $new_version);
    }

    protected function placeInitialComposerLock()
    {
        // Empty on purpose, since we place those files more dynamically.
    }

    public function getDrupalUpdatesAndSec()
    {
        return [
            [
                '9.99.6',
                'drupal/core-recommended',
                false,
            ],
            [
                '9.1.2',
                'drupal/core-recommended',
                true,
            ],
            [
                '8.0.6',
                'drupal/core',
                true,
            ],
            [
                '8.10.6',
                'drupal/core',
                false,
            ],
            [
                '7.212',
                'drupal/core',
                false,
            ],
            [
                '7.0',
                'drupal/core',
                true,
            ],
            [
                '10.9.8',
                'drupal/core',
                false,
            ],
            [
                '70.55.86',
                'drupal/core',
                false,
            ],
        ];
    }
}
