<?php
/**
 * User: ashamis
 * Date: 07.05.19
 * Time: 13:49
 */


/**
 * MatchAgainst
 *
 * Definition for MATCH AGAINST MySQL instruction to be used in DQL Queries
 *
 * Usage: MATCH_AGAINST(column, :text)
 * Config: $config->addCustomStringFunction("MATCH_AGAINST",
"\DqlFunctions\MatchAgainst");
 *
 * @author gbrunacci at _ gmail _ dot c o m
 */
namespace App\Doctrine;

use Doctrine\ORM\Query\Lexer;
use Doctrine\Orm\Query\AST\Literal;

class MatchAgainst extends \Doctrine\ORM\Query\AST\Functions\FunctionNode {

//    public $columns = array();
//    public $needle;
//    public function parse(\Doctrine\ORM\Query\Parser $parser)
//    {
//        $parser->match(Lexer::T_IDENTIFIER);
//        $parser->match(Lexer::T_OPEN_PARENTHESIS);
//
//        do {
//            $this->columns[] = $parser->StateFieldPathExpression();
//            $parser->match(Lexer::T_COMMA);
//        }
//        while (!$parser->getLexer()->isNextToken(Lexer::T_INPUT_PARAMETER));
//
//        // Got an input parameter
//        $this->needle = $parser->InputParameter();
//
//        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
//    }
//    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
//    {
//        $haystack = null;
//        $first = true;
//        foreach ($this->columns as $column) {
//            $first ? $first = false : $haystack .= ', ';
//            $haystack .= $column->dispatch($sqlWalker);
//        }
//        return "MATCH(" .
//            $haystack .
//            ") AGAINST (" .
//            $this->needle->dispatch($sqlWalker) .
//            " IN NATURAL LANGUAGE MODE )";
//    }



//    public $_ma_column = null;
//    public $_ma_value = null;
//
//    public function parse(\Doctrine\ORM\Query\Parser $parser)
//    {
//        $parser->match(Lexer::T_IDENTIFIER);
//        $parser->match(Lexer::T_OPEN_PARENTHESIS);
//        $this->_ma_column = $parser->StringPrimary();
//        $parser->match(Lexer::T_COMMA);
//        $this->_ma_value = $parser->StringPrimary();
//        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
//    }
//
//    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
//    {
//        return "MATCH(" .
//            $this->_ma_column->dispatch($sqlWalker) .
//            ") AGAINST ('" .
//            $this->_ma_value->value .
//            "')";
//    }


    public $columns = array();
    public $needle;
    public $mode;
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        do {
            $this->columns[] = $parser->StateFieldPathExpression();
            $parser->match(Lexer::T_COMMA);
        }
        while ($parser->getLexer()->isNextToken(Lexer::T_IDENTIFIER));
        $this->needle = $parser->InParameter();
        while ($parser->getLexer()->isNextToken(Lexer::T_STRING)) {
            $this->mode = $parser->Literal();
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $haystack = null;
        $first = true;
        foreach ($this->columns as $column) {
            $first ? $first = false : $haystack .= ', ';
            $haystack .= $column->dispatch($sqlWalker);
        }
        $query = 'MATCH(' . $haystack . ') AGAINST (' . $this->needle->dispatch($sqlWalker);
        if($this->mode) {
            $query .= ' ' . $this->mode->dispatch($sqlWalker) . ' )';
        } else {
            $query .= ' )';
        }
        return $query;
    }
}