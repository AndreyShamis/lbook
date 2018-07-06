<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookMessageRepository")
 */
class LogBookMessage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    protected $id;

    /**
     * [20]	(1, 2, 3, 4, 5) Chosen if the column length is less or equal to 2 ^ 32 - 1 = 4294967295 or empty.
     * @var string
     *
     * @ORM\Column(name="message", type="text", length=4294967295)
     * @Assert\NotBlank(message="test log cannot be blank, please provide message")
     */
    protected $message = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookMessageType", cascade={"persist"})
     * @ORM\JoinColumn(name="msg_type", fieldName="id", referencedColumnName="id")
     */
    protected $msgType;

    /**
     * @var integer
     *
     * @ORM\Column(name="chain", type="smallint", options={"unsigned"=true})
     */
    protected $chain = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookTest", inversedBy="logs", cascade={"persist"})
     * @ORM\JoinColumn(name="test", fieldName="id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $test;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="log_time", type="datetime")
     */
    protected $logTime;

    /**
     * @return \DateTime
     */
    public function getLogTime(): \DateTime
    {
        return $this->logTime;
    }

    /**
     * @param \DateTime $logTime
     */
    public function setLogTime(\DateTime $logTime): void
    {
        $this->logTime = $logTime;
    }

    /**
     * @return mixed
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param mixed $test
     */
    public function setTest($test): void
    {
        $this->test = $test;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return LogBookMessageType
     */
    public function getMsgType(): LogBookMessageType
    {
        return $this->msgType;
    }

    /**
     * @param LogBookMessageType $msgType
     */
    public function setMsgType(LogBookMessageType $msgType): void
    {
        $this->msgType = $msgType;
    }

    /**
     * @return int
     */
    public function getChain(): int
    {
        return $this->chain;
    }

    /**
     * @param int $chain
     */
    public function setChain(int $chain): void
    {
        $this->chain = $chain;
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}
