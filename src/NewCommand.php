<?php

namespace Rareloop\Pebble\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Rareloop\Lumberjack\Installer\NewCommand as LumberjackNewCommand;

class NewCommand extends LumberjackNewCommand
{
    protected $description = 'Create a new Pebble project';

    protected $defaultFolderName = 'pebble-site';

    protected function install()
    {
        parent::install();

        $this->setupPrimer();
    }

    protected function getServiceProviders(): array
    {
        return array_merge(
            parent::getServiceProviders(),
            [
                'Rareloop\Lumberjack\Primer\PrimerServiceProvider::class',
            ]
        );
    }

    protected function getComposerDependencies(): array
    {
        return array_merge(
            parent::getComposerDependencies(),
            [
                'rareloop/lumberjack-core',
                'rareloop/lumberjack-primer:^1.0.0',
            ]
        );
    }

    protected function getTemplateLoadPaths(): array
    {
        return [
            'resources/patterns',
            'resources/templates',
        ];
    }

    protected function packagesToCheckForUpdates(): array
    {
        return array_merge(
            parent::packagesToCheckForUpdates(),
            [
                'rareloop/pebble-installer',
            ]
        );
    }

    protected function setupPrimer()
    {
        $this->output->writeln('<info>Installing Primer</info>');

        $this->upgradeLumberjackTemplatesToPrimer();
        $this->copyThemeAssets();
        $this->addTemplateLoadPaths();
        // $this->addPrimerAlias();
    }

    protected function upgradeLumberjackTemplatesToPrimer()
    {
        $this->output->writeln('<info>- Upgrade Lumberjack templates/Controllers for Primer compatibility</info>');

        $templates = [
            'posts',
            'home',
            'generic-page',
            'errors/404',
            'errors/whoops',
        ];

        $controllers = [
            '404.php',
            'archive.php',
            'author.php',
            'index.php',
            'page.php',
            'search.php',
            'single.php',
        ];

        $this->moveLumberjackTwigFiles($templates);
        $this->updateControllerTwigPaths($controllers);
        $this->updateExceptionHandler();
        $this->upgradeLumberjackTwigFilesContentBlocks($templates);

        rmdir($this->themeDirectory . '/views/templates/errors');
        rmdir($this->themeDirectory . '/views/templates');
    }

    protected function updateExceptionHandler()
    {
        $contents = file_get_contents($this->themeDirectory . '/app/Exceptions/Handler.php');

        $contents = str_replace('templates/errors/whoops.twig', 'errors/whoops', $contents);

        file_put_contents($this->themeDirectory . '/app/Exceptions/Handler.php', $contents);
    }

    protected function moveLumberjackTwigFiles(array $templates)
    {
        foreach ($templates as $template) {
            mkdir($this->themeDirectory . '/resources/templates/' . $template, 0777, true);
            rename($this->themeDirectory . '/views/templates/' . $template . '.twig', $this->themeDirectory . '/resources/templates/' . $template . '/template.twig');
        }
    }

    protected function updateControllerTwigPaths(array $controllers)
    {
        foreach ($controllers as $controller) {
            $contents = file_get_contents($this->themeDirectory . '/' . $controller);

            $contents = preg_replace('/TimberResponse\(\'templates\/(.*?)\.twig\'/', 'TimberResponse(\'$1\'', $contents);

            file_put_contents($this->themeDirectory . '/' . $controller, $contents);
        }
    }

    protected function upgradeLumberjackTwigFilesContentBlocks(array $templates)
    {
        foreach ($templates as $template) {
            $contents = file_get_contents($this->themeDirectory . '/resources/templates/' . $template . '/template.twig');

            $contents = str_replace('{% block content %}', '{% block templateContent %}', $contents);

            file_put_contents($this->themeDirectory . '/resources/templates/' . $template . '/template.twig', $contents);
        }
    }

    protected function copyThemeAssets()
    {
        $this->output->writeln('<info>- Adding Primer theme modifications</info>');

        $this->runCommands([
            'cp -r ' . escapeshellarg(__DIR__ . '/../theme/') . ' ' . escapeshellarg($this->themeDirectory),
        ]);
    }

    protected function addTemplateLoadPaths()
    {
        $paths = $this->getTemplateLoadPaths();

        if (empty($paths)) {
            return;
        }

        $this->output->writeln('<info>- Adding additional Twig load paths</info>');

        $configPath = $this->projectPath . '/web/app/themes/lumberjack/config/timber.php';

        $appConfig = file_get_contents($configPath);
        $paths = array_map(function ($path) {
            return "'{$path}'";
        }, $paths);

        $appConfig = str_replace("'paths' => [", "'paths' => [\n        " . implode(",\n        ", $paths) . ",", $appConfig);

        file_put_contents($configPath, $appConfig);
    }
}
