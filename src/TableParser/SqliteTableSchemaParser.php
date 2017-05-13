<?php

namespace Maghead\TableParser;

use Exception;
use stdClass;

class TableDef {

    public $columns = [];

    public $temporary;

    public $ifNotExists = false;

    public $tableName;

    public $constraints;

}

class Constraint {

    public $name;

    public $primaryKey;

    public $unique;

    public $foreignKey;

}

class Column {

    public $name;

    public $type;

    public $length;

    public $decimals;

    public $unsigned;

    public $primary;

    public $ordering;

    public $autoIncrement;

    public $unique;

    public $notNull;

    public $default;

    public $collate;

    public $references;

}


class BaseTableSchemaParser
{
    /**
     *  @var int
     *
     *  The default buffer offset
     */
    protected $p = 0;

    /**
     * @var string
     *
     * The buffer string for parsing.
     */
    protected $str = '';

    protected function consume($token, $typeName)
    {
        if (($p2 = stripos($this->str, $token, $this->p)) !== false && $p2 == $this->p) {
            $this->p += strlen($token);

            return new Token($typeName, $token);
        }

        return false;
    }

    protected function tryParseKeyword(array $keywords, $as = 'keyword')
    {
        $this->ignoreSpaces();
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

    protected function cur()
    {
        return $this->str[ $this->p ];
    }

    protected function expect($c)
    {
        if ($c === $this->str[$this->p]) {
            ++$this->p;
            return true;
        }
        throw new Exception("Expect '$c': ".$this->currentWindow());
    }

    protected function advance($c = null)
    {
        if (!$this->metEnd()) {
            if ($c) {
                if ($c === $this->str[$this->p]) {
                    ++$this->p;
                    return true;
                }
            } else {
                ++$this->p;
            }
        }
    }

    /**
     * return the current buffer window
     */
    protected function currentWindow($window = 32)
    {
        return var_export(substr($this->str, $this->p, $window) . '...', true)." FROM '{$this->str}'\n";
    }

    protected function metEnd()
    {
        return $this->p + 1 >= $this->strlen;
    }

    protected function ignoreSpaces()
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

}


/**
 * SQLite Parser for parsing table column definitions:.
 *
 *  CREATE TABLE {identifier} ( columndef, columndef, ... );
 *
 *
 * The syntax follows the official documentation below:
 *
 *   http://www.sqlite.org/lang_createtable.html
 *
 * Unsupported:
 *
 * - Create table .. AS SELECT ...
 * - Foreign key clause actions
 */
class SqliteTableSchemaParser extends BaseTableSchemaParser
{


    public function parse($input, $offset = 0)
    {
        $this->str    = $input;
        $this->strlen = strlen($input);
        $this->p = $offset;

        $tableDef = new TableDef;

        $this->ignoreSpaces();

        $keyword = $this->tryParseKeyword(['CREATE']);

        $this->ignoreSpaces();

        if ($this->tryParseKeyword(['TEMPORARY', 'TEMP'])) {
            $tableDef->temporary = true;
        }

        $this->ignoreSpaces();

        $this->tryParseKeyword(['TABLE']);

        $this->ignoreSpaces();

        if ($this->tryParseKeyword(['IF'])) {
            if (!$this->tryParseKeyword(['NOT'])) {
                throw new Exception('Unexpected token');
            }
            if (!$this->tryParseKeyword(['EXISTS'])) {
                throw new Exception('Unexpected token');
            }
            $tableDef->ifNotExists = true;
        }

        $tableName = $this->tryParseIdentifier();

        $tableDef->tableName = $tableName->val;

        $this->ignoreSpaces();
        $this->expect('(');
        $this->parseColumns($tableDef);
        $this->expect(')');

        return $tableDef;
    }

    protected function parseColumns(TableDef $tableDef)
    {
        // Parse columns
        while (!$this->metEnd()) {
            $this->ignoreSpaces();

            if ($this->looksLikeTableConstraint()) {
                break;
            }

            $identifier = $this->tryParseIdentifier();
            if (!$identifier) {
                break;
            }

            $column = new Column;
            $column->name = $identifier->val;

            $this->ignoreSpaces();
            $typeName = $this->tryParseTypeName();
            if ($typeName) {
                $this->ignoreSpaces();
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
                    $column->unsigned = $this->consume('unsigned', 'unsigned');
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

                    } else if ($constraintToken->val == 'COLLATE') {
                        $collateName = $this->tryParseIdentifier();
                        $column->collate = $collateName->val;

                    } else if ($constraintToken->val == 'REFERENCES') {

                        $tableNameToken = $this->tryParseIdentifier();

                        $this->expect('(');
                        $columnNames = $this->parseColumnNames();
                        $this->expect(')');

                        $actions = [];
                        if ($this->tryParseKeyword(['ON'])) {
                            while ($onToken = $this->tryParseKeyword(['DELETE', 'UPDATE'])) {
                                $on = $onToken->val;
                                $actionToken = $this->tryParseKeyword(['SET NULL', 'SET DEFAULT', 'CASCADE', 'RESTRICT', 'NO ACTION']);
                                $actions[$on] = $actionToken->val;
                            }
                        }

                        $column->references = (object) [
                            'table' => $tableNameToken->val,
                            'columns' => $columnNames,
                            'actions' => $actions,
                        ];
                    }
                    $this->ignoreSpaces();
                }
            }

