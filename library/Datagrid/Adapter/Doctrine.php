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

    public function hasColumn($columnName)
    {
        return $this->_table->hasColumn($columnName);
    }

    public function hasRelation($relationName)
    {
        return $this->_table->hasRelation($relationName);
    }

    public function prepare()
    {
        $this->_query = $this->_table->createQuery($this->_tableAlias);
        $this->_query->setHydrationMode(Doctrine::HYDRATE_ARRAY);
    }

    public function selectColumn($column, array $relations = array())
    {

    }

    public function sort($column, $order, array $relations = array())
    {
        if(!empty($this->_currentSortedColumn)) {
            $currentSortedColumn = $columns[$this->_currentSortedColumn];
            if($currentSortedColumn->hasRelation()) {
                $relation = $currentSortedColumn->getRelation();
                $relation = $relation['name'];
                $query->addOrderBy($columns[$this->_currentSortedColumn]->getOrderByClause($relations[$relation]->getAlias(), $this->_currentSort));
            }
            else {
                $query->addOrderBy($columns[$this->_currentSortedColumn]->getOrderByClause($this->_tableAlias, $this->_currentSort));
            }
        }
    }

    public function filter($column, $filter, $matchMode, array $relations = array())
    {
        if(!empty($filter) && $filter != Datagrid_Filter::SELECT_ALL) {
            $where = $this->_getWhereClause($column, $filter, $matchMode, $relations);
            if(!empty($where)) {
                echo 'WHERE : '.$where;
                $this->_query->addWhere($where);
            }
        }
    }

    protected function _getQuery(array $columns, array $relations, array $filters)
    {
        $this->_loadParams();

        $tableAlias = $this->_tableAlias;

        $query = $this->_table->createQuery($tableAlias);

        if(!empty($this->_where)) {
            $query->addWhere($this->_where);
        }

        foreach($relations as $key => $relation) {
            $alias = $relation->getAlias();
            $where = $relation->getWhere();
            if($relation->hasRelation()) {
                $parentRelation = $relations[$relation->getRelation()];
                $query->leftJoin("{$parentRelation->getAlias()}.{$relation->getName()} $alias");
            }
            else {
                $query->leftJoin("$tableAlias.{$relation->getName()} $alias");
            }
            if(!empty($where)){
                $query->addWhere($where);
            }
        }

        foreach($filters as $filter) {
            if($filter->hasRelation()) {
                $where = $filter->getWhereClause($this->_tableAlias, $this->_params, $relations[$filter->getRelation()]);
            }
            else {
                $where = $filter->getWhereClause($this->_tableAlias, $this->_params);
            }

            if(!empty($where)) {
                $query->addWhere($where);
            }
        }

        /*foreach($this->_orderby as $orderby) {
            $query->addOrderBy("$tableAlias.$orderby");
        }*/

        if(!empty($this->_currentSortedColumn)) {
            $currentSortedColumn = $columns[$this->_currentSortedColumn];
            if($currentSortedColumn->hasRelation()) {
                $relation = $currentSortedColumn->getRelation();
                $relation = $relation['name'];
                $query->addOrderBy($columns[$this->_currentSortedColumn]->getOrderByClause($relations[$relation]->getAlias(), $this->_currentSort));
            }
            else {
                $query->addOrderBy($columns[$this->_currentSortedColumn]->getOrderByClause($this->_tableAlias, $this->_currentSort));
            }
        }

        $query->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        //echo $query->getSqlQuery();

        return $query;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        $pager = new Doctrine_Pager($this->_query, $offset/$itemCountPerPage+1, $itemCountPerPage);

        return $pager->execute();
    }

    public function count()
    {
        return $this->_query->count();
    }

    protected function _getWhereClause($column, $filter, $matchMode, $relation = null)
    {
        if(!empty($relation)) {
            $columnLabel = "{$relation->getAlias()}.{$this->_column}";
        }
        else {
            $columnLabel = "$this->_tableAlias.{$column}";
        }

        switch($matchMode) {
            case Datagrid_Filter::MATCH_BEGINS:
            return "$columnLabel LIKE '$filter%'";

            case Datagrid_Filter::MATCH_CONTAINS:
            return "$columnLabel LIKE '%$filter%'";

            default:
            return null;
        }
    }
}
