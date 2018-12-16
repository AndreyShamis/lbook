<?php

namespace App\Entity;

use App\Model\EventStatus;
use Doctrine\ORM\Mapping\PrePersist;
use App\Model\EventType;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\Table(name="events", uniqueConstraints={@ORM\UniqueConstraint(name="each_object_event_type_unique", columns={"object_id", "object_class", "event_type"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="text", name="message")
     */
    private $message;

    /**
     * @var int
     * @ORM\Column(name="event_type", type="smallint", options={"unsigned"=true})
     */
    private $eventType = 0;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $status = 0;

    /**
     * @ORM\Column(name="object_id", type="bigint", options={"unsigned"=true})
     */
    private $objectId = 0;

    /**
     * @ORM\Column(name="object_class", type="string", length=190, nullable=true)
     */
    private $objectClass = '';

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt;
    /**
     * @ORM\Column(type="dateinterval", nullable=true)
     */
    private $duration;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $metadata = [];

    public function __construct(int $type=0)
    {
        $this->setEventType($type);
        $this->setStatus(EventStatus::CREATED);
    }

    /**
     * @return array
     */
    public function getMetaData(): array
    {
        if ($this->metadata === null) {
            $this->metadata = array();
        }
        return $this->metadata;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param mixed $objectId
     */
    public function setObjectId($objectId): void
    {
        $this->objectId = $objectId;
    }

    /**
     * @return mixed
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @param mixed $objectClass
     */
    public function setObjectClass($objectClass): void
    {
        $this->objectClass = $objectClass;
    }

    /**
     * @param array $metadata
     */
    public function addMetaData(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * @param array $metadata
     */
    public function setMetaData(array $metadata): void
    {
        if ($this->metadata === null || \count($this->metadata) === 0) {
            $this->metadata = $metadata;
        } else {
            $this->addMetaData($metadata);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getEventType(): int
    {
        return $this->eventType;
    }

    /**
     * @param int $eventType - actual smallint
     * @return Event
     */
    public function setEventType(int $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @PrePersist
     * @throws \Exception
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @param \DateTimeInterface $startedAt
     * @return Event
     */
    public function setStartedAt(\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @PrePersist
     * @throws \Exception
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    public function setDuration(?\DateInterval $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function __toString()
    {
        return '[' .
            $this->getName() . ':' .
            EventType::getTypeName($this->getEventType()) . '(' . $this->getEventType() . '):' .
            EventStatus::getStatusName($this->getStatus()) . '(' . $this->getStatus() . ')]';
    }
}
