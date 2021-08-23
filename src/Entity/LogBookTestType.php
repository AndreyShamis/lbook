<?php

namespace App\Entity;

use App\Repository\LogBookTestTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookTestTypeRepository::class)
 * @ORM\Table(name="lbook_test_type")
 */
class LogBookTestType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $name;

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
        $this->name = $name;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
