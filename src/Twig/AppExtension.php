<?php

namespace App\Twig;

use App\Entity\LogBookMessageType;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class AppExtension extends AbstractExtension
{
    private $parser;

    /**
     * Define twig filters
     * @return array|\Twig_Filter[]
     */
    public function getFilters(): array
    {
        return array(
            new Twig_SimpleFilter('ExecutionTimeInHours', array($this, 'ExecutionTimeInHours')),
            new Twig_SimpleFilter('ExecutionTimeGeneric', array($this, 'ExecutionTimeGeneric')),
            new Twig_SimpleFilter('executionTimeGenericShort', array($this, 'executionTimeGenericShort')),
            new Twig_SimpleFilter('TimeToHour', array($this, 'TimeToHour')),
            new Twig_SimpleFilter('getPercentage', array($this, 'getPercentage')),
            new Twig_SimpleFilter('cast_to_array', array($this, 'cast_to_array')),
            new Twig_SimpleFilter('pre_print_r', array($this, 'pre_print_r'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('md2html', array($this, 'markdownToHtml'), array('is_safe' => array('html'))),
            new Twig_SimpleFilter('time_ago', function ($time) { return $this->ExecutionTimeInHours(time() - $time);}),
            new TwigFilter('filter_name', [$this, 'doSomething'], ['is_safe' => ['html']]),
        );
    }

    /**
     * Define TWIG function
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ExecutionTimeGeneric', array($this, 'ExecutionTimeGeneric')),
            new TwigFunction('executionTimeGenericShort', array($this, 'executionTimeGenericShort')),
            new TwigFunction('relativeTime', array($this, 'relativeTime')),
            new TwigFunction('passRateToColor', [$this, 'passRateToColor']),
            new TwigFunction('shortString', [$this, 'shortString']),
            new TwigFunction('verdictToBadge', [$this, 'verdictToBadge']),
            new TwigFunction('getPercentage', [$this, 'getPercentage']),
            new TwigFunction('logTypeToTableColor', [$this, 'logTypeToTableColor']),
            new Twig_SimpleFunction('inarray', array($this, 'inArray')),
        ];
    }

    /**
     * Receive input, if string len > len, cut the string to the len and return with postfix
     * @param string $input
     * @param int $len
     * @param string $postFix
     * @return string
     */
    public function shortString(string $input, int $len = 20, string $postFix = '...'): string
    {
        if (\strlen($input) > $len) {
            return substr($input, 0, $len) . $postFix;
        }
        return $input;
    }

    /**
     * @param LogBookMessageType $msgType
     * @return string
     */
    public function logTypeToTableColor(LogBookMessageType $msgType): string
    {
        $ret = '';
        $tmp = strtolower($msgType->getName());
        switch ($tmp) {
            case 'pass':
                $ret = 'log-success';
                break;
            case 'fail':
                $ret = 'log-fail';
                break;
            case 'error':
                $ret = 'log-error';
                break;
            case 'info':
                $ret = 'log-info';
                break;
            case 'warning':
                $ret = 'log-warning';
                break;
            case 'debug':
                $ret = 'log-debug';
                break;
            case 'critical':
                $ret = 'log-critical';
                break;
            case 'test_na':
                $ret = 'log-na';
                break;
            default:
                break;
        }
        return $ret;
    }

    /**
     * Return classes in percentage range
     * @param $passRate - percentage
     * @return string
     */
    public function passRateToColor($passRate): string
    {
        if ($passRate >= 100) {
            $ret = 'text-success font-bold';
        } elseif ($passRate >= 80) {
            $ret = 'text-success';
        } elseif ($passRate >= 70) {
            $ret = 'text-primary font-bold';
        } elseif ($passRate >= 60) {
            $ret = 'text-primary';
        } elseif ($passRate >= 50) {
            $ret = 'text-muted font-bold';
        } elseif ($passRate >= 40) {
            $ret = 'text-muted';
        } elseif ($passRate >= 30) {
            $ret = 'text-warning font-bold';
        } elseif ($passRate >= 20) {
            $ret = 'text-warning';
        } elseif ($passRate < 10) {
            $ret = 'text-danger font-bold';
        } else{
            $ret = 'text-danger';
        }
        return $ret;
    }

    /**
     * @param $verdict
     * @return string
     */
    public function verdictToBadge($verdict): string
    {
        $ret = '';
        $tmp_verdict = strtolower($verdict);
        switch ($tmp_verdict) {
            case 'pass':
                $ret = 'badge-success';
                break;
            case 'fail':
                $ret = 'badge-danger';
                break;
            case 'error':
                $ret = 'badge-warning';
                break;
            case 'test_na':
                $ret = 'badge-info';
                break;
            default:
                break;
        }
        return $ret;
    }

    /**
     * @param mixed $variable
     * @param array $arr
     *
     * @return bool
     */
    public function inArray($variable, $arr): bool
    {
        return \in_array($variable, $arr, true);
    }

    /**
     * @param $obj
     * @return string
     */
    public function pre_print_r($obj): string
    {
        return '<pre>' . print_r($obj, true) . '</pre>';
    }

    /**
     * @param $stdClassObject
     * @return array
     * @throws ReflectionException
     */
    public function cast_to_array($stdClassObject): array
    {
        $array = array();
        try {
            $reflectionClass = new ReflectionClass(\get_class($stdClassObject));

            foreach ($reflectionClass->getProperties() as $property) {
                $property->setAccessible(true);
                $array[$property->getName()] = $property->getValue($stdClassObject);
                $property->setAccessible(false);
            }
        } catch (Exception  $ex) {
        }
        return $array;
    }

//
//    public function __construct(Markdown $parser = null)
//    {
//        $this->parser = $parser;
//    }

    /**
     * @param $valueOf
     * @param $valueFrom
     * @param int $precision
     * @return float|int
     */
    public function getPercentage($valueOf, $valueFrom, $precision = 2)
    {
        $ret = 0;
        try {
            if ($valueFrom>0) {
                $ret = ($valueOf*100)/$valueFrom;
            }
            $ret = round($ret,$precision);
        }
        catch (Exception $ex) {
        }
        return $ret;
    }

    /**
     * @param $time
     * @return string
     */
    public function ExecutionTimeInHours($time): string
    {
        $seconds  =   $time%60;
        $minutes  =   ($time/60)%60;
        $hours    =   number_format (floor($time/60/60));
        $min_print = sprintf('%02d', $minutes);
        if ($min_print === '00') {
            return ($hours . 'h');
        }
        return ($hours . 'h ' . sprintf('%02d', $minutes) . 'm');
    }

    /**
     * Print time diff for two datetimes
     * @param \DateTime $timeStart
     * @param \DateTime $current
     * @return string
     */
    public function relativeTime(\DateTime $timeStart, \DateTime $current): string
    {
        $diff = abs($timeStart->getTimestamp() - $current->getTimestamp());
        return $this->executionTimeGenericShort($diff);
    }

    /**
     * @param $time
     * @return string
     */
    public function executionTimeGenericShort(int $time): string
    {
        $seconds  =   $time%60;
        $minutes  =   ($time/60)%60;
        $hours    =   number_format (floor($time/60/60));
        $hour_print = sprintf('%d',$hours);
        $min_print = sprintf('%02d',$minutes);
        $sec_print = sprintf('%02d',$seconds);
        if ($hours > 0) {
            $ret = sprintf('%s:%s:%s', $hour_print, $min_print, $sec_print);
        } else {
            $ret = sprintf('%s:%s', $min_print, $sec_print);
        }
        return $ret;
    }

    /**
     * @param $time
     * @return string
     */
    public function ExecutionTimeGeneric(int $time): string
    {
        $seconds  =   $time%60;
        $minutes  =   ($time/60)%60;
        $hours    =   number_format (floor($time/60/60));
        $hour_print = sprintf('%dh',$hours);
        $min_print = sprintf('%02dm',$minutes);
        $sec_print = sprintf('%02ds',$seconds);
        if ($hours > 0) {
            $ret = sprintf('%s %s %s', $hour_print, $min_print, $sec_print);
        } else {
            $ret = sprintf('%s %s', $min_print, $sec_print);
        }
        return $ret;
    }

    /**
     * @param $time
     * @return integer
     */
    public function TimeToHour($time): int
    {
        $minutes  =   ($time/60)%60;
        $hours    =   floor($time/60/60);
//        if($minutes > 30){
//            $hours += 1;
//        }
        return $time;
    }

    public function markdownToHtml($content)
    {
        if ($this->parser === null) {
            $this->parser = new Markdown();
        }
        return $this->parser->toHtml($content);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'twig_common';
    }
}
