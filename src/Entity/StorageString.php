<?php

namespace App\Entity;

use App\Repository\StorageStringRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StorageStringRepository::class)
 * @ORM\Table(name="db_strings", uniqueConstraints={@ORM\UniqueConstraint(
 *          name="uniq_entry",
 *          columns={"key1","key2","key3", "name"}
 *      )
 *  },
 *  indexes={
 *     @ORM\Index(name="name_index", columns={"name"}),
 *     @ORM\Index(name="keys_and_name", columns={"key1", "key2", "key3", "name"}),
 *     @ORM\Index(name="keys", columns={"key1", "key2", "key3"}),
 *  }
 * )

 */
class StorageString
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $key1 = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $key2 = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $key3;

    /**
     * @ORM\Column(name="vname", type="string", length=255)
     */
    private $vname = '';

    /**
     * @ORM\Column(type="text")
     */
    private $value = '';

    public function __construct()
    {
        $dt = new \DateTime();
        $this->setUpdatedAt($dt);
        $this->setCreatedAt($dt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey1(): string
    {
        return $this->key1;
    }

    public function setKey1(string $key1): self
    {
        $this->key1 = $key1;

        return $this;
    }

    public function getKey2(): ?string
    {
        return $this->key2;
    }

    public function setKey2(string $key2): self
    {
        $this->key2 = $key2;

        return $this;
    }

    public function getKey3(): ?string
    {
        return $this->key3;
    }

    public function setKey3(?string $key3): self
    {
        $this->key3 = $key3;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->vname;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->vname = $name;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
