<?php

namespace Maghead\TableParser;

use Exception;
use stdClass;

class Token
{
    /**
     * @var string
     *
     * Token type
     */
    public $type;

    /**
     * @var mixed
     */
    public $val;

    public function __construct($type, $val)
    {
        $this->type = $type;
        $this->val = $val;
    }
}

/**
 * SQLite Parser for parsing table column definitions:.
 *
 *  CREATE TABLE {identifier} ( columndef, columndef, ... );
 *
 *
 * The syntax follows the official documentation below:
 *
 *  http://www.sqlite.org/lang_createtable.html
 */
class SqliteTableDefinitionParser
{
    /**
     *  @var int
     *  
     *  The default buffer offset
     */
    public $p = 0;

    /**
     * @var string
     *
     * The buffer string for parsing.
     */
    public $str = '';

    public function __construct($str, $offset = 0)
    {
        $this->str = $str;
        $this->strlen = strlen($str);
        $this->p = $offset;
    }

    protected function looksLikeTableConstraint()
    {
        return $this->test(['CONSTRAINT', 'PRIMARY', 'UNIQUE', 'FOREIGN', 'CHECK']);
    }

    public function parse()
    {
        $tableDef = new stdClass();

        $this->skipSpaces();

        $keyword = $this->tryParseKeyword(['CREATE']);

        $this->skipSpaces();

        if ($this->tryParseKeyword(['TEMPORARY', 'TEMP'])) {
            $tableDef->temporary = true;
        }

        $this->skipSpaces();

        $this->tryParseKeyword(['TABLE']);

        $this->skipSpaces();

        if ($this->tryParseKeyword(['IF']) && $this->tryParseKeyword(['NOT']) && $this->tryParseKeyword(['EXISTS'])) {
            $tableDef->ifNotExists = true;
        }

        $tableName = $this->tryParseIdentifier();
        $tableDef->tableName = $tableName->val;

        $this->advance('(');
        $tableDef->columns = $this->parseColumnDefinitions();
        $this->advance(')');

        return $tableDef;
    }

