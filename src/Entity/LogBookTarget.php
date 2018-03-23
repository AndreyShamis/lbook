<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookTargetRepository")
 * @ORM\Table(name="lbook_targets", uniqueConstraints={@ORM\UniqueConstraint(name="uniq_name", columns={"name"})})
 * @UniqueEntity("name", message="Target with this name already exist")
 */
class LogBookTarget
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
     * @ORM\Column(name="name", type="string", length=50, options={"default"=""})
     */
    protected $name = '';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ip", type="boolean", options={"default"="0"})
     */
    protected $isIp = false;

    /**
     * @return bool
     */
    public function isIp(): bool
    {
        return $this->isIp;
    }

    /**
     * @param bool $isIp
     */
    public function setIsIp(bool $isIp): void
    {
        $this->isIp = $isIp;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->setIsIp(ip2long($this->name) !== false);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
