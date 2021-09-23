<?php

namespace App\Command;

use App\Generator\AmzSDKGenerator;
use App\GitOperator;
use App\Utility;
use OpenAPI\Parser;
use OpenAPI\Schema\V2\Swagger;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class AmzGenerateSdkCommand extends Command
{
    protected static $defaultName = 'amz:generate-sdk';
    protected static $defaultDescription = 'Generate SDK for Amazon Marketplace';
    private GitOperator $gitOperator;

    public function __construct(GitOperator $gitOperator)
    {
        parent::__construct();
        $this->gitOperator = $gitOperator;
    }


    protected function configure(): void
    {
        $this
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'Git commit message', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $files  = $finder->files()->name('*.json')
            ->in(AmzDownloadSwaggerCommand::getExtractFolder() . '/models');

        foreach ($files as $file) {
            $this->generate($file, $input, $output);
        }

        return Command::SUCCESS;
    }

    private function generate(SplFileInfo $file, InputInterface $input, OutputInterface $output)
    {
        /** @var Swagger $swagger */
        $swagger = Parser::parse($file->getRealPath());
        $version = Utility::packagistVersionFilter($swagger->info->version);

        $sdkGenerator = new AmzSDKGenerator($file, $output);
        $gitOperator  = $this->gitOperator->init($file, $sdkGenerator->getDirector());
        $repo         = $gitOperator->checkout();

        $sdkGenerator->generate();

        if ($repo->hasChanges()) {
            $isFirstTimeCommit = false;
            if (in_array($version, (array)$repo->getTags())) {
                $repo->removeTag($version);
                $repo->push(null, ['--delete', 'origin', $version]);
            } else {
                $isFirstTimeCommit = true;
            }
            $repo->addAllChanges();
            $repo->commit($input->getOption('message') ?: 'Generated against swagger version ' . $version);
            $repo->createTag($version);
            if ($isFirstTimeCommit) {
                $repo->push(null, ['--set-upstream', 'origin', 'master']);
            }
            $repo->push('origin');
            $repo->push('origin', [$version]);
        }
    }
}
