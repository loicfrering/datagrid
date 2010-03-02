<?php
/**
 * Description of Datagrid_Adapter_Interface
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
class Datagrid_Adapter_Doctrine implements Datagrid_Adapter_Interface
{
    /**
     * Datagrid Doctrine table
     * @var Doctrine_Table
     */
    protected $_table = null;
    protected $_query;

    /**
     * Doctrine table alias used id DQL
     * This can help to construct where clauses for relations
     * @var string
     */
    protected $_tableAlias = null;

    public function  __construct($doctrineTable)
    {
        if ($doctrineTable instanceof Doctrine_Table) {
            $this->_table = $doctrineTable;
        }
        else {
            // getTable throws 'Doctrine_Exception' if table name does not exist
            $this->_table = Doctrine::getTable($doctrineTable);
        }

        $tableName = $this->_table->getTableName();
        $this->_tableAlias = strtolower($tableName[0]);
    }

    /**
     * Return the table alias name used in Doctrine queries.
     * This can help to construct where clauses for relations.
     *
     * @return string
     */
    public function getTableAlias()
    {
        return $this->_tableAlias;
    }

    public function prepare(array $columns)
    {
        $this->_query = $this->_table->createQuery($this->getTableAlias());
        //$this->_query->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        /*foreach($relations as $relation) {
            $alias = $relation->getAlias();
            //$where = $relation->getWhere();
            if($relation->hasRelation()) {
                $parentAlias = $relation->getRelation()->getAlias();
            }
            else {
                $parentAlias = $this->getTableAlias();
            }
            $this->_query->leftJoin("$parentAlias.{$relation->getName()} $alias");
        }*/
    }

    /**
     *
     * @param Datagrid_Column $column
     * @param string $order
     * @param array $relations
     */
    public function sort($field, $order)
    {
        $order = ($order == Datagrid::DESC_ORDER) ? 'desc' : 'asc';
        $this->_query->addOrderBy("{$this->getTableAlias()}.$field $order");
    }

    public function filter($field, $filter, $matchMode)
    {
        echo ("{$this->getTableAlias()}.$field");

        switch($matchMode) {
            case Datagrid_Filter::MATCH_CONTAINS:
            $where = "{$this->getTableAlias()}.$field LIKE '%$filter%'";
            break;

            case Datagrid_Filter::MATCH_BEGINS:
            default:
            $where = "{$this->getTableAlias()}.$field LIKE '$filter%'";
        }
        $this->_query->addWhere($where);
    }

    public function getItems($offset, $itemCountPerPage)
    {
        $pager = new Doctrine_Pager($this->_query, $offset/$itemCountPerPage+1, $itemCountPerPage);

        return $pager->execute();
    }

    public function get($item, $field)
    {
        try {
            if(false !== strpos($field, '.')) {
                return $this->_getRelated($item, $field);
            }
            else {
                return $item[$field];
            }
        }
        catch(Doctrine_Exception $e) {
            return null;
        }
    }

    protected function _getRelated($item, $field)
    {
        $relations = explode('.', $field);

        $field = array_pop($relations);
        $relatedRecords = $item;
        
        foreach($relations as $relation) {
            $relatedRecords = $relatedRecords[$relation];
        }

        $out = array();
        foreach($relatedRecords as $relatedRecord) {
            $out[] = $relatedRecord[$field];
        }
        return $out;
    }

    public function count()
    {
        return $this->_query->count();
    }

}
