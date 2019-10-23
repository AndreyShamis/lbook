<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookBuildRepository")
 * @ORM\Table(name="lbook_build", uniqueConstraints={@ORM\UniqueConstraint(name="build_uniq_name", columns={"name"})})
 * @UniqueEntity("name", message="Build name already exist")
 */
class LogBookBuild
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $id;

    public static $MAX_NAME_LEN = 250;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, options={"default"=""})
     */
    protected $name = '';

    /**
     * @var int
     *
     * @ORM\Column(name="cycles_found", type="integer", options={"unsigned"=true, "default"="0"})
     */
    protected $cycles = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="delete_counter", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $deleteCounter = 0;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCycles(): int
    {
        return $this->cycles;
    }

    /**
     * @param int $cycles
     */
    public function setCycles(int $cycles): void
    {
        $this->cycles = $cycles;
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

    /**
     * @return int
     */
    public function getDeleteCounter(): int
    {
        return $this->deleteCounter;
    }

    /**
     * @param int $deleteCounter
     * @return LogBookBuild
     */
    public function setDeleteCounter(int $deleteCounter): self
    {
        $this->deleteCounter = $deleteCounter;
        return $this;
    }

    /**
     * @return LogBookBuild
     */
    public function increaseDeleteCounter(): self
    {
        return $this->setDeleteCounter($this->getDeleteCounter() + 1);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
