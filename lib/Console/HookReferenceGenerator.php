<?php

namespace Teak\Console;

use Symfony\Component\Filesystem\Filesystem;
use Teak\Compiler\FrontMatter\Yaml;
use Teak\Compiler\HookReference;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command used to extract markdown-formatted documentation from classes
 */
class HookReferenceGenerator extends ReferenceGenerator
{
    protected function configure()
    {
        parent::configure();

        $this->setName('generate:hook-reference');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $files
     */
    public function handleClassCollection($input, $output, $files)
    {
        $projectFactory = \phpDocumentor\Reflection\Php\ProjectFactory::createInstance();
        $fs             = new Filesystem();
        $contents       = '';
        $returns        = [];

        $project = $projectFactory->create('Teak', $files);

        // Get options
        $type         = $input->getOption(self::OPT_HOOK_TYPE);
        $outputFolder = $input->getOption(self::OPT_OUTPUT);

        // Make sure there’s a trailing slash
        $outputFolder = rtrim($outputFolder, '/') . '/';

        $types = array(
            'filter' => [
                'title' => 'Filter Hooks',
                'filename' => 'filters',
            ],
            'action' => [
                'title' => 'Action Hooks',
                'filename' => 'actions',
            ],
        );

        $title = $types[$type]['title'];

        $frontMatter = new Yaml($title, 'hooks');
        $contents .= $frontMatter->compile();

        foreach ($project->getFiles() as $file) {
            $hookReference = new HookReference($file);
            $hookReference->setHookPrefix('timber');
            $hookReference->setHookType($type);

            $contents .= $hookReference->compile();

            $returns[] = $contents;
        }

        $filename = $input->getOption(self::OPT_FILE_PREFIX) . $types[$type]['filename'] . '.md';
        $fs->dumpFile(getcwd() . '/' . $outputFolder . $filename, $contents);

        return $returns;
    }
}
