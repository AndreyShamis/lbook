<?php

namespace App\Twig;

use ArrayObject;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    private $parser;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ExecutionTimeInHours', array($this, 'ExecutionTimeInHours')),
            new \Twig_SimpleFilter('TimeToHour', array($this, 'TimeToHour')),
            new \Twig_SimpleFilter('getPercentage', array($this, 'getPercentage')),
            new \Twig_SimpleFilter('cast_to_array', array($this, 'cast_to_array')),
            new \Twig_SimpleFilter('pre_print_r', array($this, 'pre_print_r'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('md2html', array($this, 'markdownToHtml'), array('is_safe' => array('html'))
            ),
            new \Twig_SimpleFilter('time_ago', function ($time) {
                return $this->ExecutionTimeInHours(time()-$time);
            }),
            new TwigFilter('filter_name', [$this, 'doSomething'], ['is_safe' => ['html']]),

        );

    }
    public function getFunctions(): array
    {
        return [
            new TwigFunction('function_name', [$this, 'doSomething']),
            new TwigFunction('verdictToBadge', [$this, 'verdictToBadge']),
            new \Twig_SimpleFunction('inarray', array($this, 'inArray')),
        ];
    }

    public function verdictToBadge($verdict){
        $ret = "";
        $tmp_verdict = strtolower($verdict);
        switch ($tmp_verdict){
            case 'pass':
                $ret = "badge-success";
                break;
            case 'fail':
                $ret = "badge-danger";
                break;
            case 'error':
                $ret = "badge-warning";
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
        return in_array($variable, $arr);
    }

    public function pre_print_r($obj){
        return '<pre>' . print_r($obj, true) . '</pre>';
    }

    public function cast_to_array($stdClassObject)
    {
        $array = array();
        try{
            $reflectionClass = new ReflectionClass(get_class($stdClassObject));

            foreach ($reflectionClass->getProperties() as $property) {
                $property->setAccessible(true);
                $array[$property->getName()] = $property->getValue($stdClassObject);
                $property->setAccessible(false);
            }
        }
        catch (Exception  $ex){
            print_r($ex);
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
    function getPercentage($valueOf, $valueFrom, $precision=2){
        $ret = 0;
        try{
            if($valueFrom>0) {
                $ret = ($valueOf*100)/$valueFrom;
            }
            $ret = round($ret,$precision);
        }
        catch (Exception $ex){

        }
        return($ret);
    }

    /**
     * @param $time
     * @return string
     */
    function ExecutionTimeInHours($time){
        $seconds  =   $time%60;
        $minutes  =   ($time/60)%60;
        $hours    =   number_format (floor($time/60/60));
        $min_print = sprintf('%02d',$minutes);
        if($min_print == "00"){
            return ($hours  . "h");
        }
        return ($hours  . "h " . sprintf('%02d',$minutes) . "m");
        //return ($mon . "m " . $days . "d " . sprintf('%02d',$hours)  . "h " . sprintf('%02d',$minutes) . "m " . sprintf('%02d',$seconds) ."s");
    }


    /**
     * @param $time
     * @return integer
     */
    function TimeToHour($time){
        $minutes  =   ($time/60)%60;
        $hours    =   floor($time/60/60);
//        if($minutes > 30){
//            $hours += 1;
//        }
        return $time;
    }

    public function markdownToHtml($content) {
//        echo "<pre>";
//        print_r($content);
//        echo "</pre>";
//        exit;
        if($this->parser === null){
            $this->parser = new Markdown();
        }
        return $this->parser->toHtml($content);
    }




    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'twig_common';
        // TODO: Implement getName() method.
    }

    public function doSomething($value)
    {
        // ...
    }
}
