<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\DataCollector;

use Propel\Bundle\PropelBundle\Logger\PropelLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * The PropelDataCollector collector class collects information.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelDataCollector extends DataCollector
{
    /**
     * Propel configuration.
     *
     * @var \PropelConfiguration
     */
    protected $propelConfiguration;

    /**
     * Constructor.
     *
     * @param PropelLogger         $logger              A Propel logger.
     * @param \PropelConfiguration $propelConfiguration The Propel configuration object.
     */
    public function __construct(/**
     * Propel logger.
     */
    private PropelLogger $logger, \PropelConfiguration $propelConfiguration)
    {
        $this->propelConfiguration = $propelConfiguration;
    }

    public function reset()
    {
        $this->logger->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $this->data = [
            'queries' => $this->buildQueries(),
            'querycount' => $this->countQueries(),
        ];
    }

    /**
     * Returns the collector name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'propel';
    }

    /**
     * Returns the collected stats for all executed SQL queries.
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * Returns the query count.
     *
     * @return int
     */
    public function getQueryCount()
    {
        return $this->data['querycount'];
    }

    /**
     * Returns the total time spent on running all queries.
     *
     * @return float
     */
    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += (float) $query['time'];
        }

        return $time;
    }

    /**
     * Computes the stats of all executed SQL queries.
     *
     * @return array
     */
    private function buildQueries()
    {
        $queries = [];

        $outerGlue = $this->propelConfiguration->getParameter('debugpdo.logging.outerglue', ' | ');
        $innerGlue = $this->propelConfiguration->getParameter('debugpdo.logging.innerglue', ': ');

        foreach ($this->logger->getQueries() as $q) {
            $parts = explode($outerGlue, $q, 4);

            $times = explode($innerGlue, $parts[0]);
            $con = explode($innerGlue, $parts[2]);
            $memories = explode($innerGlue, $parts[1]);

            $sql = trim($parts[3]);
            $con = trim($con[1]);
            $time = trim($times[1]);
            $memory = trim($memories[1]);

            $queries[] = ['connection' => $con, 'sql' => $sql, 'time' => $time, 'memory' => $memory];
        }

        return $queries;
    }

    /**
     * Returns the total count of SQL queries.
     *
     * @return int
     */
    private function countQueries()
    {
        return count($this->logger->getQueries());
    }
}
