<?php
/**
 * User: ashamis
 * Date: 07.05.19
 * Time: 13:49
 */

namespace App\Doctrine;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\SqlWalker;

class MatchAgainst extends \Doctrine\ORM\Query\AST\Functions\FunctionNode
{
    /** @var array $columns */
    public $columns = array();
    /** @var InputParameter $needle */
    public $needle;
    /** @var Literal $mode */
    public $mode;

    /**
     * @param \Doctrine\ORM\Query\Parser $parser
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser): void
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

    /**
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $haystack = null;
        $first = true;
        foreach ($this->columns as $column) {
            $first ? $first = false : $haystack .= ', ';
            $haystack .= $column->dispatch($sqlWalker);
        }

        if ($this->mode) {
            $query = 'MATCH(' . $haystack . ') AGAINST (' . $this->needle->dispatch($sqlWalker) . ' ' . $this->mode->dispatch($sqlWalker) . ' )';
        } else {
            $query = 'MATCH(' . $haystack . ') AGAINST (' . $this->needle->dispatch($sqlWalker) . ' IN BOOLEAN MODE)';
        }
        return $query;
    }
}