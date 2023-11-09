<?php

namespace Orumad\LaravelAppVersion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UpdateVersion extends Command
{
    protected $signature = 'update:version
                            {--T|tag= : new app version number (optional)}
                            {message? : Commit and changelog message (optional)}
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
        // $changelogContent = "# $newVersion\n\n- $message\n\n" . file_get_contents('CHANGELOG.md');
        // file_put_contents('CHANGELOG.md', $changelogContent);

        // Commit, tag and push
        exec("git add --all");
        exec("git commit -a -m '$message'");
        exec("git tag $newVersion");
        exec("git push origin main --tags");

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

    private function _updateChangelog($newVersion, $message)
    {
        // Leer el contenido actual del CHANGELOG
        $changelogPath = 'CHANGELOG.md';
        $changelogContent = file_get_contents($changelogPath);

        // Crear la nueva sección
        $newSection = "## $newVersion\n- $message\n\n";

        // Buscar la posición del primer encabezado (título) en el CHANGELOG
        $firstHeaderPosition = strpos($changelogContent, '##');
        if ($firstHeaderPosition !== false) {
            // Insertar la nueva sección debajo del título y antes de las secciones anteriores
            $changelogContent = substr_replace($changelogContent, $newSection, $firstHeaderPosition, 0);
        } else {
            // Si no se encuentra un título, agregar la nueva sección al principio del CHANGELOG
            $changelogContent = $newSection . $changelogContent;
        }

        // Escribir el contenido modificado en el archivo CHANGELOG
        file_put_contents($changelogPath, $changelogContent);
    }
}
