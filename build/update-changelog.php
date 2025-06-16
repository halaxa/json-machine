<?php

declare(strict_types=1);

$version = $argv[1];
$changelogPath = $argv[2];

$changelogContents = file_get_contents($changelogPath);
if (false !== strpos($changelogContents, $version)) {
    echo "Version $version already in $changelogPath. Stopping.";
    exit(1);
}

$changelogContents = addReleaseHeading($changelogContents, $version);
$changelogContents = linkifyUsernames($changelogContents);
$changelogContents = linkifyIssues($changelogContents);

file_put_contents($changelogPath, $changelogContents);



function addReleaseHeading(string $changelogContents, $version): string
{
    $releaseDate = date('Y-m-d');
    $changelogMatch = '## master';
    $changelogReplace = "$changelogMatch
Nothing yet

<br>

## $version - $releaseDate";

    return str_replace($changelogMatch, $changelogReplace, $changelogContents);
}

function linkifyUsernames(string $changelogContents): string
{
    return preg_replace(
        '/([^\[]\s*)@([a-zA-Z-]+)(\s*[^\]])/',
        '$1[@$2](https://github.com/$2)$3',
        $changelogContents
    );
}

function linkifyIssues(string $changelogContents): string
{
    return preg_replace(
        '/([^\[]\s*)#(\d+)(\s*[^\]])/',
        '$1[#$2](https://github.com/halaxa/json-machine/issues/$2)$3',
        $changelogContents
    );
}
