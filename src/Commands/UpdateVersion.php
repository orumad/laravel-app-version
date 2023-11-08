<?php

namespace Orumad\LaravelAppVersion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UpdateVersion extends Command
{
    protected $signature = 'update:version {version? : Nueva versión (opcional)} {message? : Mensaje de commit (opcional)} {--deploy : Realizar merge a la rama deploy}';
    protected $description = 'Actualiza la versión, modifica CHANGELOG y realiza un commit en GIT';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Load the current version from 'package.json'
        $packageJson = json_decode(file_get_contents('package.json'), true);
        $currentVersion = Arr::get($packageJson, 'version', '1.0.0');
        $versionParts = explode('.', $currentVersion);

        // Get the version argument if any
        $newVersion = $this->argument('version');
        if (!$newVersion) {
            // There is not a version, so we update the build (the last) part by 1
            if (count($versionParts) === 1) {
                $newVersion = $currentVersion . '.0.1';
            } elseif (count($versionParts) === 2) {
                $newVersion = $currentVersion . '.1';
            } elseif (count($versionParts) === 3) {
                $versionParts[2] = (int)$versionParts[2] + 1;
                $newVersion = implode('.', $versionParts);
            }
        }

        // Update version in 'package.json' & 'composer.json' files
        $packageJson['version'] = $newVersion;
        file_put_contents('package.json', json_encode($packageJson, JSON_PRETTY_PRINT));
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        $composerJson['version'] = $newVersion;
        file_put_contents('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));

        // Add version and message to the 'CHANGELOG.md' file
        $message = $this->argument('message') ?: '🚧 work in progress 🤗';
        $changelogContent = "# $newVersion\n\n- $message\n\n" . file_get_contents('CHANGELOG.md');
        file_put_contents('CHANGELOG.md', $changelogContent);

        // Commit, tag and push
        exec("git add --all");
        exec("git commit -a -m '$message'");
        exec("git tag $newVersion");
        exec("git push origin --tags");

        // Merge with deploy branch if '--deploy' option is present
        if ($this->option('deploy')) {
            exec("git checkout deploy");
            exec("git merge master");
            exec("git push origin deploy");
            exec("git checkout main");
            $this->info("Changes commited and pushed to deploy branch.");
        }

        $this->info("Version '$newVersion' updated and changes pushed to repository.");
    }
}
