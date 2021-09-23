<?php

namespace App\Command;

use App\GitOperator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use ZipArchive;

class AmzDownloadSwaggerCommand extends Command
{
    public static string $tempFolder = __DIR__ . '/../../var/temp/amz';
    public static string $downloadURL = 'https://github.com/amzn/selling-partner-api-models/archive/refs/heads/main.zip';
    public static string $extractFolder = 'selling-partner-api-models-main';
    protected static $defaultName = 'amz:download-swagger';
    protected static $defaultDescription = 'Download Swagger file from GitHub';

    public static function getExtractFolder(): string
    {
        return static::$tempFolder . '/' . static::$extractFolder;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        if (!$fs->exists(static::$tempFolder)) {
            $fs->mkdir(static::$tempFolder);
        }

        $tempfile = static::$tempFolder . '/selling-parter-api-models.zip';
        if (!$this->download($io, $tempfile)) {
            return Command::FAILURE;
        }

        if (!$this->extract($io, $tempfile)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function download(StyleInterface $io, $tempfile): bool
    {
        $fs = new Filesystem();
        $io->note(sprintf('Downloading to %s', static::$tempFolder));

        $client   = HttpClient::create();
        $progress = null;
        $response = $client->request('GET', static::$downloadURL, [
            'on_progress' => function (int $dlNow, int $dlSize) use ($io, &$progress): void {
                if ($dlNow > 0 && is_null($progress)) {
                    $progress = $io->createProgressBar($dlSize);
                    $progress->start();
                }

                if (!is_null($progress)) {
                    if ($dlNow === $dlSize) {
                        $progress->finish();

                        return;
                    }
                    $progress->setProgress($dlNow);
                }
            }
        ]);

        $io->newLine();
        if (200 != $response->getStatusCode()) {
            $io->warning('Download failed');

            return false;
        }
        $fs->dumpFile($tempfile, $response->getContent());
        $io->success('Download finished!');

        return true;
    }

    private function extract(StyleInterface $io, $tempfile): bool
    {
        $zip = new ZipArchive();
        $zip->open($tempfile);
        $zip->extractTo(static::$tempFolder);
        $zip->close();

        $io->newLine();
        $io->success(sprintf('Successfully extracted into %s', realpath(static::$tempFolder)));

        return true;
    }
}
