<?php

namespace App\Entity;

use App\Repository\TestEventCmuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestEventCmuRepository::class)
 */
class TestEventCmu
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, options={"default"=""})
     */
    private $block = '';

    /**
     * @ORM\Column(type="integer", options={"default"="0"})
     */
    private $fault = 0;

    /**
     * @ORM\Column(type="string", length=150, options={"default"=""})
     */
    private $a_value = '';

    /**
     * @ORM\Column(type="string", length=150, options={"default"=""})
     */
    private $b_value = '';

    /**
     * @ORM\ManyToOne(targetEntity=LogBookTest::class)
     */
    private $test;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    /**
     * LogBookSetup constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlock(): string
    {
        return $this->block;
    }

    public function setBlock(string $block): self
    {
        $this->block = $block;

        return $this;
    }

    public function getFault(): int
    {
        return $this->fault;
    }

    public function setFault(int $fault): self
    {
        $this->fault = $fault;

        return $this;
    }

    public function getAValue(): string
    {
        return $this->a_value;
    }

    public function setAValue(string $a_value): self
    {
        $this->a_value = $a_value;

        return $this;
    }

    public function getBValue(): string
    {
        return $this->b_value;
    }

    public function setBValue(string $b_value): self
    {
        $this->b_value = $b_value;

        return $this;
    }

    public function getTest(): ?LogBookTest
    {
        return $this->test;
    }

    public function setTest(?LogBookTest $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

}
