<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Fixtures;

class Column extends \ColumnMap
{
    public function __construct(private $name, protected $type)
    {
        $this->phpName = ucfirst($this->name);
    }

    public function isText()
    {
        if (!$this->type) {
            return false;
        }
        return match ($this->type) {
            \PropelColumnTypes::CHAR, \PropelColumnTypes::VARCHAR, \PropelColumnTypes::LONGVARCHAR, \PropelColumnTypes::BLOB, \PropelColumnTypes::CLOB, \PropelColumnTypes::CLOB_EMU => true,
            default => false,
        };
    }

    public function getSize()
    {
        return $this->isText() ? 255 : 0;
    }

    public function isNotNull()
    {
        return 'id' === $this->name;
    }
}
