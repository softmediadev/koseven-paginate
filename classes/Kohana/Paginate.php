<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Paginate
 * 
 * @package		Paginate
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
abstract class Kohana_Paginate
{
	/**
	 * Sort Ascending
	 * 
	 * @var		string
	 */
	const SORT_ASC = 'ASC';
	 
	/**
	 * Sort Descending
	 * 
	 * @var		string
	 */
	const SORT_DESC = 'DESC';
	
	/**
	 * Factory pattern
	 * 
	 * @static
	 * @access	public
	 * @param	mixed	string|object
	 * @param	mixed	NULL|string
	 * @return	Paginate
	 * @throws	Kohana_Exception
	 */
	public static function factory($object, $driver = NULL)
	{
		if ($driver === NULL)
		{
			if ($object instanceof ORM)
			{
				$driver = 'ORM';
			}
			else if ($object instanceof Database_Query)
			{
				$driver = 'Database';
			}
		}
		
		if ( ! $driver)
			throw new Kohana_Exception('Paginate either expecting driver name or a driver 
				supported object instance.');
		
		$class = 'Paginate_' . $driver;

		return new $class($object); 
	}
	
	/**
	 * Object to perform paginate operations on
	 * 
	 * @access	protected
	 * @var		object
	 */
	protected $_object;
	
	/**
	 * Columns
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_columns = array();
	
	/**
	 * Search columns
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_search_columns = array();
	
	/**
	 * Search query
	 * 
	 * @access	protected
	 * @var		mixed 	NULL|string
	 */
	protected $_search_query;

	/**
	 * Count for request
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_count = 0;
	
	/**
	 * Total count
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_count_total = 0;
	
	/**
	 * Total search count
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_count_search_total = 0;

	/**
	 * Total items per page
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_items_per_page = 0;

	/**
	 * Total pages
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_count_pages = 0;

    /**
     * Current page
     *
     * @access    protected
     * @var       int
     */
    protected $_page = 1;

	/**
	 * Result
	 * 
	 * @access	protected
	 * @var		NULL
	 */
	protected $_result;

	/**
	 * Database name
	 *
	 * @access    protected
	 * @var        NULL
	 */
	protected $_db = NULL;
	
	/**
	 * Initialize
	 * 
	 * @access	public
	 * @param	object
	 * @return	void
	 */	
	public function __construct($object)
	{
		$this->_object = $object;
	}

	/**
	 * Apply limit
	 * 
	 * @access	protected
	 * @param	int
	 * @return	void
	 */
	abstract protected function _limit($start, $length);
	
	/**
	 * Apply sort
	 * 
	 * @access	protected
	 * @param	string
	 * @return	void
	 */
	abstract protected function _sort($column, $direction);

	/**
	 * Apply group
	 *
	 * @access    protected
	 *
	 * @param string
	 *
	 * @return    void
	 */
	abstract protected function _group($column);
	
	/**
	 * Apply search query
	 * 
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	abstract protected function _search($query);
	
	/**
	 * Count
	 * 
	 * @access	protected
	 * @return	int
	 */
	abstract protected function _count();
	
	/**
	 * Count total
	 * 
	 * @access	protected
	 * @return	int
	 */
	abstract protected function _count_total();
	
	/**
	 * Count search total
	 * 
	 * @access	protected
	 * @return	int
	 */
	abstract protected function _count_search_total();

	/**
	 * Execute result on object
	 * 
	 * @access	protected
	 * @return	mixed
	 */
	abstract protected function _execute();
	
	/**
	 * Set limit
	 * 
	 * @access	public
	 * @param	int
	 * @param	int
	 * @return	$this
	 */
	public function limit($start, $length)
	{
		$this->_limit($start, $length);
		
		return $this;
	}

	/**
	 * Set page number
	 *
	 * @access	public
	 * @param	integer		Page number
	 * @param	integer		Total items per page
	 * @return	$this
	 */
	public function page($page, $itemsperpage)
	{
		if ( ! is_numeric($itemsperpage))
			$itemsperpage = 0;

		$this->_items_per_page = $itemsperpage;

		if ( ! is_numeric($page))
			$page = 1;

        if ($page < 1)
            $page = 1;

        $this->_page = $page;

		$offset = ($page - 1) * $itemsperpage;

		$this->_limit($offset, $itemsperpage);

		return $this;
	}
	
	/**
	 * Set sort order
	 * 
	 * @access	public
	 * @param	string
	 * @param	mixed	SORT_ASC|SORT_DESC
	 * @return	$this
	 * @throws	Kohana_Exception
	 */
	public function sort($column, $direction = self::SORT_ASC)
	{
		if ( ! in_array($direction, array(self::SORT_ASC, self::SORT_DESC)))
			throw new Kohana_Exception('Invalid sort order of `' . $direction . '`.');
		
		$this->_sort($column, $direction);
		
		return $this;
	}

	/**
	 * Group
	 *
	 * @access    public
	 *
	 * @param string
	 *
	 * @return    $this
	 */
	public function group($column)
	{
		$this->_group($column);

		return $this;
	}
	
	/**
	 * Get or set search query
	 * 
	 * @access	public
	 * @param	mixed 	NULL|string
	 * @return	mixed 	$this|string
	 */
	public function search($query = NULL)
	{
		if ($query === NULL)
			return $this->_search_query;

		$this->_search_query = $query;

		return $this;
	}
	
	/**
	 * Get count based on post operations
	 * 
	 * @access	public
	 * @return	int
	 */
	public function count()
	{
		return (int) $this->_count;
	}
	
	/**
	 * Get total count prior to operations
	 * 
	 * @access	public
	 * @return	int
	 */
	public function count_total()
	{
		return (int) $this->_count_total;
	}
	
	/**
	 * Get search count prior to pagination
	 * 
	 * @access	public
	 * @return	int
	 */
	public function count_search_total()
	{
		return (int) $this->_count_search_total;
	}

	/**
	 * Get total pages
	 *
	 * @access	public
	 * @return	int
	 */
	public function count_pages()
	{
		return (int) $this->_count_pages;
	}

	/**
	 * Get current page number
	 *
	 * @access   public
	 * @return   int
	 */
	public function get_current_page()
	{
		return $this->_page;
	}

    /**
     * Get previous page number
     *
     * @access   public
     * @return   int
     */
    public function get_previous_page()
    {
        $val = $this->_page - 1;
        $pages = $this->count_pages();

        if ($val < 1)
            $val = 1;
        elseif ($val >= $pages)
            $val = $pages - 1;

        return $val;
    }

    /**
     * Get next page number
     *
     * @access   public
     * @return   int
     */
    public function get_next_page()
    {
        $val = $this->_page + 1;
        $pages = $this->count_pages();

        if ($val > $this->count_pages())
            $val = $pages;

        return $val;
    }

	/**
	 * Set or get columns
	 * 
	 * @access	public
	 * @param	mixed	NULL|string
	 * @return	mixed	$this|string
	 */
	public function columns(array $columns = NULL)
	{
		if ($columns === NULL)
			return $this->_columns;
		
		$this->_columns = $columns;
		
		return $this;
	}
	
	/**
	 * Set or get search columns
	 * 
	 * @access	public
	 * @param	mixed	NULL|string
	 * @return	mixed	$this|string
	 */
	public function search_columns(array $columns = NULL)
	{
		if ($columns === NULL)
			return $this->_search_columns;
		
		$this->_search_columns = $columns;
		
		return $this;
	}
	
	/**
	 * Get result
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function result()
	{
		return $this->_result;
	}
	
	/**
	 * Execute
	 * 
	 * @access	public
	 * @param	mixed	NULL|Request
	 * @return	$this
	 */
	public function execute($db = NULL)
	{
		$this->_db = $db;

		if ($this->_search_query !== NULL)
			$this->_search($this->_search_query);

		$this->_result = $this->_execute();
		
		$this->_count = $this->_count();
		
		$this->_count_total = $this->_count_total();

		if ($this->_items_per_page > 0)
			$this->_count_pages = ceil($this->_count_total / $this->_items_per_page);
		else
			$this->_count_pages = 0;

		if ($this->_search_query !== NULL)
		{
			$this->_count_search_total = $this->_count_search_total();
		}

		return $this;
	}
}
