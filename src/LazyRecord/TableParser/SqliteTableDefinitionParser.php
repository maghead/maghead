<?php
namespace LazyRecord\TableParser;
use Exception;
use stdClass;

class Token 
{
    public $type;

    public $val;

    public function __construct($type, $val) {
        $this->type = $type;
        $this->val = $val;
    }
}

class SqliteTableDefinitionParser
{

    public $p;

    public $str;


    public function __construct() {
    }



    protected function looksLikeTableConstraint() {
        return $this->test(['CONSTRAINT','PRIMARY','UNIQUE','FOREIGN','CHECK']);
    }

    protected function tryParseScalar() {

        $this->skipSpaces();

        if (preg_match('/-?\d+  \. \d+/x', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);
            return new Token('double', doubleval($matches[0]));
        } else if (preg_match('/-?\d+/x', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);
            return new Token('int', intval($matches[0]));
        } else if ($this->cur() == "'") {
            $this->advance();

            $p = $this->p;

            while(!$this->metEnd()) {
                if ($this->cur() == '\\') {
                    $this->advance();
                    $this->advance(); // skip the char after slash
                }
                if ($this->cur() == "'") {
                    $this->advance();
                    break;
                }
            }
            $string = substr($this->str, $p, ($this->p - 1) - $p);
            return new Token('string', $string);
        }
    }

    public function parseColumnDefinitions($str) {
        $this->str = $str;
        $this->strlen = strlen($str);
        $this->p = 0;
        $this->skipSpaces();

        $tableDef = new stdClass;
        $tableDef->columns = [];

        while (! $this->metEnd()) {
            while (! $this->metEnd())
            {
                $tryPos = $this->p;
                $foundTableConstraint = $this->tryParseTableConstraints();
                $this->rollback($tryPos);
                if ($foundTableConstraint) {
                    break;
                }

                $identifier = $this->tryParseIdentifier();
                if (!$identifier) {
                    break;
                }

                $column = new stdClass;
                $column->name = $identifier->val;

                $this->skipSpaces();
                $typeName = $this->tryParseTypeName();
                if ($typeName) {
                    $this->skipSpaces();
                    $column->type = $typeName->val;
                    $precision = $this->tryParseTypePrecision();
                    if ($precision && $precision->val) {
                        if (count($precision->val) == 2) {
                            $column->length = $precision->val[0];
                            $column->decimals = $precision->val[1];
                        } else if (count($precision->val) == 1) {
                            $column->length = $precision->val[0];
                        }
                    }

                    while($constraintToken = $this->tryParseColumnConstraint()) {

                        if ($constraintToken->val == 'PRIMARY') {

                            $this->tryParseKeyword(['KEY']);

                            $column->primary = true;

                            if ($orderingToken = $this->tryParseKeyword(['ASC', 'DESC'])) {
                                $column->ordering = $orderingToken->val;
                            }

                            if ($this->tryParseKeyword(['AUTOINCREMENT'])) {
                                $column->autoIncrement = true;
                            }
                        } else if ($constraintToken->val == 'UNIQUE') {

                            $column->unique = true;

                        } else if ($constraintToken->val == 'NOT NULL') {

                            $column->notNull = true;

                        } else if ($constraintToken->val == 'NULL') {

                            $column->notNull = false;

                        } else if ($constraintToken->val == 'DEFAULT') {

                            // parse scalar
                            if ($scalarToken = $this->tryParseScalar()) {

                                $column->default = $scalarToken->val;

                            } else if ($literal = $this->tryParseKeyword(['CURRENT_TIME', 'CURRENT_DATE', 'CURRENT_TIMESTAMP'], 'literal')) {

                                $column->default = $literal;

                            } else if ($null = $this->tryParseKeyword(['NULL'])) {

                                $column->default = NULL;

                            } else if ($null = $this->tryParseKeyword(['TRUE'])) {

                                $column->default = TRUE;

                            } else if ($null = $this->tryParseKeyword(['FALSE'])) {

                                $column->default = FALSE;

                            } else {
                                throw new Exception("Can't parse literal: " . $this->currentWindow() );
                            }

                        } else if ($constraintToken->val == 'COLLATE') {

                            $collateName = $this->tryParseIdentifier();
                            $column->collate = $collateName->val;

                        } else if ($constraintToken->val == 'REFERENCES') {

                            $tableNameToken = $this->tryParseIdentifier();

                            $this->advance('(');
                            $columnNames = $this->parseColumnNames();
                            $this->advance(')');

                            $actions = [];
                            if ($this->tryParseKeyword(['ON'])) {
                                while ($onToken = $this->tryParseKeyword(['DELETE', 'UPDATE'])) {
                                    $on = $onToken->val;
                                    $actionToken = $this->tryParseKeyword(['SET NULL', 'SET DEFAULT', 'CASCADE', 'RESTRICT', 'NO ACTION']);
                                    $actions[$on] = $actionToken->val;
                                }
                            }

                            $column->references = (object) array(
                                'table' => $tableNameToken->val,
                                'columns' => $columnNames,
                                'actions' => $actions,
                            );
                        }
                        $this->skipSpaces();
                    }
                }

                $this->skipSpaces();
                if ($this->metComma()) {
                    $this->skipComma();
                    $this->skipSpaces();
                    $tableDef->columns[] = $column;
                } else {
                    $tableDef->columns[] = $column;
                }
            }

            while(!$this->metEnd() && $tableConstraints = $this->tryParseTableConstraints()) {
                $tableDef->tableConstraints = $tableConstraints;
                $this->skipSpaces();
                if ($this->metComma()) {
                    $this->skipComma();
                    $this->skipSpaces();
                }
            }
            $this->advance();
        }
        return $tableDef;
    }

