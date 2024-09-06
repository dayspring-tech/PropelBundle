<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;

/**
 * Wrapper for Propel commands.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * Additional Phing args to add in specialized commands.
     * @var array
     */
    protected $additionalPhingArgs = array();

    /**
     * Temporary XML schemas used on command execution.
     * @var array
     */
    protected $tempSchemas = array();

    /**
     * @var string
     */
    protected $cacheDir = null;

    /**
     * The Phing output.
     * @string
     */
    protected $buffer = null;

    /**
     * @var Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    protected $bundle = null;

    /**
     * @var Boolean
     */
    private $alreadyWroteConnection = false;

    /**
     *
     * @var InputInterface
     */
    protected $input;

    /**
     *
     * @var OutputInterface
     */
    protected $output;

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, $name = null)
    {
        $this->container = $container;

        parent::__construct($name);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Return the package for a given bundle.
     *
     * @param Bundle $bundle
     * @param string $baseDirectory The base directory to exclude from prefix.
     *
     * @return string
     */
    protected function getPackage(Bundle $bundle, $namespace = '', $baseDirectory = '')
    {
        $path  = explode(DIRECTORY_SEPARATOR, realpath($bundle->getPath()));
        $bundle_namespace = explode('\\', $bundle->getNamespace());

        $diff = array_diff($bundle_namespace, $path);

        if (empty($diff)) {
            // PSR-0
            $length = count($bundle_namespace) * (-1);

            $package = implode(
                DIRECTORY_SEPARATOR,
                array_merge(
                    array_slice($path, 0, $length),
                    explode('\\', $namespace)
                )
            );
        } else {
            // PSR-4
            $ns = explode('\\', $namespace);

            $diff = array_diff($ns, $bundle_namespace);

            $package = implode(
                DIRECTORY_SEPARATOR,
                array_merge($path, $diff)
            );
        }

        $package = ltrim(str_replace($baseDirectory, '', $package), DIRECTORY_SEPARATOR);

        if (!empty($package)) {
            $package = str_replace(DIRECTORY_SEPARATOR, '.', $package);
        }

        return $package;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($input->getOption('verbose')) {
            $this->additionalPhingArgs[] = 'verbose';
        }

        $this->input = $input;
        $this->output = $output;

        $this->checkConfiguration();

        if ($input->hasArgument('bundle') && $input->getArgument('bundle')) {
            $bundleName = $input->getArgument('bundle');
            if (0 === strpos($bundleName, '@')) {
                $bundleName = substr($bundleName, 1);
            }

            $this->bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);
        }
    }

    /**
     * Call a Phing task.
     *
     * @param string $taskName   A Propel task name.
     * @param array  $properties An array of properties to pass to Phing.
     */
    protected function callPhing($taskName, $properties = array())
    {
        $kernel = $this->getApplication()->getKernel();

        if (isset($properties['propel.schema.dir'])) {
            $this->cacheDir = $properties['propel.schema.dir'];
        } else {
            $this->cacheDir = $kernel->getCacheDir().'/propel';

            $filesystem = new Filesystem();
            $filesystem->remove($this->cacheDir);
            $filesystem->mkdir($this->cacheDir);
        }

        $this->copySchemas($kernel, $this->cacheDir);

        // build.properties
        $this->createBuildPropertiesFile($kernel, $this->cacheDir.'/build.properties');

        // buildtime-conf.xml
        $this->createBuildTimeFile($this->cacheDir.'/buildtime-conf.xml');

        // Verbosity
        $bufferPhingOutput = $this->getContainer()->getParameter('kernel.debug');

        // Phing arguments
        $args = $this->getPhingArguments($kernel, $this->cacheDir, $properties);

        // Add any arbitrary arguments last
        foreach ($this->additionalPhingArgs as $arg) {
            if (in_array($arg, array('verbose', 'debug'))) {
                $bufferPhingOutput = false;
            }

            $args[] = '-'.$arg;
        }

        $args[] = $taskName;

        // Enable output buffering
        Phing::setOutputStream(new \OutputStream(fopen('php://output', 'w')));
        Phing::setErrorStream(new \OutputStream(fopen('php://output', 'w')));
        Phing::startup();
        Phing::setProperty('phing.home', getenv('PHING_HOME'));

        ob_start();

        $phing = new Phing();
        $returnStatus = true; // optimistic way

        try {
            $phing->execute($args);
            $phing->runBuild();

            $this->buffer = ob_get_contents();

            // Guess errors
            if (strstr($this->buffer, 'failed. Aborting.') ||
                strstr($this->buffer, 'Failed to execute') ||
                strstr($this->buffer, 'failed for the following reason:')) {
                $returnStatus = false;
            }
        } catch (\Exception $e) {
            $returnStatus = false;
        }

        if ($bufferPhingOutput) {
            ob_end_clean();
        } else {
            ob_end_flush();
        }

        return $returnStatus;
    }

    /**
     * @param KernelInterface $kernel The application kernel.
     */
    protected function copySchemas(KernelInterface $kernel, $cacheDir)
    {
        $filesystem = new Filesystem();

        $base = ltrim(realpath($kernel->getProjectDir()), DIRECTORY_SEPARATOR);

        /** @var array<string, array{?BundleInterface, \SplFileInfo}> $finalSchemas */
        $finalSchemas = $this->getFinalSchemas($kernel, $this->bundle);
        foreach ($finalSchemas as $schema) {
            list($bundle, $finalSchema) = $schema;

            if ($bundle) {
                $file = $cacheDir.DIRECTORY_SEPARATOR.'bundle-'.$bundle->getName().'-'.$finalSchema->getBaseName();
            } else {
                $file = $cacheDir.DIRECTORY_SEPARATOR.'app-'.$finalSchema->getBaseName();
            }

            $filesystem->copy((string) $finalSchema, $file, true);

            // the package needs to be set absolute
            // besides, the automated namespace to package conversion has
            // not taken place yet so it needs to be done manually
            $database = simplexml_load_file($file);

            if (isset($database['package'])) {
                // Do not use the prefix!
                // This is used to override the package resulting from namespace conversion.
                $database['package'] = $database['package'];
            } elseif (isset($database['namespace'])) {
                if ($bundle) {
                    $database['package'] = $this->getPackage($bundle, (string)$database['namespace'], $base);
                } else {
                    $database['package'] = $this->getPackageFromApp((string)$database['namespace']);
                }
            } else {
                throw new \RuntimeException(
                    sprintf(
                        '%s : Please define a `package` attribute or a `namespace` attribute for schema `%s`',
                        $bundle ? $bundle->getName() : 'App',
                        $finalSchema->getBaseName()
                    )
                );
            }

            if ($this->input && $this->input->hasOption('connection') && $this->input->getOption('connection')
                && $database['name'] != $this->input->getOption('connection')) {
                // we skip this schema because the connection name doesn't match the input values
                $filesystem->remove($file);
                $this->output->writeln(sprintf(
                    '<info>Skipped schema %s due to database name missmatch (%s not in [%s]).</info>',
                    $finalSchema->getPathname(),
                    $database['name'],
                    $this->input->getOption('connection')
                ));
                continue;
            }

            foreach ($database->table as $table) {
                if (isset($table['package'])) {
                    $table['package'] = $table['package'];
                } elseif (isset($table['namespace'])) {
                    if ($bundle) {
                        $table['package'] = $this->getPackage($bundle, (string)$table['namespace']);
                    } else {
                        $table['package'] = $this->getPackageFromApp((string)$table['namespace']);
                    }
                } elseif (isset($database['package'])) {
                    $table['package'] = $database['package'];
                }
            }

            file_put_contents($file, $database->asXML());
        }
    }

    /**
     * Return a list of final schema files that will be processed.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     *
     * @return array
     */
    protected function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        if (null !== $bundle) {
            return $this->getSchemaLocator()->locateFromBundle($bundle);
        }

        return $this->getSchemaLocator()->locateFromBundlesAndConfiguration($kernel->getBundles());
    }

    /**
     * @param  \SplFileInfo $file
     * @return string
     */
    protected function getRelativeFileName(\SplFileInfo $file)
    {
        return substr(str_replace(realpath($this->getContainer()->getParameter('kernel.project_dir')), '', $file), 1);
    }

    /**
     * Create a 'build.properties' file.
     *
     * @param KernelInterface $kernel The application kernel.
     * @param string          $file   Should be 'build.properties'.
     */
    protected function createBuildPropertiesFile(KernelInterface $kernel, $file)
    {
        $filesystem = new Filesystem();
        $buildPropertiesFile = $kernel->getProjectDir().'/app/config/propel.ini';

        if (file_exists($buildPropertiesFile)) {
            $filesystem->copy($buildPropertiesFile, $file);
        } else {
            $filesystem->touch($file);
        }
    }

    /**
     * Create an XML file which represents propel.configuration
     *
     * @param string $file Should be 'buildtime-conf.xml'.
     */
    protected function createBuildTimeFile($file)
    {
        $container = $this->getContainer();

        if (!$container->has('propel.configuration')) {
            throw new \InvalidArgumentException('Could not find Propel configuration.');
        }

        $xml = strtr(<<<EOT
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="%default_connection%">

EOT
        , array('%default_connection%' => $container->getParameter('propel.dbal.default_connection')));

        $propelConfiguration = $container->get('propel.configuration');
        foreach ($propelConfiguration['datasources'] as $name => $datasource) {
            if (is_scalar($datasource)) {
                continue;
            }

            $xml .= strtr(<<<EOT
      <datasource id="%name%">
        <adapter>%adapter%</adapter>
        <connection>
          <classname>%classname%</classname>
          <dsn>%dsn%</dsn>
          <user>%username%</user>
          <password>%password%</password>
        </connection>
      </datasource>

EOT
            , array(
                '%name%'     => $name,
                '%adapter%'  => $datasource['adapter'],
                '%classname%'=> $datasource['connection']['classname'],
                '%dsn%'      => $datasource['connection']['dsn'],
                '%username%' => $datasource['connection']['user'],
                '%password%' => isset($datasource['connection']['password']) ? $datasource['connection']['password'] : '',
            ));
        }

        $xml .= <<<EOT
    </datasources>
  </propel>
</config>
EOT;

        file_put_contents($file, $xml);
    }

    /**
     * Returns an array of properties as key/value pairs from an input file.
     *
     * @param  string $file A file properties.
     * @return array  An array of properties as key/value pairs.
     */
    protected function getProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new \Exception(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' == $line || in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos      = strpos($line, '=');
            $property = trim(substr($line, 0, $pos));
            $value    = trim(substr($line, $pos + 1));

            if ("true" === $value) {
                $value = true;
            } elseif ("false" === $value) {
                $value = false;
            }

            $properties[$property] = $value;
        }

        return $properties;
    }

    /**
     * @param string $namespace
     *
     * @return string
     */
    protected function getPackageFromApp(string $namespace): string
    {
        if ('\\' === $namespace[0]) {
            $namespace = substr($namespace, 1);
        }

        if (0 === stripos($namespace, 'App\\')) {
            $namespace = substr($namespace, 4);
        }

        return 'src.'.str_replace('\\', '.', $namespace);
    }

    /**
     * Return the current Propel cache directory.
     * @return string The current Propel cache directory.
     */
    protected function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @return \Symfony\Component\Config\FileLocatorInterface
     */
    protected function getFileLocator()
    {
        return $this->getContainer()->get('propel.file_locator');
    }

    /**
     * @return SchemaLocator
     */
    protected function getSchemaLocator(): SchemaLocator
    {
        /** @var SchemaLocator $obj */
        $obj = $this->getContainer()->get('propel.schema_locator');

        return $obj;
    }

    /**
     * Get connection by checking the input option named 'connection'.
     * Returns the default connection if no option specified or an exception
     * if the specified connection doesn't exist.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throw \InvalidArgumentException If the connection does not exist.
     * @return array
     */
    protected function getConnection(InputInterface $input, OutputInterface $output)
    {
        $propelConfiguration = $this->getContainer()->get('propel.configuration');
        $name = $input->getOption('connection') ?: $this->getContainer()->getParameter('propel.dbal.default_connection');

        if (isset($propelConfiguration['datasources'][$name])) {
            $defaultConfig = $propelConfiguration['datasources'][$name];
        } else {
            throw new \InvalidArgumentException(sprintf('Connection named %s doesn\'t exist', $name));
        }

        if (false === $this->alreadyWroteConnection) {
            $output->writeln(sprintf('Use connection named <comment>%s</comment> in <comment>%s</comment> environment.',
                $name, $this->getApplication()->getKernel()->getEnvironment())
            );
            $this->alreadyWroteConnection = true;
        }

        // prevent errors
        if (!isset($defaultConfig['connection']['password'])) {
            $defaultConfig['connection']['password'] = null;
        }

        return array($name, $defaultConfig);
    }

    /**
     * Extract the database name from a given DSN
     *
     * @param  string $dsn A DSN
     * @return string The database name extracted from the given DSN
     */
    protected function parseDbName($dsn)
    {
        preg_match('#dbname=([a-zA-Z0-9\_]+)#', $dsn, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        // e.g. SQLite
        return null;
    }

    /**
     * Check the PropelConfiguration object.
     */
    protected function checkConfiguration()
    {
        $parameters = $this->getContainer()->get('propel.configuration')->getParameters();

        if (!isset($parameters['datasources']) || 0 === count($parameters['datasources'])) {
            throw new \RuntimeException('Propel should be configured (no database configuration found).');
        }
    }

    /**
     * Write Propel output as summary based on a Regexp.
     *
     * @param OutputInterface $output   The output object.
     * @param string          $taskname A task name
     */
    protected function writeSummary(OutputInterface $output, $taskname)
    {
        foreach (explode("\n", $this->buffer) as $line) {
            if (false !== strpos($line, '[' . $taskname . ']')) {
                $arr  = preg_split('#\[' . $taskname . '\] #', $line);
                $info = $arr[1];

                if ('"' === $info[0]) {
                    $info = sprintf('<info>%s</info>', $info);
                }

                $output->writeln($info);
            }
        }
    }

    /**
     * Comes from the SensioGeneratorBundle.
     * @see https://github.com/sensio/SensioGeneratorBundle/blob/master/Command/Helper/DialogHelper.php#L52
     *
     * @param OutputInterface $output The output.
     * @param string          $text   A text message.
     * @param string          $style  A style to apply on the section.
     */
    protected function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }

    /**
     * Renders an error message if a task has failed.
     *
     * @param OutputInterface $output   The output.
     * @param string          $taskName A task name.
     * @param Boolean         $more     Whether to add a 'more details' message or not.
     */
    protected function writeTaskError($output, $taskName, $more = true)
    {
        $moreText = $more ? ' To get more details, run the command with the "--verbose" option.' : '';

        return $this->writeSection($output, array(
            '[Propel] Error',
            '',
            'An error has occured during the "' . $taskName . '" task process.' . $moreText
        ), 'fg=white;bg=red');
    }

    /**
     * @param OutputInterface $output   The output.
     * @param string          $filename The filename.
     */
    protected function writeNewFile(OutputInterface $output, $filename)
    {
        $output->writeln('>>  <info>File+</info>    ' . $filename);
    }

    /**
     * @param OutputInterface $output    The output.
     * @param string          $directory The directory.
     */
    protected function writeNewDirectory(OutputInterface $output, $directory)
    {
        $output->writeln('>>  <info>Dir+</info>     ' . $directory);
    }

    /**
     * Ask confirmation from the user.
     *
     * @param OutputInterface $output   The output.
     * @param string          $question A given question.
     * @param string          $default  A default response.
     */
    protected function askConfirmation(OutputInterface $output, $question, $default = null)
    {
        return $this->getHelper('question')->ask($this->input, $output, new ConfirmationQuestion($question, $default));
    }

    /**
     * @param  \SplFileInfo    $schema
     * @param  BundleInterface $bundle
     * @return string
     */
    protected function transformToLogicalName(\SplFileInfo $schema, BundleInterface $bundle)
    {
        $schemaPath = str_replace(
            $bundle->getPath(). DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR,
            '',
            $schema->getRealPath()
        );

        return sprintf('@%s/Resources/config/%s', $bundle->getName(), $schemaPath);
    }

    /**
     * Compiles arguments/properties for the Phing process.
     * @return array
     */
    private function getPhingArguments(KernelInterface $kernel, $workingDirectory, $properties)
    {
        $args = array();

        // Default properties
        $properties = array_merge(array(
            'propel.database'           => 'mysql',
            'project.dir'               => $workingDirectory,
            'propel.output.dir'         => $kernel->getProjectDir().'/app/propel',
            'propel.php.dir'            => $kernel->getProjectDir(),
            'propel.packageObjectModel' => true,
            'propel.useDateTimeClass'   => true,
            'propel.dateTimeClass'      => 'DateTime',
            'propel.defaultTimeFormat'  => '',
            'propel.defaultDateFormat'  => '',
            'propel.addClassLevelComment'       => false,
            'propel.defaultTimeStampFormat'     => '',
            'propel.builder.pluralizer.class'   => 'builder.util.StandardEnglishPluralizer',
        ), $properties);

        // Adding user defined properties from the configuration
        $properties = array_merge(
            $properties,
            $this->getContainer()->get('propel.build_properties')->getProperties()
        );

        foreach ($properties as $key => $value) {
            $args[] = "-D$key=$value";
        }

        // Build file
        $args[] = '-f';
        $args[] = realpath($this->getContainer()->getParameter('propel.path').'/generator/build.xml');

        return $args;
    }
}
