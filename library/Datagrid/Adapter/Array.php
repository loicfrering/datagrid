<?php
/**
 * Description of Datagrid_Adapter_Array
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
class Datagrid_Adapter_Array implements Datagrid_Adapter_Interface
{
    protected $_data;
    protected $_currentFilteredColumn;
    protected $_currentFilter;
    protected $_currentFilterMatchMode;

    public function  __construct($data)
    {
        $this->_data = $data;
    }

    public function prepare(array $columns)
    {
        
    }

    public function filter($field, $filter, $matchMode)
    {
        if(!empty($filter)) {
            $this->_data = array_filter($this->_data, function($item) use ($field, $filter, $matchMode) {
                switch($matchMode) {
                    case Datagrid_Filter::MATCH_BEGINS:
                        //Zend_Debug::dump(stripos($item[$field], $filter) === 0);
                        return stripos($item[$field], $filter) === 0;

                    case Datagrid_Filter::MATCH_CONTAINS:
                        return stripos($item[$field], $filter) !== false;

                    default:
                        return true;
                }
            });
        }
    }

    public function sort($field, $order)
    {
        $data = $this->_data;
        $columns = array();
        foreach ($data as $key => $row) {
            $columns[$key]  = $row[$field];
        }

        array_multisort($columns, $order == Datagrid::DESC_ORDER ? SORT_DESC : SORT_ASC, $data);

        $this->_data = $data;
    }

    public function get($item, $field)
    {
        return $item[$field];
    }

    public function getItems($offset, $itemCountPerPage)
    {
        return array_slice($this->_data, $offset, $itemCountPerPage);
    }

    public function count()
    {
        return count($this->_data);
    }
}
