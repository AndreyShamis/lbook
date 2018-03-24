<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @property string salt
 * @ORM\Entity(repositoryClass="App\Repository\LogBookUserRepository")
 * @ORM\Table(name="lbook_users", uniqueConstraints={@ORM\UniqueConstraint(name="user_uniq_name", columns={"username"})})
 * @UniqueEntity("username", message="This User Name already exist")
 */
class LogBookUser implements UserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     * @Assert\NotBlank(message="User Name cannot ne empty")
     */
    protected $username = '';

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    protected $firstName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    protected $lastName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="another_id", type="string", length=255, nullable=true)
     */
    protected $anotherId = '';

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     */
    protected $fullName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true)
     */
    protected $mobile = '';

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * //@Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\Email()
     */
    protected $email = '';

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="json", columnDefinition="TEXT NOT NULL")
     */
    protected $roles = [];

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ldap_user", type="boolean", options={"default"="0"})
     */
    protected $isLdapUser = 0;

    /**
     *
     * LogBookUser constructor.
     */
    public function __construct()
    {
        $this->isActive = true;
        $this->roles = array();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username='')
    {
        $this->username = $username;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     */
    public function setFullName(string $fullName=null)
    {
        $this->fullName = $fullName;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName=null)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName=null)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getAnotherId(): ?string
    {
        return $this->anotherId;
    }

    /**
     * @param string|null $anotherId
     */
    public function setAnotherId(string $anotherId=null)
    {
        $this->anotherId = $anotherId;
    }

    /**
     * @return string
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile(string $mobile=null)
    {
        $this->mobile = $mobile;
    }

    /**
     * @return bool
     */
    public function isLdapUser(): bool
    {
        return $this->isLdapUser;
    }

    /**
     * @param bool $isLdapUser
     */
    public function setIsLdapUser(bool $isLdapUser): void
    {
        $this->isLdapUser = $isLdapUser;
    }

    /**
     * @return string
     */
    public function getPlainPassword(): string
    {
        if (\is_string($this->plainPassword)) {
            return $this->plainPassword;
        }

        return '';
    }

    /**
     * @param string $password
     */
    public function setPlainPassword($password = '')
    {
        $this->plainPassword = $password;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        if (\is_bool($this->isActive)) {
            return $this->isActive;
        }
        return false;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email=null): void
    {
        $this->email = $email;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return array (Role|string)[] The user roles
     */
    public function getRoles() : array
    {
        $roles = $this->roles;
        // give everyone ROLE_USER!
        if (!\is_array($roles)) {
            $roles = array();
        }
        if (!\in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return $roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() : string
    {
        if (\is_string($this->password)) {
            return $this->password;
        }

        return '';
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     */
    public function getSalt(): void
    {
        $this->salt = md5( uniqid('', true));
        //return $this->salt;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {

    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     * @see \Serializable::serialize() */
    public function serialize(): string
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->isActive,
            // see section on salt below
            // $this->salt,
        ));
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return array
     * @since 5.1.0
     * @see \Serializable::unserialize() */
    public function unserialize($serialized): array
    {
        return list (
            $this->id,
            $this->username,
            $this->password,
            $this->isActive,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled(): bool
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUsername();
    }
}
