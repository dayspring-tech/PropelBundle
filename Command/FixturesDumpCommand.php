<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Command;

use Propel\Bundle\PropelBundle\DataFixtures\Dumper\YamlDataDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * FixturesDumpCommand.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'propel:fixtures:dump', description: 'Dump data from database into YAML fixtures file.')]
class FixturesDumpCommand extends AbstractCommand
{
    /**
     * Default fixtures directory.
     * @var string
     */
    private $defaultFixturesDir = 'app/propel/fixtures';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a fixture directory')
            ->setHelp(<<<EOT
The <info>propel:fixtures:dump</info> dumps data from database into YAML fixtures file.

  <info>php app/console propel:fixtures:dump</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The <info>--dir</info> parameter allows you to change the output directory.
The default connection is the active connection (propel.dbal.default_connection).
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
        [$name, $defaultConfig] = $this->getConnection($input, $output);
        $fixtureDir = $input->getOption('dir') ?: $this->defaultFixturesDir;

        $path = realpath($this->getApplication()->getKernel()->getProjectDir()) . '/' . $fixtureDir;

        if (!file_exists($path)) {
            $output->writeln("<info>The $path folder does not exists.</info>");
            if ($this->askConfirmation($output, "<question>Do you want me to create it for you ?</question> [Yes]")) {
                $fs = new Filesystem();
                $fs->mkdir($path);
            } else {
                throw new \IOException(sprintf('Unable to find the %s folder', $path));
            }
        }

        $filename = $path . '/fixtures_' . time() . '.yml';

        $dumper = new YamlDataDumper($this->getApplication()->getKernel()->getProjectDir() . '/app');

        try {
            $dumper->dump($filename, $name);
        } catch (\Exception $e) {
            $this->writeSection($output, [
                '[Propel] Exception',
                '',
                $e->getMessage()], 'fg=white;bg=red');

            return 1;
        }

        $this->writeNewFile($output, $filename);

        return 0;
    }
}
