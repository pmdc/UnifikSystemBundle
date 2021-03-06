<?php

namespace Unifik\SystemBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Role\RoleInterface;

use Unifik\SystemBundle\Entity\User;
use Unifik\SystemBundle\Lib\BaseEntity;

use Unifik\DoctrineBehaviorsBundle\Model as UnifikORMBehaviors;

/**
 * Role
 */
class Role extends BaseEntity implements RoleInterface, \Serializable
{
    use UnifikORMBehaviors\Translatable\Translatable;
    use UnifikORMBehaviors\Timestampable\Timestampable;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string $role
     */
    private $role;

    /**
     * @var ArrayCollection
     */
    private $users;

    /**
     * @var ArrayCollection
     */
    private $sections;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (false == $this->id) {
            return 'New role';
        }

        if ($name = $this->getName()) {
            return $name;
        }

        // No translation found in the current locale
        return '';
    }

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->sections = new ArrayCollection();
    }

    /**
     * Get Role
     *
     * @return null|string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * Add users
     *
     * @param User $users
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;
    }

    /**
     * Get users
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add sections
     *
     * @param  \Unifik\SystemBundle\Entity\Section $sections
     * @return Role
     */
    public function addSection(\Unifik\SystemBundle\Entity\Section $sections)
    {
        $this->sections[] = $sections;

        return $this;
    }

    /**
     * Set Sections
     *
     * @param ArrayCollection $sections
     */
    public function setSections(ArrayCollection $sections)
    {
        $this->sections = $sections;
    }

    /**
     * Remove sections
     *
     * @param \Unifik\SystemBundle\Entity\Section $sections
     */
    public function removeSection(\Unifik\SystemBundle\Entity\Section $sections)
    {
        $this->sections->removeElement($sections);
    }

    /**
     * Get sections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Remove users
     *
     * @param \Unifik\SystemBundle\Entity\User $users
     */
    public function removeUser(\Unifik\SystemBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get the backend route
     *
     * @param string $suffix
     *
     * @return string
     */
    public function getRouteBackend($suffix = 'edit')
    {
        return 'unifik_system_backend_role_' . $suffix;
    }

    /**
     * Get params for the backend route
     *
     * @param array $params Additional parameters
     *
     * @return array
     */
    public function getRouteBackendParams($params = array())
    {
        $defaults = array(
            'id' => $this->id ? $this->id : 0
        );

        $params = array_merge($defaults, $params);

        return $params;
    }

    /**
     * Used to serialize the User in the Session
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->role
        ));
    }

    /**
     * Used to unserialize the User from the Session
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->role
        ) = unserialize($serialized);
    }
}
