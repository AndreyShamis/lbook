<?php

namespace App\Entity;

use App\Repository\LogBookTestMDRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass=LogBookTestMDRepository::class)
 * @ORM\Table(name="lbook_test_meta_data", indexes={
 *     @Index(name="fulltext_metadata", columns={"value"}, flags={"fulltext"})})
 */
class LogBookTestMD
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $value;

    /**
     * @ORM\OneToOne(targetEntity=LogBookTest::class, inversedBy="newMetaData", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $test;

    public function __construct()
    {
        $this->value = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(array $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getTest(): ?LogBookTest
    {
        return $this->test;
    }

    public function setTest(LogBookTest $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function __toString()
    {
        return implode(';;', $this->getValue());
    }
}
