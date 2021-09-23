<?php

namespace App;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use SplFileInfo;

class GitOperator
{
    private const GITHUB_ORG = 'bricre';
    private string $codeDirectory;
    private string $githubUsername;
    private string $githubToken;
    private SplFileInfo $swaggerFileInfo;
    private string $gitUserName;
    private string $gitUserEmail;

    private Git $git;

    public function __construct(string $githubUsername, string $githubToken, string $gitUserName, string $gitUserEmail)
    {
        $this->githubUsername = $githubUsername;
        $this->githubToken    = $githubToken;
        $this->gitUserName    = $gitUserName;
        $this->gitUserEmail   = $gitUserEmail;

        $this->git = new Git();

        $repo = $this->git->init(sys_get_temp_dir() . '/' . md5(microtime()));
        $repo->execute('config', ['--global user.email' => $this->gitUserEmail]);
        $repo->execute('config', ['--global user.name' => $this->gitUserName]);
    }


    public function init(SplFileInfo $swaggerFileInfo, string $codeDirectory): GitOperator
    {
        $this->swaggerFileInfo = $swaggerFileInfo;
        $this->codeDirectory   = $codeDirectory;

        return $this;
    }

    public function checkout(): GitRepository
    {
        $git = new Git();
        if (!$git->isRemoteUrlReadable($this->getGitUrl(true), ['master'])) {
            $repo = $git->init($this->codeDirectory);
            $repo->addRemote('origin', $this->getGitUrl(true));
        } else {
            $repo = $git->cloneRepository($this->getGitUrl(true), $this->codeDirectory);
        }

        return $repo;
    }

    private function getGitUrl(bool $isWithCredential = false): string
    {
        $path      = $this->swaggerFileInfo->getPath();
        $directory = explode(DIRECTORY_SEPARATOR, $path)[count(explode(DIRECTORY_SEPARATOR, $path)) - 1];

        $gitUrl = 'https://';
        if ($isWithCredential) {
            $gitUrl .= "{$this->githubUsername}:{$this->githubToken}@";
        }
        $gitUrl .= "github.com/" . self::GITHUB_ORG . '/amazon-selling-partner-sdk-' .
                   str_replace('-api-model', '', $directory) . '.git';

        return $gitUrl;
    }

}
