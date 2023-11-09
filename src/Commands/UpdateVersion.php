<?php

namespace Orumad\LaravelAppVersion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UpdateVersion extends Command
{
    protected $signature = 'update:version
                            {message? : Commit and changelog message (optional)}
                            {--T|tag= : new app version number (optional)}
                            {--deploy : Merge changes to deploy branch}';
    protected $description = 'Updates the app version, the changelog and commit changes to git repository';

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
        $newVersion = $this->option('tag');
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
        if ($message = $this->argument('message')) {
            $this->_updateChangelog($newVersion, $message);
        } else {
            $message = '🚧 work in progress 🤗';
        }

        // Commit, tag and push
        exec("git add --all");
        exec("git commit -a -m \"$message\"");
        exec("git tag $newVersion");
        exec("git push origin main --tags");

        // Merge with deploy branch if '--deploy' option is present
        if ($this->option('deploy')) {
            exec("git checkout deploy");
            exec("git merge main");
            exec("git push origin deploy");
            exec("git checkout main");
            $this->info("Changes commited and pushed to deploy branch.");
        }

        $this->info("Version '$newVersion' updated and changes pushed to repository.");
    }

    private function _updateChangelog($newVersion, $message)
    {
        // Load the CHANGELOG content
        $changelogPath = 'CHANGELOG.md';
        $changelogContent = file_get_contents($changelogPath);

        // Prepare the message
        $message = str_replace('\n', PHP_EOL, $message);
        $newSection = "## $newVersion\n- $message\n\n";

        // Search the position od the first head in the CHANGELOG and insert the new message before it
        $firstHeaderPosition = strpos($changelogContent, '##');
        if ($firstHeaderPosition !== false) {
            $changelogContent = substr_replace($changelogContent, $newSection, $firstHeaderPosition, 0);
        } else {
            $changelogContent = $newSection . $changelogContent;
        }

        // Write the new CHANGELOG
        file_put_contents($changelogPath, $changelogContent);
    }
}