    public function parseColumnDefinitions()
    {
        $this->skipSpaces();

        $tableDef = new stdClass();
        $tableDef->columns = [];

        while (!$this->metEnd()) {
            while (!$this->metEnd()) {
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

                $column = new stdClass();
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
                        } elseif (count($precision->val) == 1) {
                            $column->length = $precision->val[0];
                        }
                    }

                    if (in_array(strtoupper($column->type), self::$intTypes)) {
                        $unsigned = $this->consume('unsigned', 'unsigned');
                    }

                    while ($constraintToken = $this->tryParseColumnConstraint()) {
                        if ($constraintToken->val == 'PRIMARY') {
                            $this->tryParseKeyword(['KEY']);

                            $column->primary = true;

                            if ($orderingToken = $this->tryParseKeyword(['ASC', 'DESC'])) {
                                $column->ordering = $orderingToken->val;
                            }

                            if ($this->tryParseKeyword(['AUTOINCREMENT'])) {
                                $column->autoIncrement = true;
                            }
                        } elseif ($constraintToken->val == 'UNIQUE') {
                            $column->unique = true;
                        } elseif ($constraintToken->val == 'NOT NULL') {
                            $column->notNull = true;
                        } elseif ($constraintToken->val == 'NULL') {
                            $column->notNull = false;
                        } elseif ($constraintToken->val == 'DEFAULT') {

                            // parse scalar
                            if ($scalarToken = $this->tryParseScalar()) {
                                $column->default = $scalarToken->val;
                            } elseif ($literal = $this->tryParseKeyword(['CURRENT_TIME', 'CURRENT_DATE', 'CURRENT_TIMESTAMP'], 'literal')) {
                                $column->default = $literal;
                            } elseif ($null = $this->tryParseKeyword(['NULL'])) {
                                $column->default = null;
                            } elseif ($null = $this->tryParseKeyword(['TRUE'])) {
                                $column->default = true;
                            } elseif ($null = $this->tryParseKeyword(['FALSE'])) {
                                $column->default = false;
                            } else {
                                throw new Exception("Can't parse literal: ".$this->currentWindow());
                            }
                        } elseif ($constraintToken->val == 'COLLATE') {
                            $collateName = $this->tryParseIdentifier();
                            $column->collate = $collateName->val;
                        } elseif ($constraintToken->val == 'REFERENCES') {
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

            while (!$this->metEnd() && $tableConstraints = $this->tryParseTableConstraints()) {
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
            foreach ($str as $s) {
                if ($this->test($s)) {
                    return strlen($s);
                }
            }
        } elseif (is_string($str)) {
            $p = stripos($this->str, $str, $this->p);

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
        while ($identifier = $this->tryParseIdentifier()) {
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
        while ($tableConstraintKeyword = $this->tryParseKeyword(['PRIMARY', 'UNIQUE', 'CONSTRAINT', 'CHECK', 'FOREIGN'])) {
            $tableConstraint = new stdClass();

            if (in_array($tableConstraintKeyword->val, ['PRIMARY', 'FOREIGN'])) {
                $this->skipSpaces();
                $this->tryParseKeyword(['KEY']);
            }

            if ($tableConstraintKeyword->val == 'PRIMARY') {
                if ($indexColumns = $this->tryParseIndexColumns()) {
                    $tableConstraint->primaryKey = $indexColumns;
                }
            } elseif ($tableConstraintKeyword->val == 'UNIQUE') {
                if ($indexColumns = $this->tryParseIndexColumns()) {
                    $tableConstraint->unique = $indexColumns;
                }
            } elseif ($tableConstraintKeyword->val == 'CONSTRAINT') {
                $this->skipSpaces();
                $constraintName = $this->tryParseIdentifier();
                $tableConstraint->name = $constraintName->val;
            } elseif ($tableConstraintKeyword->val == 'FOREIGN') {
                if ($this->cur() == '(') {
                    $this->advance('(');
                    $tableConstraint->foreignKey = $this->parseColumnNames();
                    if ($this->cur() == ')') {
                        $this->advance(')');
                    } else {
                        throw new Exception('Unexpected token: '.$this->currentWindow());
                    }
                } else {
                    throw new Exception('Unexpected token: '.$this->currentWindow());
                }
            }
            $tableConstraints[] = $tableConstraint;
        }

        return $tableConstraints;
    }

    protected function currentWindow($window = 20)
    {
        return '=>'.substr($this->str, $this->p, $window)."....\n";
    }

    protected function metEnd()
    {
        return $this->p + 1 >= $this->strlen;
    }

    protected function skipSpaces()
    {
        while (!$this->metEnd() && in_array($this->str[$this->p], [' ', "\t", "\n"])) {
            ++$this->p;
        }
    }

    protected function metComma()
    {
        return !$this->metEnd() && $this->str[$this->p] == ',';
    }

    protected function skipComma()
    {
        if (!$this->metEnd() && $this->str[ $this->p ] == ',') {
            ++$this->p;
        }
    }

    protected function tryParseColumnConstraint()
    {
        return $this->tryParseKeyword(['PRIMARY', 'UNIQUE', 'NOT NULL', 'NULL', 'DEFAULT', 'COLLATE', 'REFERENCES'], 'constraint');
    }

    protected function cur()
    {
        return $this->str[ $this->p ];
    }

    protected function advance($c = null)
    {
        if (!$this->metEnd()) {
            if ($c) {
                if ($c == $this->str[$this->p]) {
                    ++$this->p;
                }
            } else {
                ++$this->p;
            }
        }
    }

    protected function tryParseKeyword(array $keywords, $as = 'keyword')
    {
        $this->skipSpaces();
        $this->sortKeywordsByLen($keywords);

        foreach ($keywords as $keyword) {
            $p2 = stripos($this->str, $keyword, $this->p);
            if ($p2 === $this->p) {
                $this->p += strlen($keyword);

                return new Token($as, $keyword);
            }
        }

        return;
    }

    protected function tryParseIndexColumns()
    {
        $c = $this->cur();
        if ($c == '(') {
            $this->advance();
            $this->skipSpaces();
            $indexColumns = [];
            while ($columnName = $this->tryParseIdentifier()) {
                $indexColumn = new stdClass();
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

        return;
    }

    protected function tryParseTypePrecision()
    {
        $c = $this->str[ $this->p ];
        if ($c == '(') {
            if (preg_match('/\( \s* (\d+) \s* , \s* (\d+) \s* \)/x', $this->str, $matches, 0, $this->p)) {
                $this->p += strlen($matches[0]);

                return new Token('precision', [intval($matches[1]), intval($matches[2])]);
            } elseif (preg_match('/\(  \s* (\d+) \s* \)/x', $this->str, $matches, 0, $this->p)) {
                $this->p += strlen($matches[0]);

                return new Token('precision', [intval($matches[1])]);
            } else {
                throw new Exception('Invalid precision syntax');
            }
        }

        return;
    }

    protected function sortKeywordsByLen(array &$keywords)
    {
        usort($keywords, function ($a, $b) {
            $al = strlen($a);
            $bl = strlen($b);
            if ($al == $bl) {
                return 0;
            } elseif ($al > $bl) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    public static $intTypes = ['INT', 'INTEGER', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'BIG INT', 'INT2', 'INT8'];
    public static $textTypes = ['CHARACTER', 'VARCHAR', 'VARYING CHARACTER', 'NCHAR', 'NATIVE CHARACTER', 'NVARCHAR', 'TEXT', 'CLOB'];
    public static $numericTypes = ['NUMERIC', 'DECIMAL', 'BOOLEAN', 'DATE', 'DATETIME', 'TIMESTAMP'];

    protected function consume($token, $typeName)
    {
        if (($p2 = stripos($this->str, $token, $this->p)) !== false && $p2 == $this->p) {
            $this->p += strlen($token);

            return new Token($typeName, $token);
        }

        return false;
    }

    protected function tryParseTypeName()
    {
        $blobTypes = ['BLOB', 'NONE'];
        $realTypes = ['REAL', 'DOUBLE', 'DOUBLE PRECISION', 'FLOAT'];
        $allTypes = array_merge(self::$intTypes,
            self::$textTypes,
            $blobTypes,
            $realTypes,
            self::$numericTypes);
        $this->sortKeywordsByLen($allTypes);
        foreach ($allTypes as $typeName) {
            // Matched
            if (($p2 = stripos($this->str, $typeName, $this->p)) !== false && $p2 == $this->p) {
                $this->p += strlen($typeName);

                return new Token('type-name', $typeName);
            }
        }

        return;
        // throw new Exception('Expecting type-name');
    }

    protected function tryParseIdentifier()
    {
        $this->skipSpaces();
        if ($this->str[$this->p] == '`') {
            ++$this->p;
            // find the quote pair position
            $p2 = strpos($this->str, '`', $this->p);
            if ($p2 === false) {
                throw new Exception('Expecting identifier quote (`): '.$this->currentWindow());
            }
            $token = substr($this->str, $this->p, $p2 - $this->p);
            $this->p = $p2 + 1;

            return new Token('identifier', $token);
        }
        if (preg_match('/^(\w+)/', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);

            return new Token('identifier', $matches[1]);
        }

        return;
    }

    protected function tryParseScalar()
    {
        $this->skipSpaces();

        if ($this->cur() == "'") {
            $this->advance();

            $p = $this->p;

            while (!$this->metEnd()) {
                $this->advance();
                if ($this->cur() == "'") {
                    $pos = $this->p;
                    $this->advance();
                    if ($this->cur() != "'") {
                        $this->rollback($pos);
                        break;
                    }
                }
            }
            $string = str_replace("''", "'", substr($this->str, $p, ($this->p - 1) - $p));

            return new Token('string', $string);
        } elseif (preg_match('/-?\d+  \. \d+/x', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);

            return new Token('double', doubleval($matches[0]));
        } elseif (preg_match('/-?\d+/x', substr($this->str, $this->p), $matches)) {
            $this->p += strlen($matches[0]);

            return new Token('int', intval($matches[0]));
        }
    }
}
