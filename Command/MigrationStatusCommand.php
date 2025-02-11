<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MigrationStatusCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'propel:migration:status', description: 'Lists the migrations yet to be executed')]
class MigrationStatusCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setHelp(<<<EOT
The <info>propel:migration:status</info> command checks the version of the database structure, and looks for migration files not yet executed (i.e. with a greater version timestamp).

  <info>php app/console propel:migration:status</info>
EOT
            )
        ;
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
        $this->callPhing('status');

        $this->writeSummary($output, 'propel-migration-status');
        return 0;
    }
}
