<?php

namespace App\Entity;

use App\Repository\LogBookEmailRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookEmailRepository::class)
 */
class LogBookEmail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookUser::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $recipient;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getRecipient(): ?LogBookUser
    {
        return $this->recipient;
    }

    public function setRecipient(?LogBookUser $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }
}
