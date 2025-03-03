<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Tests\Command;

use Propel\Bundle\PropelBundle\Command\FixturesLoadCommand;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class FixturesLoadCommandTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        $container = $this->getMockBuilder(\Symfony\Component\DependencyInjection\ContainerInterface::class)->getMock();

        $this->command = new TestableFixturesLoadCommand($container, 'testable-command');

        // let's create some dummy fixture files
        $this->fixturesDir   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'propel';
        $this->fixturesFiles = [
            '10_foo.yml', '20_bar.yml', '15_biz.yml', '18_boo.sql', '42_baz.sql'
        ];

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->fixturesDir);

        $this->fixturesDir = realpath($this->fixturesDir);

        foreach ($this->fixturesFiles as $file) {
            $this->filesystem->touch($this->fixturesDir . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function tearDown(): void
    {
        $this->filesystem->remove($this->fixturesDir);
    }

    public function testOrderedFixturesFiles()
    {
        $this->assertEquals(
            ['10_foo.yml', '15_biz.yml', '20_bar.yml',],
            $this->cleanFixtureIterator($this->command->getFixtureFiles('yml', $this->fixturesDir))
        );

        $this->assertEquals(
            ['18_boo.sql', '42_baz.sql',],
            $this->cleanFixtureIterator($this->command->getFixtureFiles('sql', $this->fixturesDir))
        );
    }

    protected function cleanFixtureIterator($file_iterator)
    {
        $tmpDir = realpath($this->fixturesDir);

        return array_map(fn($file) => str_replace($tmpDir . DIRECTORY_SEPARATOR, '', $file), array_values(iterator_to_array($file_iterator)));
    }
}

class TestableFixturesLoadCommand extends FixturesLoadCommand
{
    public function getFixtureFiles($type = 'sql', $in = null)
    {
        return parent::getFixtureFiles($type, $in);
    }
}
