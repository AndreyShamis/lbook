<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
// BADAKTI

/**
 * @ORM\Entity(repositoryClass="App\Repository\NodeRepository")
 */
class Node
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $rootId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $graphId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nodeCode;

    /**
     * @ORM\Column(type="integer")
     */
    private $level;

    public function __construct(array $data)
    {
        $this->rootId = $data['root_id'];
        $this->graphId = $data['graph_id'];
        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->nodeCode = $data['node_code'];
        $this->level = $data['level'];
    }

    // Getters and setters for all properties...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRootId(): ?string
    {
        return $this->rootId;
    }

    public function setRootId(string $rootId): self
    {
        $this->rootId = $rootId;
        return $this;
    }

    public function getGraphId(): ?string
    {
        return $this->graphId;
    }

    public function setGraphId(string $graphId): self
    {
        $this->graphId = $graphId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
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

    public function getNodeCode(): ?string
    {
        return $this->nodeCode;
    }

    public function setNodeCode(string $nodeCode): self
    {
        $this->nodeCode = $nodeCode;
        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }
}
