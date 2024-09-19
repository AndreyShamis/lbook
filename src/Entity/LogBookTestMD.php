<?php

namespace App\Entity;

use App\Repository\LogBookTestMDRepository;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass=LogBookTestMDRepository::class)
 * @ORM\Table(name="lbook_test_meta_data", indexes={
 *      @ORM\Index(name="fulltext_metadata", columns={"value"}, flags={"fulltext"})
 * })
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
     * @ORM\Column(name="valueJson", type="json", nullable=true)
     */
    private $valueJson;
    
    /**
     * @ORM\OneToOne(targetEntity=LogBookTest::class, inversedBy="newMetaData", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $test;

    public function __construct()
    {
        $this->value = [];
    }

    public function getValueJson(): ?array
    {
        return $this->valueJson;
    }

    public function setValueJson(?array $valueJson): self
    {
        $this->valueJson = $valueJson;
        return $this;
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
        // Set the value for the serialized field (legacy support)
        $this->value = $value;
    
        // Convert key-value pairs into JSON format and store them in valueJson
        $jsonArray = [];
        foreach ($value as $key => $val) {
            $jsonArray[] = ['key' => $key, 'val' => $val];
        }
    
        // Set the JSON field with the converted key-value pairs
        $this->setValueJson($jsonArray);
    
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
