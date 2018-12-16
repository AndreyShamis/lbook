<?php
/**
 * User: Andrey Shamis
 * Date: 16/12/18
 * Time: 10:27
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="App\Repository\LogBookMessageMongoRepository")
 */
class LogBookMessageMongo
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(name="message", type="string")
     */
    protected $message = '';

    /**
     * @MongoDB\Field(name="msg_type", type="int")
     */
    protected $msgType;

    /**
     * @var integer
     *
     * @MongoDB\Field(name="chain", type="int")
     */
    protected $chain = 0;

    /**
     * @MongoDB\Field(name="test", type="int")
     */
    protected $test;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(name="log_time", type="date")
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
     * @return int
     */
    public function getMsgType(): int
    {
        return $this->msgType;
    }

    /**
     * @param int $msgType
     */
    public function setMsgType(int $msgType): void
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