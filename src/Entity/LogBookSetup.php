<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookSetupRepository")
 * @ORM\Table(name="lbook_setups")
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
     * @var integer
     *
     * @ORM\Column(name="os", type="smallint", nullable=true)
     */
    protected $os = "";

    /**
     * @var bool
     *
     * @ORM\Column(name="check_up_time", type="boolean", options={"default" : "0"})
     */
    protected $checkUpTime = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="owner", type="integer", options={"unsigned"=true})
     */
    protected $owner = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookCycle", mappedBy="setup", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="cycles", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $cycles;

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
    public function getNameShown(): string
    {
        if(strlen($this->nameShown)>0){
            return $this->nameShown;
        }
        else{
            return $this->getName();
        }

    }

    /**
     * @param string $nameShown
     */
    public function setNameShown($nameShown): void
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
     * @return string
     */
    public function getOs(): string
    {
        return $this->os;
    }

    /**
     * @param $os
     */
    public function setOs($os): void
    {
        $this->os = $os;
    }


    /**
     * @return int
     */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /**
     * @param int $owner
     */
    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
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
