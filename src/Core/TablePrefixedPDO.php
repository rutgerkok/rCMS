<?php

namespace Rcms\Core;

use BadMethodCallException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Variant of PDO that supports table prefixes for tables with certain names.
 */
final class TablePrefixedPDO extends PDO {

    private $prefix;
    // Replacing table names in queries
    private $tableNamesToReplace;
    private $replacingTableNames;

    /**
     * Creates a new instance.
     * @param string $dsn Data source name, see PDO documentation.
     * @param string $user Username used for the connection.
     * @param string $pass Password used for the connection.
     * @param string $options The table prefix option, as well as driver-
     * specific options. At least the option table_prefix must be present, with
     * as a value the prefix to use. The remaining options are then passed to
     * the database driver.
     * @throws BadMethodCallException If the table_prefix option is not present
     * in the options array.
     */
    public function __construct($dsn, $user, $pass, $options) {

        // Read table prefix
        if (!isSet($options["table_prefix"])) {
            throw new BadMethodCallException("\$options[table_prefix] not present");
        }
        $this->prefix = $options["table_prefix"];
        unset($options["table_prefix"]);

        // Connect
        parent::__construct($dsn, $user, $pass, $options);
    }

    /**
     * Adds the table with the given name to the list of tables that must be
     * prefixed.
     * @param string $tableName The table name.
     */
    public function prefixTable($tableName) {
        $this->tableNamesToReplace[] = "`{$tableName}`";
        $this->replacingTableNames[] = "`{$this->prefix}{$tableName}`";
    }

    /**
     * Adds all tables with the given names to the list of tables that must be
     * prefixed.
     * @param string[] $tableNames The table names.
     */
    public function prefixTables(array $tableNames) {
        foreach ($tableNames as $tableName) {
            $this->prefixTable($tableName);
        }
    }

    /**
     * Executes a SQL query. Use this for UPDATE, DELETE and INSERT queries.
     * Automatically adds prefixes to the table names, as long as they are
     * placed in backticks.
     * @param string $sql The SQL to execute
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return int The number of affected rows.
     */
    public function exec($sql) {
        $replacedSql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::exec($replacedSql);
    }

    /**
     * Executes a SQL query and returns the result. Use this for SELECT
     * queries. Automatically adds prefixes to the table names, as long as they
     * are placed in backticks.
     * @param string $sql The SQL to execute.
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return PDOStatement The results.
     */
    public function query($sql) {
        $replacedSql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::query($replacedSql);
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * @link http://php.net/manual/en/pdo.prepare.php
     * @param string $sql This must be a valid SQL statement for the target
     * database server.
     * @param array $driverOptions [optional] This array holds one or more 
     * key=&gt;value pairs to set attribute values for the PDOStatement object
     * that this method returns. You would most commonly use this to set the
     * PDO::ATTR_CURSOR value to PDO::CURSOR_SCROLL to request a scrollable
     * cursor. Some drivers have driver specific options that may be set at
     * prepare-time.
     * @return PDOStatement If the database server successfully prepares the statement,
     * <b>PDO::prepare</b> returns a
     * <b>PDOStatement</b> object.
     * If the database server cannot successfully prepare the statement,
     * <b>PDO::prepare</b> returns <b>FALSE</b> or emits
     * <b>PDOException</b> (depending on error handling).
     * </p>
     * <p>
     * Emulated prepared statements does not communicate with the database server
     * so <b>PDO::prepare</b> does not check the statement.
     */
    public function prepare($sql, $driverOptions = []) {
        $replacedSql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::prepare($replacedSql, $driverOptions);
    }

}
