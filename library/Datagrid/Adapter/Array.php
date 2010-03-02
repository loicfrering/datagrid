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

    public function hasColumn($columnName)
    {
        $record = reset($this->_data);
        return array_key_exists($columnName, $record);
    }

    public function hasRelation($relationName)
    {
        $record = reset($this->_data);
        return array_key_exists($relationName, $record) && is_array($record[$relationName]);
    }

    public function prepare()
    {
        $preparedData = $this->_data;
    }

    public function selectColumn($column, array $relations = array())
    {

    }

    public function filter($column, $filter, $matchMode, array $relations = array())
    {
        $this->_currentFilteredColumn = $column;
        $this->_currentFilter = $filter;
        $this->_currentFilterMatchMode = $matchMode;
        $this->_data = array_filter($this->_data, array($this, '_filter'));
    }

    /**
     *
     * @param Datagrid_column $column
     * @param string $order
     * @param array $relations
     */
    public function sort($column, $order, array $relations = array())
    {
        $data = $this->_data;
        $columns = array();
        foreach ($data as $key => $row) {
            $columns[$key]  = $row[$column->getName()];
        }

        array_multisort($columns, $order == Datagrid::DESC_ORDER ? SORT_DESC : SORT_ASC, $data);

        $this->_data = $data;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        return array_slice($this->_data, $offset, $itemCountPerPage);
    }

    public function count()
    {
        return count($this->_data);
    }

    protected function _filter($item)
    {
        switch($this->_currentFilterMatchMode) {
            case Datagrid_Filter::MATCH_BEGINS:
                return strncasecmp($item[$this->_currentFilteredColumn], $this->_currentFilter, strlen($this->_currentFilter)) == 0;
                break;

            case Datagrid_Filter::MATCH_CONTAINS:
                return stripos($item[$this->_currentFilteredColumn], $this->_currentFilter) !== false;
                break;

            default:
                return true;
        }
    }
}
