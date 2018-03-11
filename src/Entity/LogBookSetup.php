<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookSetupRepository")
 * @ORM\Table(name="lbook_setups")
 * @UniqueEntity("name", message="Setup with this name already exist")
 */
class LogBookSetup
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Test Verdict name cannot ne empty")
     */
    protected $name = "";

    /**
     * @var string
     *
     * @ORM\Column(name="name_shown", type="string", length=50, nullable=true)
     */
    protected $nameShown = "";

    /**
     * @var bool
     *
     * @ORM\Column(name="is_disabled", type="boolean", options={"default" : "0"})
     */
    protected $disabled = false;

    /**
     * @ORM\Embedded(class = "OsType")
     */
    protected $os;

    /**
     * @var bool
     *
     * @ORM\Column(name="check_up_time", type="boolean", options={"default" : "0"})
     */
    protected $checkUpTime = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookUser", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="owner", referencedColumnName="id", nullable=true)
     */
    protected $owner = 0;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LogBookUser", fetch="EXTRA_LAZY")
     */
    protected $moderators;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookCycle", mappedBy="setup", cascade={"all"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="cycles", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $cycles;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_private", type="boolean", options={"default"=false})
     */
    protected $isPrivate = false;

    public function __construct()
    {
        $this->moderators = array();
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    /**
     * @param bool $isPrivate
     */
    public function setIsPrivate(bool $isPrivate): void
    {
        $this->isPrivate = $isPrivate;
    }

    /**
     * @return mixed
     */
    public function getModerators()
    {
        return $this->moderators;
    }

    /**
     * @param mixed $moderators
     */
    public function setModerators($moderators): void
    {
        $this->moderators = $moderators;
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
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getNameShown(): ?string
    {
        if (strlen($this->nameShown)>0) {
            return $this->nameShown;
        } else {
            return $this->getName();
        }
    }

    /**
     * @param string $nameShown
     */
    public function setNameShown(string $nameShown=null): void
    {
        $this->nameShown = $nameShown;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /**
     * @return OsType|null
     */
    public function getOs(): ?OsType
    {
        return $this->os;
    }


    public function setOs($os): void
    {
        $this->os = $os;
    }

    /**
     * @return bool
     */
    public function isCheckUpTime(): bool
    {
        return $this->checkUpTime;
    }

    /**
     * @param bool $checkUpTime
     */
    public function setCheckUpTime(bool $checkUpTime): void
    {
        $this->checkUpTime = $checkUpTime;
    }

    /**
     * @return mixed
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    /**
     * @param mixed $cycles
     */
    public function setCycles($cycles): void
    {
        $this->cycles = $cycles;
    }
}
