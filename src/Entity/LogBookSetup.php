<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use App\Controller\LogBookUploaderController;
use App\Model\OsType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PrePersist;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookSetupRepository")
 * @ORM\Table(name="lbook_setups", indexes={
 *     @Index(name="full_text", columns={"name", "name_shown"}, flags={"fulltext"})}))
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
     * @ORM\JoinTable(name="lbook_setup_moderators")
     */
    protected $moderators;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookCycle", mappedBy="setup", cascade={"all"}, fetch="EXTRA_LAZY", orphanRemoval=true)
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
     * @ORM\Column(name="retention_policy", type="smallint", options={"unsigned"=true, "default"="40"})
     */
    protected $retentionPolicy = 40;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LogBookUser", inversedBy="favoriteSetups", fetch="EXTRA_LAZY", indexBy="")
     * @ORM\JoinTable(name="favorited_by_user")
     */
    private $favoritedByUsers;

    /**
     * @ORM\Column(type="integer", name="cycles_count", nullable=true, options={"unsigned"=true, "default"="0"})
     */
    private $cyclesCount = 0;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extDefectsJql;

    /**
     * @ORM\Column(type="boolean", options={"default"="0"})
     */
    private $autoCycleReport = false;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookUser::class, inversedBy="subscribedSetups")
     * @ORM\JoinTable(name="lbook_setup_lbook_user_subscribers")
     */
    private $subscribers;

    /**
     * @ORM\Column(type="integer", name="logs_size", nullable=true, options={"unsigned"=true, "default"="0"})
     */
    private $logsSize = 0;

    public static $MIN_NAME_LEN = 2;
    public static $MAX_NAME_LEN = 250;

    protected $rate = 0;
    /**
     * @param float $r
     */
    public function setRate(float $r) {
        $this->rate = round($r, 2);
    }

    public function getRate(): float{
        return $this->rate;
    }

    /**
     * LogBookSetup constructor.
     */
    public function __construct()
    {
        $this->setUpdatedAt();
        $this->setCreatedAt();
        $this->moderators = new ArrayCollection();
        $this->cycles = new ArrayCollection();
        $this->setRetentionPolicy(7);
        $this->favoritedByUsers = new ArrayCollection();
        $this->cyclesCount = 0;
        $this->subscribers = new ArrayCollection();
    }

    /**
     * Get logs table size in MB
     * @return int
     */
    public function getLogsSize(): int
    {
        return $this->logsSize;
    }

    /**
     * Set logs table size in MB
     * @param int $logsSize
     */
    public function setLogsSize(int $logsSize): void
    {
        $this->logsSize = $logsSize;
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
        $up_path = LogBookUploaderController::getUploadPath();
        $path = $up_path . '/' . $this->getId();
        $up_path_len = mb_strlen($up_path);
        $path_len = mb_strlen($path);
        if ($up_path !== '' && $up_path_len > 10 && $up_path_len + 1 < $path_len &&  mb_strpos($path, $up_path) !== false) {
            return $path;
        }
        throw new \RuntimeException('Bad SETUP getLogFilesPath UP_PATH:' . $up_path . ' PATH:'. $path, 1);
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

    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return Collection|LogBookUser[]
     */
    public function getFavoritedByUsers(): Collection
    {
        return $this->favoritedByUsers;
    }

    public function userInFavorite(LogBookUser $favoritedByUser): bool
    {
        if ($this->favoritedByUsers->contains($favoritedByUser)) {
            return true;
        }
        return false;
    }
    public function addFavoritedByUser(LogBookUser $favoritedByUser): self
    {
        if (!$this->userInFavorite($favoritedByUser)) {
            $this->favoritedByUsers[] = $favoritedByUser;
        }

        return $this;
    }

    public function removeFavoritedByUser(LogBookUser $favoritedByUser): self
    {
        if ($this->userInFavorite($favoritedByUser)) {
            $this->favoritedByUsers->removeElement($favoritedByUser);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getCyclesCount(): int
    {
        return $this->cyclesCount;
    }

    /**
     * @param int $cyclesCount
     */
    public function setCyclesCount(int $cyclesCount): void
    {
        $this->cyclesCount = $cyclesCount;
    }

    public function getExtDefectsJql(): ?string
    {
        return $this->extDefectsJql;
    }

    public function setExtDefectsJql(?string $extDefectsJql): self
    {
        $this->extDefectsJql = $extDefectsJql;

        return $this;
    }

    public function getAutoCycleReport(): bool
    {
        return $this->autoCycleReport;
    }

    public function setAutoCycleReport(bool $autoCycleReport): self
    {
        $this->autoCycleReport = $autoCycleReport;

        return $this;
    }

    /**
     * @return Collection|LogBookUser[]
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(UserInterface $subscriber): self
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers[] = $subscriber;
        }

        return $this;
    }

    public function removeSubscriber(UserInterface $subscriber): self
    {
        if ($this->subscribers->contains($subscriber)) {
            $this->subscribers->removeElement($subscriber);
        }

        return $this;
    }

    /**
     * @param bool $addRate
     * @return array
     */
    public function toJson(bool $addRate=false): array
    {
        $ret = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'show_name' => $this->getNameShown(),
            'disabled' => $this->isDisabled(),
            'os' => $this->getOsStr(),
            'check_uptime' => $this->isCheckUpTime(),
            'owner' => (string)$this->getOwner(),
            'created_at' => $this->getCreatedAt()->getTimestamp(),
            'updated_at' => $this->getUpdatedAt()->getTimestamp(),
            'retention_policy' => $this->getRetentionPolicy(),
            'cycles_count' => $this->getCyclesCount(),
            'extDefectsJql' => $this->getExtDefectsJql(),
            'auto_cycle_report' => $this->getAutoCycleReport(),
            'logs_size' => $this->getLogsSize(),
        ];
        if ($addRate) {
            $ret['rate'] = $this->getRate();
        }
        return $ret;
    }
}