    protected function rollback($p)
    {
        $this->p = $p;
    }

    protected function test($str)
    {
        if (is_array($str)) {
            foreach($str as $s) {
                if ($this->test($s)) {
                    return strlen($s);
                }
            }
        } else if (is_string($str)) {
            $p = stripos($this->str, $str, $this->p );
            return $p === $this->p ? strlen($str) : false;
        } else {
            throw new Exception('Invalid argument type');
        }
    }

    protected function skip($tokens)
    {
        while ($len = $this->test($tokens)) {
            $this->p += $len;
        }
    }

    protected function parseColumnNames()
    {
        $columnNames = [];
        while($identifier = $this->tryParseIdentifier()) {
            $columnNames[] = $identifier->val;
            $this->skipSpaces();
            if ($this->metComma()) {
                $this->skipComma();
                $this->skipSpaces();
            } else {
                break;
            }
        }
        return $columnNames;
    }

    protected function tryParseTableConstraints()
    {
        $tableConstraints = null;
        while($tableConstraintKeyword = $this->tryParseKeyword(['PRIMARY','UNIQUE', 'CONSTRAINT', 'CHECK', 'FOREIGN'])) {
            $tableConstraint = new stdClass;

            if (in_array($tableConstraintKeyword->val, ['PRIMARY', 'FOREIGN'])) {
                $this->skipSpaces();
                $this->tryParseKeyword(['KEY']);
            }

            if ($tableConstraintKeyword->val == 'PRIMARY') {
                if ($indexColumns = $this->tryParseIndexColumns()) {
                    $tableConstraint->primaryKey = $indexColumns;
                }
            } else if ($tableConstraintKeyword->val == 'UNIQUE') {
                if ($indexColumns = $this->tryParseIndexColumns()) {
                    $tableConstraint->unique = $indexColumns;
                }
            } else if ($tableConstraintKeyword->val == 'CONSTRAINT') {
                $this->skipSpaces();
                $constraintName = $this->tryParseIdentifier();
                $tableConstraint->name = $constraintName->val;
            } else if ($tableConstraintKeyword->val == 'FOREIGN') {
                if ($this->cur() == '(') {
                    $this->advance('(');
                    $tableConstraint->foreignKey = $this->parseColumnNames();
                    if ($this->cur() == ')') {
                        $this->advance(')');
                    } else {
                        throw new Exception('Unexpected token: ' . $this->currentWindow());
                    }

                } else {
                    throw new Exception('Unexpected token: ' . $this->currentWindow());
                }
            }
            $tableConstraints[] = $tableConstraint;
        }
        return $tableConstraints;
    }

    protected function currentWindow($window = 20) 
    {
        return "=>" . substr($this->str, $this->p, $window) . "....\n";
    }

    protected function metEnd() 
    {
        return $this->p + 1 >= $this->strlen;
    }

    protected function skipSpaces() {
        while(!$this->metEnd() && in_array($this->str[$this->p],[' ',"\t","\n"])) {
            $this->p++;
        }
    }

    protected function metComma() {
        return !$this->metEnd() && $this->str[$this->p] == ',';
    }

    protected function skipComma() {
        if (!$this->metEnd() && $this->str[ $this->p ] == ',') {
            $this->p++;
        }
    }

    protected function tryParseColumnConstraint() 
    {
        return $this->tryParseKeyword(['PRIMARY', 'UNIQUE', 'NOT NULL', 'NULL', 'DEFAULT', 'COLLATE', 'REFERENCES'], 'constraint');
    }