            $tableDef->columns[] = $column;
            $this->ignoreSpaces();
            if ($this->metComma()) {
                $this->skipComma();
                $this->ignoreSpaces();
            }
        } // end of column parsing

        if ($tableConstraints = $this->tryParseTableConstraints()) {
            $tableDef->constraints = $tableConstraints;
        }
        $this->ignoreSpaces();
        return $tableDef;
    }
    
    protected function looksLikeTableConstraint()
    {
        return $this->test(['CONSTRAINT', 'PRIMARY', 'UNIQUE', 'FOREIGN', 'CHECK']);
    }

    protected function parseColumnNames()
    {
        $columnNames = [];
        while ($identifier = $this->tryParseIdentifier()) {
            $columnNames[] = $identifier->val;
            $this->ignoreSpaces();
            if ($this->metComma()) {
                $this->skipComma();
                $this->ignoreSpaces();
            } else {
                break;
            }
        }

        return $columnNames;
    }

    protected function tryParseTableConstraints()
    {
        $tableConstraints = null;

        while (!$this->metEnd()) {
            $this->ignoreSpaces();
            $tableConstraint = new Constraint;

            if ($this->tryParseKeyword(['CONSTRAINT'])) {
                $this->ignoreSpaces();
                $constraintName = $this->tryParseIdentifier();
                if (!$constraintName) {
                    throw new Exception('Expect constraint name');
                }
                $tableConstraint->name = $constraintName->val;
            }

            $this->ignoreSpaces();
            $tableConstraintKeyword = $this->tryParseKeyword(['PRIMARY', 'UNIQUE', 'CHECK', 'FOREIGN']);

            if (!$tableConstraintKeyword) {
                if (isset($tableConstraint->name)) {
                    throw new Exception('Expect constraint type');
                }
                break;
            }

            if (in_array($tableConstraintKeyword->val, ['PRIMARY', 'FOREIGN'])) {
                $this->ignoreSpaces();
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
            } else if ($tableConstraintKeyword->val == 'FOREIGN') {
                $this->expect('(');
                $tableConstraint->foreignKey = $this->parseColumnNames();
                $this->expect(')');
            }
            $tableConstraints[] = $tableConstraint;
        }

        return $tableConstraints;
    }

    protected function tryParseColumnConstraint()
    {
        return $this->tryParseKeyword(['PRIMARY', 'UNIQUE', 'NOT NULL', 'NULL', 'DEFAULT', 'COLLATE', 'REFERENCES'], 'constraint');
    }


    protected function tryParseIndexColumns()
    {
        $this->expect('(');
        $this->ignoreSpaces();
        $indexColumns = [];
        while ($columnName = $this->tryParseIdentifier()) {
            $indexColumn = new stdClass();
            $indexColumn->name = $columnName->val;

            if ($this->tryParseKeyword(['COLLATE'])) {
                $this->ignoreSpaces();
                if ($collationName = $this->tryParseIdentifier()) {
                    $indexColumn->collationName = $collationName->val;
                }
            }

            if ($ordering = $this->tryParseKeyword(['ASC', 'DESC'])) {
                $indexColumn->ordering = $ordering->val;
            }

            $this->ignoreSpaces();
            if ($this->metComma()) {
                $this->skipComma();
            }
            $indexColumns[] = $indexColumn;
            $this->ignoreSpaces();
        }
        $this->expect(')');
        return $indexColumns;
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
    public static $textTypes = ['CHARACTER', 'VARCHAR', 'VARYING CHARACTER', 'NCHAR', 'NATIVE CHARACTER', 'NVARCHAR', 'TEXT', 'BLOB', 'BINARY'];
    public static $numericTypes = ['NUMERIC', 'DECIMAL', 'BOOLEAN', 'DATE', 'DATETIME', 'TIMESTAMP'];


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
        $this->ignoreSpaces();
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
        $this->ignoreSpaces();

        if ($this->advance("'")) {

            $p = $this->p;

            while (!$this->metEnd()) {
                if ($this->advance("'")) {
                    break;
                }
                $this->advance("\\"); // skip
                $this->advance();
            }

            return new Token('string', substr($this->str, $p, ($this->p - 1) - $p));

        } else if (preg_match('/-?\d+(\.\d+)?/x', substr($this->str, $this->p), $matches)) {

            $this->p += strlen($matches[0]);

            if (isset($matches[1])) {
                return new Token('double', doubleval($matches[0]));
            }
            return new Token('int', intval($matches[0]));

        }
    }


}
