<?php

namespace App\Twig;

use App\Entity\LogBookMessageType;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Utils\LogBookCommon;

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
            new TwigFilter('stringToColor', array($this, 'stringToColor')),
            new TwigFilter('ExecutionTimeInHours', array($this, 'ExecutionTimeInHours')),
            new TwigFilter('ExecutionTimeGeneric', array($this, 'ExecutionTimeGeneric')),
            new TwigFilter('executionTimeGenericShort', array($this, 'executionTimeGenericShort')),
            new TwigFilter('TimeToHour', array($this, 'TimeToHour')),
            new TwigFilter('getPercentage', array($this, 'getPercentage')),
            new TwigFilter('testFilterTestToBr', array($this, 'testFilterTestToBr')),
            new TwigFilter('jiraKey', array($this, 'jiraKey')),
            new TwigFilter('jiraKeyToUrl', array($this, 'jiraKeyToUrl')),
            new TwigFilter('jiraLabelToUrl', array($this, 'jiraLabelToUrl')),
            new TwigFilter('cast_to_array', array($this, 'cast_to_array')),
            new TwigFilter('pre_print_r', array($this, 'pre_print_r'), array('is_safe' => array('html'))),
            new TwigFilter('md2html', array($this, 'markdownToHtml'), array('is_safe' => array('html'))),
            new TwigFilter('arrayToString', array($this, 'arrayToString')),
            new TwigFilter('time_ago', function ($time) { return $this->ExecutionTimeInHours(time() - $time);}),
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
            new TwigFunction('stringToColor', array($this, 'stringToColor')),
            new TwigFunction('ExecutionTimeGeneric', array($this, 'ExecutionTimeGeneric')),
            new TwigFunction('executionTimeGenericShort', array($this, 'executionTimeGenericShort')),
            new TwigFunction('relativeTime', array($this, 'relativeTime')),
            new TwigFunction('passRateToColor', [$this, 'passRateToColor']),
            new TwigFunction('failRateToColor', [$this, 'failRateToColor']),
            new TwigFunction('shortString', [$this, 'shortString']),
            new TwigFunction('verdictToBadge', [$this, 'verdictToBadge']),
            new TwigFunction('isUrl', [$this, 'isUrl']),
            new TwigFunction('formatBytes', [$this, 'formatBytes']),
            new TwigFunction('getPercentage', [$this, 'getPercentage']),
            new TwigFunction('logTypeToTableColor', [$this, 'logTypeToTableColor']),
            new TwigFunction('inarray', array($this, 'inArray')),
            new TwigFunction('arrayToString', array($this, 'arrayToString')),
            new TwigFunction('parseDomain', array($this, 'parseDomain')),
            new TwigFunction('cleanAutotestFinalMessage', array($this, 'cleanAutotestFinalMessage')),
            new TwigFunction('jiraKey', array($this, 'jiraKey')),
            new TwigFunction('jiraKeyToUrl', array($this, 'jiraKeyToUrl')),
            new TwigFunction('jiraLabelToUrl', array($this, 'jiraLabelToUrl')),
            new TwigFunction('testFilterTestToBr', array($this, 'testFilterTestToBr')),

        ];
    }

    public function jiraLabelToUrl($label)
    {
        try {
            return getenv('JIRA_HOST') . '/issues/?jql=labels%20%3D%20' . $label;
        } catch (\Throwable $ex) {}
        return '';
    }
    public function jiraKeyToUrl($key)
    {
        try {
            return getenv('JIRA_HOST') . '/browse/' . $key;
        } catch (\Throwable $ex) {}
        return '';
    }

    public function testFilterTestToBr(string $input=null, string $replace='<br/>'): string
    {
        if ($input !== null) {
            $input = str_replace(' ', ',', $input);
            $input = str_replace('\n', ',', $input);
            $input = str_replace(', ', ',', $input);
        } else {
            $input = '';
        }
        $output = str_replace(',', $replace, $input);
        return $output;
    }

    public function parseDomain(string $input= null): string
    {
        if ($input !== null && strlen($input) > 5) {
            return parse_url($input, PHP_URL_HOST);
        }
        return '';
    }

    /**
     * @param int $size
     * @param int $precision
     * @return string
     */
    public function formatBytes($size = 0, $precision = 2): string
    {
        $base = log($size, 1024);
        $suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');
        try {
            $suffix = $suffixes[(int)floor($base)];
        } catch (\Exception $ex) {
            $suffix = 'b';
        }
        try {
            if ($size > 0) {
                $value = 1024 ** ($base - floor($base));
            } else {
                $value = 0;
            }
        } catch (\Exception $ex) {
            $value = $size;
        }

        return round($value, $precision) . ' ' . $suffix;
    }

    /**
     * Receive input, if string len > len, cut the string to the len and return with postfix
     * @param string $input
     * @param int $len
     * @param string $postFix
     * @return string
     */
    public function shortString(string $input = null, int $len = 20, string $postFix = '...'): string
    {
        if ($input === null) {
            return '';
        }
        if (\strlen($input) > $len) {
            return substr($input, 0, $len) . $postFix;
        }
        return $input;
    }

    public function isUrl($string): bool
    {
        if (filter_var($string, FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
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
            case 'step':
                $ret = 'log-step';
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

    public function jiraKey(string $jiraUrl = null): string
    {
        $ret = '';
        try {
            $tmp = explode('/', $jiraUrl);
            foreach ($tmp as $key => $val) {
                if ($val === 'browse') {
                    if (array_key_exists($key+1, $tmp)){
                        $ret = $tmp[$key + 1];
                    } else {
                        $ret = '';
                    }
                    break;
                }
            }
        } catch (\Throwable $ex) {}
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

    public static function parser_BW_TPT($matches){
        $percent = AppExtension::getPercentageStatic($matches[2], $matches[3], 0);
        return $matches[1] . ' value ' . $percent .  '% ,below expected BW ' . $matches[3] . $matches[4];
    }

    public static function parser_PerfTest($matches){
        return 'preformace The value is larger then expected';
        //return '' . round($matches[1], 0).  ' is larger then ' . round($matches[2], 0).  ' * ' . round($matches[3], 2).  ' =' . round($matches[4], 0);
    }

    /**
     * @param string|null $input
     * @return string
     */
    public static function cleanAutotestFinalMessage(string $input=null): string
    {
        $ret_val = substr($input, 0 , 5);
        try {
            preg_match_all("/(FAIL|ERROR|TEST_NA) .*\d\d\:\d\d\:\d\d\s+(.*)/", $input, $out, PREG_PATTERN_ORDER);

            if (count($out) >= 3 && count($out[2]) >= 1 && strlen($out[2][0]) > 10 ) {
                $ret_val = $out[2][0];

            }
            preg_match_all("/(Exception escaped control file, job aborting:)s*(.*)/", $input, $out, PREG_PATTERN_ORDER);

            if (count($out) >= 3 && count($out[2]) >= 1 && strlen($out[2][0]) > 10 ) {
                $ret_val = $out[2][0];
            }
            $r = preg_match_all("/Command \<.*EXIT_CODE\=\d+\,(.*)\:\s(.*)/", $ret_val, $out, PREG_PATTERN_ORDER);
            if ($r) {
                $ret_val = $out[2][0];
            }
            $ret_val = str_replace('> failed,', '-',$ret_val);
            $ret_val = str_replace(' || DURATION=0', '',$ret_val);
            $ret_val = str_replace('Command <&&', '',$ret_val);
            $ret_val = str_replace('Command <', '',$ret_val);
            $ret_val = str_replace('0000000001', '',$ret_val);
            $ret_val = str_replace('Total Actual ', '',$ret_val);
            $ret_val = str_replace('[+] ', '',$ret_val);
            $ret_val = str_replace('/nonrelease_content', '',$ret_val);
            $ret_val = str_replace('0000000002', '',$ret_val);
            $ret_val = str_replace('0000000003', '',$ret_val);
            $ret_val = str_replace('9999999999', '',$ret_val);
            $ret_val = str_replace('==== STDOUT ====', 'stdout',$ret_val);
            $ret_val = str_replace('==== STDERR ====', 'stderr',$ret_val);
            $ret_val = preg_replace('/\/localdrive\/users\/[\d|\w|\_|\-|\.]+\/jenkins_ws\/workspace\/CI_workspace/', '/WS/',$ret_val);
            $ret_val = preg_replace('/\. PID: \d+\,[\d|\-|\,]+/', '',$ret_val);
            $ret_val = preg_replace('/\. PID: \d+/', '',$ret_val);
            $ret_val = preg_replace('/\/bin\/bash\: line 1\: \d* /', '',$ret_val);
            $ret_val = preg_replace('/CmdResult\:\:EXIT_CODE\=\d+ \|\| CMD\=\[.*/', '',$ret_val);
            $ret_val = preg_replace('/\t+/', ' ',$ret_val);
            $ret_val = preg_replace('/\s+/', ' ',$ret_val);
            $ret_val = preg_replace('/\-+/', '-',$ret_val);
            $ret_val = preg_replace('/\++/', '+',$ret_val);
            $ret_val = preg_replace('/\=+/', '=',$ret_val);
            $ret_val = preg_replace('/\-gtest_filter\=[\.|\/|\_|\-|\d|\w|\=|\s|\*|\"|\:]+\- EXIT_CODE/', ' EXIT_CODE',$ret_val);



            try {
                $ret_val = preg_replace_callback('/(.*) value (\d+[\.|\,|\d]*) ,below expected BW (\d+)(.*)/', array('self', 'parser_BW_TPT'), $ret_val);
            } catch (\Throwable $ex) {

            }
            try {
                $ret_val = preg_replace_callback('/preformace_tests FAILED current result (\d+[\.|\,|\d]*) is larger then (\d+[\.|\,|\d]*) \* (\d+[\.|\,|\d]*) \=(\d+[\.|\,|\d]*)/', array('self', 'parser_PerfTest'), $ret_val);
            } catch (\Throwable $ex) {

            }
            trim($ret_val);
        } catch (\Throwable $ex) {

        }

        return $ret_val;
    }

    /**
     * Return classes in percentage range
     * @param $failRate - percentage
     * @return string
     */
    public function failRateToColor($failRate): string
    {
        return $this->passRateToColor(100 - $failRate);
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

    public function arrayToString($array): string
    {
        $ret = '';
        try{
            foreach ($array as $key => $val) {
                if (mb_strlen($ret) > 0) {
                    $ret .= ';' . $val;
                } else {
                    $ret = $val;
                }

            }
        }catch (\Throwable $ex) {

        }
        return $ret;
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


    public function stringToColor($input=null): string
    {
        $ret = '000000';
        try {
            $ret = sprintf('%d%d', LogBookCommon::stringDigitsToInt($input),  LogBookCommon::stringToInt($input));
            if (strlen($ret) < 6) {
                $ret = sprintf('%d%d%d', LogBookCommon::stringDigitsToInt($input),  LogBookCommon::stringToInt($input), LogBookCommon::stringDigitsToInt($input));
            }
            if (strlen($ret) < 6) {
                $ret = sprintf('%d%d%d%d', LogBookCommon::stringDigitsToInt($input),  LogBookCommon::stringToInt($input), LogBookCommon::stringDigitsToInt($input), LogBookCommon::stringToInt($input));
            }
            if (strlen($ret) < 6) {
                $ret = sprintf('%d%d%02d%d', LogBookCommon::stringDigitsToInt($input),  LogBookCommon::stringToInt($input), LogBookCommon::stringDigitsToInt($input), LogBookCommon::stringToInt($input));
            }
            if (strlen($ret) < 6) {
                $ret = sprintf('%d%d%02d%02d', LogBookCommon::stringDigitsToInt($input),  LogBookCommon::stringToInt($input), LogBookCommon::stringDigitsToInt($input), LogBookCommon::stringToInt($input));
            }
            if (strlen($ret) > 6) {
                $ret = substr($ret, 0, 6);
            }
        }
        catch (Exception $ex) {
        }
        return '#'.$ret;
    }

    public static function getPercentageStatic($valueOf, $valueFrom, $precision = 2) {
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
        $seconds = $time%60;
        $minutes = ($time/60)%60;
        $hours = floor($time/60/60);
        $hour_print = sprintf('%dh', $hours);
        $min_print = sprintf('%02dm', $minutes);
        $sec_print = sprintf('%02ds', $seconds);
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
