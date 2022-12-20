PropelBundle
============

![Build Status](https://github.com/dayspring-tech/PropelBundle/actions/workflows/ci.yml/badge.svg)
[![Total Downloads](https://poser.pugx.org/dayspring-tech/PropelBundle/downloads.png)](https://packagist.org/packages/dayspring-tech/PropelBundle)
[![Latest Stable Version](https://poser.pugx.org/dayspring-tech/PropelBundle/v/stable.png)](https://packagist.org/packages/dayspring-tech/PropelBundle)

This is the official implementation of [Propel](http://www.propelorm.org/) in Symfony2.

This fork is maintained by [Dayspring Partners](https://www.dayspringpartners.com/) and provides compatiblity with PHP 7.4 and 8.x.

## Branching model

### Propel1 integration

The two major branches being supported are:

* The `1.5` branch contains Propel *1.6+* integration for Symfony *2.8 LTS*.
* The `1.6` branch contains Propel *1.6+* integration for Symfony *3.0*.
* The `1.7` branch contains Propel *1.7+* integration for Symfony *4.0*.
* The `1.8` branch contains Propel *1.8+* integration for Symfony *4.0* and *5.0*.

If you are running on an older version, you may require one of the following versions of this bundle.

* The `1.0` branch contains Propel *1.6* integration for Symfony *2.0*.
* The `1.1` branch contains Propel *1.6* integration for Symfony *2.1*.
* The `1.2` branch contains Propel *1.6* integration for Symfony *>2.1*.
* The `1.4` branch contains Propel *1.6* integration for Symfony *>2.3*.

### Propel2 integration

* For Propel2 and Propel3 integration, see the upstream repo [https://github.com/propelorm/PropelBundle](https://github.com/propelorm/PropelBundle)

**Note:** the `master` branch won't be updated anymore, and will trigger an `E_USER_DEPRECATED` error to notice people.

## Features

 * Generation of model classes based on an XML schema (not YAML) placed under `BundleName/Resources/*schema.xml`;
 * Insertion of SQL statements;
 * Runtime autoloading of Propel and generated classes;
 * Propel runtime initialization through the XML configuration;
 * Migrations [Propel 1.6](http://www.propelorm.org/documentation/10-migrations.html);
 * Reverse engineering from [existing database](http://www.propelorm.org/wiki/Documentation/1.6/Existing-Database);
 * Integration to the Symfony2 Profiler;
 * Load SQL, YAML and XML fixtures;
 * Create/Drop databases;
 * Integration with the Form component;
 * Integration with the Security component;
 * Propel ParamConverter can be used with Sensio Framework Extra Bundle.

For documentation, see:

    Resources/doc/

[Read the documentation](https://github.com/propelorm/PropelBundle/blob/1.5/Resources/doc/index.markdown)

For license, see:

    Resources/meta/LICENSE
