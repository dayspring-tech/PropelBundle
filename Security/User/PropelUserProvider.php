<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provides easy to use provisioning for Propel model users.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelUserProvider implements UserProviderInterface
{
    /**
     * A Query class name.
     *
     * @var string
     */
    protected $queryClass;

    /**
     * Default constructor.
     *
     * @param string      $class    The User model class.
     * @param string|null $property The property to use to retrieve a user.
     */
    public function __construct(/**
     * A Model class name.
     */
    protected $class, /**
     * A property to use to retrieve the user.
     */
    protected $property = null)
    {
        $this->queryClass = $this->class.'Query';
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $username): UserInterface
    {
        $queryClass = $this->queryClass;
        $query = $queryClass::create();

        if (null !== $this->property) {
            $filter = 'filterBy'.ucfirst($this->property);
            $query->$filter($username);
        } else {
            $query->filterByUsername($username);
        }

        if (null === $user = $query->findOne()) {
            if (class_exists('Symfony\Component\Security\Core\Exception\UsernameNotFoundException')) {
                throw new \Symfony\Component\Security\Core\Exception\UsernameNotFoundException(sprintf('User "%s" not found.', $username));
            } else {
                throw new \Symfony\Component\Security\Core\Exception\UserNotFoundException(sprintf('User "%s" not found.', $username));
            }
        }

        return $user;
    }

    public function loadUserByUsername($username)
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): \Symfony\Component\Security\Core\User\UserInterface
    {
        if (!$user instanceof $this->class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $queryClass = $this->queryClass;

        return $queryClass::create()->findPk($user->getPrimaryKey());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return $class === $this->class;
    }
}
