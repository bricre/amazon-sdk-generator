<?php

namespace App\TemplateUpdater;

use App\Utility;
use OpenAPI\Schema\ObjectInterface;
use OpenAPI\Schema\V2\Swagger;
use OpenAPI\Schema\V3\OpenAPI;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

class TemplateFileUpdater
{
    private ObjectInterface $swagger;
    private string $package;
    private string $namespace;

    public function __construct(
        ObjectInterface $swagger,
        string $package,
        string $namespace
    ) {
        $this->swagger   = $swagger;
        $this->package   = $package;
        $this->namespace = $namespace;
    }

    public function update(SplFileInfo $templateFile)
    {
        $fs          = new Filesystem();
        $content     = file_get_contents($templateFile->getRealPath());
        $description = 'Selling Partner API';
        $version     = 'dev-master';

        if ($this->swagger instanceof Swagger) {
            $description = $this->swagger->info->title;
            $version     = $this->swagger->info->version;
        } elseif ($this->swagger instanceof OpenAPI) {
            $description = $this->swagger->info->title;
            $version     = $this->swagger->info->version;
        }

        $content = str_replace([
            '{package}',
            '{namespace}',
            '{escapedNamespace}',
            '{version}',
            '{packagistVersion}',
            '{description}'
        ], [
            $this->package,
            $this->namespace,
            addslashes($this->namespace),
            $version,
            Utility::packagistVersionFilter($version),
            $description
        ], $content);

        $fs->dumpFile($templateFile->getRealPath(), $content);
    }
}
