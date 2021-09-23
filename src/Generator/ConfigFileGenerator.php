<?php

namespace App\Generator;

use Laminas\Code\Generator\BodyGenerator;
use Laminas\Code\Generator\FileGenerator;

class ConfigFileGenerator
{

    private string $rootSourceDir;
    private string $namespaceRoot;

    public function __construct(string $rootSourceDir, string $namespaceRoot)
    {

        $this->rootSourceDir = $rootSourceDir;
        $this->namespaceRoot = $namespaceRoot;
    }

    public function generate($file)
    {
        $fileGenerator = new FileGenerator();
        $bodyGenerator = new BodyGenerator();

        $fileGenerator->setFilename($file);

        $fileGenerator->setUse('OpenAPI\CodeGenerator\Config');
        $bodyGenerator->setContent(
            'return [' . PHP_EOL .
            'Config::OPTION_ROOT_SOURCE_DIR => \'' . $this->rootSourceDir . '\',' . PHP_EOL .
            'Config::OPTION_NAMESPACE_ROOT => \'' . $this->namespaceRoot . '\',' . PHP_EOL .
            '];');
        $fileGenerator->setBody($bodyGenerator->generate());

        $fileGenerator->write();
    }
}
