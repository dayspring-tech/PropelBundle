<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildCommand.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'propel:build', description: 'Hub for Propel build commands (Model classes, SQL)')]
class BuildCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('classes', '', InputOption::VALUE_NONE, 'Build only classes'),
                new InputOption('sql', '', InputOption::VALUE_NONE, 'Build only SQL'),
                new InputOption('insert-sql', '', InputOption::VALUE_NONE, 'Build all and insert SQL'),
                new InputOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ]);
    }

    /**
     * @see Command
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('sql')) {
            $in = new ArrayInput([
                    'command'      => 'propel:model:build',
                    '--connection' => $input->getOption('connection'),
                    '--verbose'    => $input->getOption('verbose'),
            ]);
            $modelCommand = $this->getApplication()->find('propel:model:build');
            $res = $modelCommand->run($in, $output);
        }

        if (!$input->getOption('classes')) {
            $in = new ArrayInput([
                    'command'      => 'propel:build:sql',
                    '--connection' => $input->getOption('connection'),
                    '--verbose'    => $input->getOption('verbose'),
            ]);
            $sqlCommand = $this->getApplication()->find('propel:sql:build');
            $sqlCommand->run($in, $output);
        }

        if ($input->getOption('insert-sql')) {
            $in = new ArrayInput([
                    'command'      => 'propel:sql:insert',
                    '--connection' => $input->getOption('connection'),
                    '--verbose'    => $input->getOption('verbose'),
                    '--force'      => true,
            ]);
            $insertCommand = $this->getApplication()->find('propel:sql:insert');
            $insertCommand->run($in, $output);
        }
        return 0;
    }
}
