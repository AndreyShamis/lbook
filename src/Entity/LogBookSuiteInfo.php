<?php

namespace App\Entity;

use App\Repository\LogBookSuiteInfoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookSuiteInfoRepository::class)
 */
class LogBookSuiteInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=38, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $testsCount;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $assignee = [];


    public static $MAX_NAME_LEN = 250;

    public static function validateName($newName): string
    {
        if (mb_strlen($newName) > self::$MAX_NAME_LEN) {
            $newName = mb_substr($newName, 0, self::$MAX_NAME_LEN);
        }
        return $newName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = LogBookSuiteInfo::validateName($name);

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTestsCount(): ?int
    {
        return $this->testsCount;
    }

    public function setTestsCount(int $testsCount): self
    {
        $this->testsCount = $testsCount;

        return $this;
    }

    public function getAssignee(): ?array
    {
        return $this->assignee;
    }

    public function setAssignee(?array $assignee): self
    {
        $this->assignee = $assignee;

        return $this;
    }
}
