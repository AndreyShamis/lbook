<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookUserSettingsRepository")
 * @ORM\Table(name="lbook_user_settings")
 */
class LogBookUserSettings
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $id;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestIdShow = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeStartShow = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeEndShow = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeRatioShow = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestMetaDataShow = 1;

    /**
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestMetaDataOptShow = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestUptime = 1;

    /**
     * @ORM\Column(type="string", length=50, options={"default"="H:i:s"})
     */
    protected $cycleShowTestTimeStartFormat = 'H:i:s';

    /**
     * @ORM\Column(type="string", length=50, options={"default"="H:i:s"})
     */
    protected $cycleShowTestTimeEndFormat = 'H:i:s';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\LogBookUser", inversedBy="settings", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isCycleShowTestUptime(): bool
    {
        return $this->cycleShowTestUptime;
    }

    /**
     * @param bool $val
     */
    public function setCycleShowTestUptime(bool $val): void
    {
        $this->cycleShowTestUptime = $val;
    }

    /**
     * @return bool
     */
    public function getCycleShowTestMetaDataOptShow(): bool
    {
        return $this->cycleShowTestMetaDataOptShow;
    }

    /**
     * @param bool $cycleShowTestMetaDataOptShow
     */
    public function setCycleShowTestMetaDataOptShow(bool $cycleShowTestMetaDataOptShow): void
    {
        $this->cycleShowTestMetaDataOptShow = $cycleShowTestMetaDataOptShow;
    }

    /**
     * @return bool
     */
    public function getCycleShowTestMetaDataShow(): bool
    {
        return $this->cycleShowTestMetaDataShow;
    }

    /**
     * @param bool $val
     */
    public function setCycleShowTestMetaDataShow(bool $val): void
    {
        $this->cycleShowTestMetaDataShow = $val;
    }

    /**
     * @return mixed
     */
    public function getCycleShowTestTimeStartShow(): bool
    {
        return $this->cycleShowTestTimeStartShow;
    }

    /**
     * @param bool $val
     */
    public function setCycleShowTestTimeStartShow(bool $val): void
    {
        $this->cycleShowTestTimeStartShow = $val;
    }

    /**
     * @return bool
     */
    public function getCycleShowTestTimeEndShow(): bool
    {
        return $this->cycleShowTestTimeEndShow;
    }

    /**
     * @param bool $val
     */
    public function setCycleShowTestTimeEndShow(bool $val): void
    {
        $this->cycleShowTestTimeEndShow = $val;
    }

    /**
     * @return bool
     */
    public function getCycleShowTestTimeRatioShow(): bool
    {
        return $this->cycleShowTestTimeRatioShow;
    }

    /**
     * @param bool $val
     */
    public function setCycleShowTestTimeRatioShow(bool $val): void
    {
        $this->cycleShowTestTimeRatioShow = $val;
    }

    /**
     * @return string
     */
    public function getCycleShowTestTimeEndFormat(): string
    {
        return $this->cycleShowTestTimeEndFormat;
    }

    /**
     * @param string $cycleShowTestTimeEndFormat
     */
    public function setCycleShowTestTimeEndFormat(string $cycleShowTestTimeEndFormat): void
    {
        $this->cycleShowTestTimeEndFormat = $cycleShowTestTimeEndFormat;
    }

    /**
     * @return bool
     */
    public function getCycleShowTestIdShow(): bool
    {
        return $this->cycleShowTestIdShow;
    }

    /**
     * @param bool $val
     * @return LogBookUserSettings
     */
    public function setCycleShowTestIdShow(bool $val): self
    {
        $this->cycleShowTestIdShow = $val;

        return $this;
    }

    /**
     * @return string
     */
    public function getCycleShowTestTimeStartFormat(): string
    {
        return $this->cycleShowTestTimeStartFormat;
    }

    /**
     * @param string $cycleShowTestTimeStartFormat
     * @return LogBookUserSettings
     */
    public function setCycleShowTestTimeStartFormat(string $cycleShowTestTimeStartFormat): self
    {
        $this->cycleShowTestTimeStartFormat = $cycleShowTestTimeStartFormat;

        return $this;
    }

    /**
     * @return LogBookUser
     */
    public function getUser(): ?LogBookUser
    {
        return $this->user;
    }

    /**
     * @param LogBookUser $user
     * @return LogBookUserSettings
     */
    public function setUser(LogBookUser $user): self
    {
        $this->user = $user;

        return $this;
    }
}