    protected function cur() {
        return $this->str[ $this->p ];
    }

    protected function advance($c = NULL) {
        if (!$this->metEnd()) {
            if ($c) {
                if ($c == $this->str[$this->p]) {
                    $this->p++;
                }
            } else {
                $this->p++;
            }
        }
    }

    protected function tryParseKeyword(array $keywords, $as = 'keyword')
    {
        $this->skipSpaces();
        $this->sortKeywordsByLen($keywords);

        foreach($keywords as $keyword) {
            if (($p2 = stripos($this->str, $keyword, $this->p)) && $p2 == $this->p) {
                $this->p += strlen($keyword);
                return new Token($as, $keyword);
            }
        }
        return null;
    }

    protected function tryParseIndexColumns()
    {
        $c = $this->cur();
        if ($c == '(') {
            $this->advance();
            $this->skipSpaces();
            $indexColumns = [];
            while($columnName = $this->tryParseIdentifier()) {
                $indexColumn = new stdClass;
                $indexColumn->name = $columnName->val;

                if ($this->tryParseKeyword(['COLLATE'])) {
                    $this->skipSpaces();
                    if ($collationName = $this->tryParseIdentifier()) {
                        $indexColumn->collationName = $collationName->val;
                    }
                }

                if ($ordering = $this->tryParseKeyword(['ASC', 'DESC'])) {
                    $indexColumn->ordering = $ordering->val;
                }

                $this->skipSpaces();
                if ($this->metComma()) {
                    $this->skipComma();
                }
                $this->skipSpaces();
                $indexColumns[] = $indexColumn;
            }
            if ($this->cur() == ')') {
                $this->advance();
            }
            return $indexColumns;
        }
        return null;
    }


    protected function tryParseTypePrecision() 
    {
        $c = $this->str[ $this->p ];
        if ($c == '(') {
            if (preg_match('/\( \s* (\d+) \s* , \s* (\d+) \s* \)/x', $this->str, $matches, 0, $this->p )) {
                $this->p += strlen($matches[0]);
                return new Token('precision', [intval($matches[1]), intval($matches[2])]);
            }
            else if (preg_match('/\(  \s* (\d+) \s* \)/x', $this->str, $matches, 0, $this->p )) {
                $this->p += strlen($matches[0]);
                return new Token('precision', [intval($matches[1])]);
            } else {
                throw new Exception('Invalid precision syntax');
            }
        }
        return NULL;
    }


    protected function sortKeywordsByLen(array & $keywords)
    {
        usort($keywords, function($a,$b) {
            $al = strlen($a);
            $bl = strlen($b);
            if ($al == $bl) {
                return 0;
            } else if ( $al > $bl ){
                return -1;
            } else {
                return 1;
            }
        });
    }


    protected function tryParseTypeName() 
    {
        $intTypes = ['INT', 'INTEGER', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'BIG INT', 'INT2' , 'INT8'];
        $textTypes = ['CHARACTER', 'VARCHAR', 'VARYING CHARACTER', 'NCHAR', 'NATIVE CHARACTER', 'NVARCHAR', 'TEXT', 'CLOB'];
        $blobTypes = ['BLOB', 'NONE'];
        $realTypes = ['REAL', 'DOUBLE', 'DOUBLE PRECISION', 'FLOAT'];
        $numericTypes = ['NUMERIC', 'DECIMAL', 'BOOLEAN', 'DATE', 'DATETIME', 'TIMESTAMP'];

        $allTypes = array_merge($intTypes, $textTypes, $blobTypes, $realTypes, $numericTypes);

        $this->sortKeywordsByLen($allTypes);

        foreach($allTypes as $typeName) {
            // Matched
            if (($p2 = stripos($this->str, $typeName, $this->p)) !== false && $p2 == $this->p) {
                $this->p += strlen($typeName);
                return new Token('type-name', $typeName);
            }
        }
        return NULL;
        // throw new Exception('Expecting type-name');
    }

    protected function tryParseIdentifier() 
    {
        $this->skipSpaces();
        if ($this->str[$this->p] == '`') {
            $this->p++;
            // find the quote pair position
            $p2 = strpos($this->str, '`', $this->p);
            if ($p2 === false) {
                throw new Exception('Expecting identifier quote (`): ' . $this->currentWindow() );
            }
            $token = substr($this->str, $this->p, $p2 - $this->p);
            $this->p = $p2 + 1;
            return new Token('identifier',$token);
        }
        if (preg_match('/^(\w+)/', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);
            return new Token('identifier',$matches[1]);
        }
        return NULL;
        // throw new Exception('Not an identifier: ' . substr($this->str, $this->p));
    }
}
