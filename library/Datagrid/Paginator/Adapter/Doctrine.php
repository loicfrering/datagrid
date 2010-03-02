<?php
/**
 * Description of Doctrine
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
class Datagrid_Paginator_Adapter_Doctrine implements Zend_Paginator_Adapter_Interface
{
    /**
     * Doctrine query for records to paginate
     * 
     * @var Doctrine_Query
     */
    protected $_query = null;
    
    /**
     * Record count
     *
     * @var integer
     */
    protected $_count = null;
    
    /**
     * Constructor
     * 
     * @param Doctrine_Query $query Doctrine query for records to paginate
     */
    public function __construct($query)
    {
        $this->_query = $query;
        $this->_query->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_count = $query->count();
    }

    /**
     * Returns an array of records for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $pager = new Doctrine_Pager($this->_query, $offset/$itemCountPerPage+1, $itemCountPerPage);

        return $pager->execute();
    }

    /**
     * Returns the total number of records
     *
     * @return integer
     */
    public function count()
    {
        return $this->_count;
    }
}
