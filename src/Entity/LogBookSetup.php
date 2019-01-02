<?php

namespace App\Entity;

use App\Controller\LogBookUploaderController;
use App\Model\OsType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PrePersist;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookSetupRepository")
 * @ORM\Table(name="lbook_setups")
 * @UniqueEntity("name", message="Setup with this name already exist")
 * @ORM\HasLifecycleCallbacks()
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
    protected $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="name_shown", type="string", length=50, nullable=true)
     */
    protected $nameShown = '';

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
    protected $os = 0;

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

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $updatedAt;

    /**
     * @var integer
     * Value in days used for delete Cycles
     * @ORM\Column(name="retention_policy", type="smallint", options={"unsigned"=true, "default"="400"})
     */
    protected $retentionPolicy = 400;

    public static $MIN_NAME_LEN = 2;
    public static $MAX_NAME_LEN = 250;

    /**
     * LogBookSetup constructor.
     */
    public function __construct()
    {
        $this->setUpdatedAt();
        $this->setCreatedAt();
        $this->moderators = new ArrayCollection();
        $this->cycles = new ArrayCollection();
        $this->setRetentionPolicy(90);
    }

    /**
     * @return int
     */
    public function getRetentionPolicy(): int
    {
        return $this->retentionPolicy;
    }

    /**
     * @param int $retentionPolicy
     */
    public function setRetentionPolicy(int $retentionPolicy): void
    {
        $this->retentionPolicy = $retentionPolicy;
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
     * @return Collection|LogBookUser[]
     */
    public function getModerators(): Collection
    {
        return $this->moderators;
    }

    /**
     * @param Collection $moderators
     */
    public function setModerators(Collection $moderators): void
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
     * @return string
     */
    public function getLogFilesPath(): string
    {
        $path = LogBookUploaderController::$UPLOAD_PATH . '/' . $this->getId();
        return realpath($path);
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

    public static function validateName($newName): string
    {
        if (mb_strlen($newName) > self::$MAX_NAME_LEN) {
            $newName = mb_substr($newName, 0, self::$MAX_NAME_LEN);
        }
        return $newName;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = self::validateName($name);
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
        if (\strlen($this->nameShown)>0) {
            return $this->nameShown;
        }

        return $this->getName();
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
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @return string
     */
    public function getOsStr(): string
    {
        return OsType::getTypeName($this->getOs());
    }

    /**
     * @param $os
     */
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
     * @return Collection|LogBookCycle[]
     */
    public function getCycles(): Collection
    {
        return $this->cycles;
    }

    /**
     * @param Collection $cycles
     */
    public function setCycles($cycles): void
    {
        $this->cycles = $cycles;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @PreFlush
     * @PrePersist
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
