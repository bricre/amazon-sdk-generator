<?php

namespace App\Generator;

use App\TemplateUpdater\TemplateFileUpdater;
use Camel\CaseTransformer;
use Camel\Format\CamelCase;
use Camel\Format\SnakeCase;
use OpenAPI\CodeGenerator\Command\GenerateCommand;
use OpenAPI\CodeGenerator\Config;
use OpenAPI\Parser;
use SplFileInfo;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class AmzSDKGenerator
{
    private string $topNamespace = 'Amz';
    private string $directory;
    private SplFileInfo $file;
    private OutputInterface $output;
    private Filesystem $fs;

    public function __construct(SplFileInfo $file, OutputInterface $output)
    {
        $this->file      = $file;
        $this->output    = $output;
        $this->directory = __DIR__ . '/../../var/generated/' . $this->getModelName();
        $this->fs        = new Filesystem();

        if ($this->fs->exists($this->directory)) {
            $this->fs->remove($this->directory);
        }
        $this->output->writeln($this->getPackageName());
    }

    private function getModelName(): string
    {

        $modelName = implode('_', explode('-', $this->getPackageName()));

        $transformer = new CaseTransformer(new SnakeCase(), new CamelCase());

        return ucfirst($transformer->transform($modelName));
    }

    private function getPackageName(): string
    {
        $path      = $this->file->getPath();
        $directory = explode(DIRECTORY_SEPARATOR, $path)[count(explode(DIRECTORY_SEPARATOR, $path)) - 1];

        return str_replace('-api-model', '', $directory);
    }

    public function generate(): void
    {

        $this->initDirectory();
        $this->createConfigFile();
        $this->copyTemplateFiles();
        $this->runCodeGenerator();
        $this->composerUpdate();

        //Clear Config instance to later code generations will load fresh config
        Config::reset();
    }

    private function initDirectory(): void
    {
        $this->fs->mkdir($this->directory . '/src');
        $this->directory = realpath($this->directory);
    }

    private function createConfigFile(): void
    {
        $configFileGenerator = new ConfigFileGenerator($this->directory . '/src', $this->getNamespace());
        $configFileGenerator->generate($this->directory . '/.config.openapi-generator.php');
    }

    private function copyTemplateFiles(): void
    {
        $finder = new Finder();

        $templateFiles = $finder->files()->ignoreDotFiles(false)->in(__DIR__ . '/../../templates/');
        foreach ($templateFiles as $templateFile) {
            $this->fs->copy($templateFile->getRealPath(), $this->directory . '/' . $templateFile->getFilename(), true);
        }

        $swagger = Parser::parse($this->file->getRealPath());

        //Update composer.json
        $composerTemplateUpdater = new TemplateFileUpdater(
            $swagger,
            $this->getPackageName(),
            $this->getNamespace()
        );
        $composerTemplateUpdater->update(new SplFileInfo($this->directory . '/composer.json'));

        $readMeTemplateUpdater = new TemplateFileUpdater(
            $swagger,
            $this->getPackageName(),
            $this->getNamespace()
        );
        $readMeTemplateUpdater->update(new SplFileInfo($this->directory . '/README.md'));

    }

    private function runCodeGenerator()
    {
        $generateCommand = new GenerateCommand();
        $input           = new ArrayInput([
            '--input'  => $this->file->getRealPath(),
            '--config' => $this->directory . '/.config.openapi-generator.php'
        ]);
        $generateCommand->run($input, $this->output);
    }

    private function composerUpdate()
    {
        $process = new Process(['php', __DIR__ . '/../../vendor/bin/composer', '-d', $this->getDirector(), 'update']);
        $process->run();
    }

    private function getNamespace(): string
    {
        return $this->topNamespace . '\\' . $this->getModelName();
    }

    public function getDirector(): string
    {
        return $this->directory;
    }
}
