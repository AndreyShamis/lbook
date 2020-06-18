<?php
/**
 * User: Andrey Shamis
 * Date: 14.06.20
 * Time: 17:57
 */

namespace App\Entity;


class SuiteExecutionSearch
{

    protected $name = '';

    /** @var array  */
    protected $testingLevel = [];

    /** @var array  */
    protected $publish = [];

    /** @var array  */
    protected $platforms = [];

    /** @var array  */
    protected $chips = [];

    protected $setups;

    protected $fromDate;

    protected $toDate;

    /** @var int  */
    protected $limit = 200;

    public static $MAX_LIMIT = 10000;

    public static $DEFAULT_LIMIT = 2000;

    public function __construct()
    {
        $d = new \DateTime('- 7 days');
        $this->fromDate = $d->format('m/d/Y');
    }

    /**
     * @return array
     */
    public function getPublish(): array
    {
        return $this->publish;
    }

    /**
     * @param array $publish
     */
    public function setPublish(array $publish): void
    {
        $this->publish = $publish;
    }

    /**
     * @return array
     */
    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    /**
     * @param array $platforms
     */
    public function setPlatforms(array $platforms): void
    {
        $this->platforms = $platforms;
    }

    /**
     * @return array
     */
    public function getChips(): array
    {
        return $this->chips;
    }

    /**
     * @param array $chips
     */
    public function setChips(array $chips): void
    {
        $this->chips = $chips;
    }

    /**
     * @return array
     */
    public function getTestingLevel(): array
    {
        return $this->testingLevel;
    }

    /**
     * @param array $testingLevel
     */
    public function setTestingLevel(array $testingLevel): void
    {
        $this->testingLevel = $testingLevel;
    }

    /**
     * @return mixed
     */
    public function getSetups()
    {
        return $this->setups;
    }

    /**
     * @param mixed $setups
     */
    public function setSetups($setups): void
    {
        $this->setups = $setups;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        if ($this->limit < 1 || $this->limit > self::$MAX_LIMIT ) {
            $this->setLimit(self::$DEFAULT_LIMIT);
        }
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit = null): void
    {
        if ($limit === null) {
            $this->limit = self::$DEFAULT_LIMIT;
        } else {
            $this->limit = $limit;
        }
    }

    /**
     * @return mixed
     */
    public function getToDate()
    {
        return $this->toDate;
    }

    /**
     * @param mixed $toDate
     */
    public function setToDate($toDate): void
    {
        $this->toDate = $toDate;
    }

    /**
     * @return mixed
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @param mixed $fromDate
     */
    public function setFromDatet($fromDate): void
    {
        $this->fromDate = $fromDate;
    }

    /**
     * @return string
     */
    public function getCycle(): ?string
    {
        return '';
    }

    /**
     * @param string $cycle
     */
    public function setCycle(string $cycle = null): void
    {
        return;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name = null): void
    {
        $this->name = $name;
    }


    /**
     * @return LogBookSetup[]
     */
    public function getSetup(): ?array
    {
        return $this->setups;
    }

    /**
     * @param LogBookSetup[] $setups
     */
    public function setSetup(LogBookSetup $setups = null): void
    {
        $this->setups = $setups;
    }


}