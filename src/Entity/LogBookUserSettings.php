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
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestIdShow = 1;

    /**
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeStartShow = 1;

    /**
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeEndShow = 1;

    /**
     * @ORM\Column(type="boolean", options={"default"="1"})
     */
    protected $cycleShowTestTimeRatioShow = 1;

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

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCycleShowTestTimeStartShow(): bool
    {
        return $this->cycleShowTestTimeStartShow;
    }

    /**
     * @param mixed $cycleShowTestTimeStartShow
     */
    public function setCycleShowTestTimeStartShow($cycleShowTestTimeStartShow): void
    {
        $this->cycleShowTestTimeStartShow = $cycleShowTestTimeStartShow;
    }

    /**
     * @return mixed
     */
    public function getCycleShowTestTimeEndShow(): bool
    {
        return $this->cycleShowTestTimeEndShow;
    }

    /**
     * @param mixed $cycleShowTestTimeEndShow
     */
    public function setCycleShowTestTimeEndShow($cycleShowTestTimeEndShow): void
    {
        $this->cycleShowTestTimeEndShow = $cycleShowTestTimeEndShow;
    }

    /**
     * @return mixed
     */
    public function getCycleShowTestTimeRatioShow(): bool
    {
        return $this->cycleShowTestTimeRatioShow;
    }

    /**
     * @param mixed $cycleShowTestTimeRatioShow
     */
    public function setCycleShowTestTimeRatioShow($cycleShowTestTimeRatioShow): void
    {
        $this->cycleShowTestTimeRatioShow = $cycleShowTestTimeRatioShow;
    }

    /**
     * @return mixed
     */
    public function getCycleShowTestTimeEndFormat(): bool
    {
        return $this->cycleShowTestTimeEndFormat;
    }

    /**
     * @param mixed $cycleShowTestTimeEndFormat
     */
    public function setCycleShowTestTimeEndFormat($cycleShowTestTimeEndFormat): void
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
     * @param bool $cycleShowTestIdShow
     * @return LogBookUserSettings
     */
    public function setCycleShowTestIdShow(bool $cycleShowTestIdShow): self
    {
        $this->cycleShowTestIdShow = $cycleShowTestIdShow;

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
