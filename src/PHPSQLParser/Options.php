<?php
/**
 * @author     mfris
 *
 */

namespace PHPSQLParser;

use PHPSQLParser\processors\AbstractProcessor;

/**
 *
 * @author  mfris
 * @package PHPSQLParser
 *
 * @template T
 */
class Options
{

    /**
     * @var array
     */
    private $options;

    /**
     * @const string
     */
    const CONSISTENT_SUB_TREES = 'consistent_sub_trees';

    /**
     * @const string
     */
    const ANSI_QUOTES = 'ansi_quotes';

    protected $instances = array();
    /**
     * @var bool
     */
    protected $mboverloaded = false;

    /**
     * Options constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
        $this->mboverloaded = (bool)@ini_get('mbstring.func_overload');
    }

    /**
     * @return bool
     */
    public function getConsistentSubtrees()
    {
        return (isset($this->options[self::CONSISTENT_SUB_TREES]) && $this->options[self::CONSISTENT_SUB_TREES]);
    }

    /**
     * @return bool
     */
    public function getANSIQuotes()
    {
        return (isset($this->options[self::ANSI_QUOTES]) && $this->options[self::ANSI_QUOTES]);
    }

    /**
     * @param class-string<T>|string $classString
     * @return T|AbstractProcessor
     */
    public function getProcessor($classString)
    {
        if (!isset($this->instances[$classString])) {
            $this->instances[$classString] = new $classString($this);
        }

        return $this->instances[$classString];
    }

    /**
     * @return bool
     */
    public function isMBOverloaded()
    {
        return $this->mboverloaded;
    }
}
