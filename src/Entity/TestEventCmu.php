<?php

namespace App\Entity;

use App\Repository\TestEventCmuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestEventCmuRepository::class)
 * @ORM\Table(name="lbook_test_event_cmu", uniqueConstraints={@ORM\UniqueConstraint(name="unique_event", columns={"block", "fault", "test_id", "a_time", "b_time"})})
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
     * @ORM\Column(type="string", length=50, options={"default"=""})
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
     * @ORM\Column(type="string", length=30, options={"default"="0"})
     */
    private $a_time = '0';

    /**
     * @ORM\Column(type="string", length=30, options={"default"="0"})
     */
    private $b_time = '0';
    /**
     * @ORM\ManyToOne(targetEntity=LogBookTest::class)
     * @ORM\JoinColumn(name="test_id", fieldName="id", referencedColumnName="id", onDelete="CASCADE")
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

    /**
     * @return string
     */
    public function getATime(): string
    {
        return $this->a_time;
    }

    /**
     * @param string $a_time
     */
    public function setATime(string $a_time): void
    {
        $this->a_time = $a_time;
    }

    /**
     * @return string
     */
    public function getBTime(): string
    {
        return $this->b_time;
    }

    /**
     * @param string $b_time
     */
    public function setBTime(string $b_time): void
    {
        $this->b_time = $b_time;
    }

    public function __toString()
    {
        return $this->getBlock() . '[' . $this->getFault() . ']' . $this->getATime() . '-' . $this->getBTime();
    }
}
