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

class Item implements \Persistent
{
    public function __construct(private $id = null, private $value = null, private $groupName = null, private $price = null, private $slug = null)
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getGroupName()
    {
        return $this->groupName;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getPrimaryKey()
    {
        return $this->getId();
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->setId($primaryKey);
    }

    public function isModified()
    {
        return false;
    }

    public function isColumnModified($col)
    {
        return false;
    }

    public function isNew()
    {
        return false;
    }

    public function setNew($b)
    {
    }

    public function resetModified()
    {
    }

    public function isDeleted()
    {
        return false;
    }

    public function setDeleted($b)
    {
    }

    public function delete(\PropelPDO $con = null)
    {
    }

    public function save(\PropelPDO $con = null)
    {
    }
}
