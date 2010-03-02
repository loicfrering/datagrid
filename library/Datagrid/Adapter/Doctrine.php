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

    public function prepare(array $columns, array $relations)
    {
        $this->_query = $this->_table->createQuery($this->getTableAlias());
        $this->_query->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        foreach($relations as $relation) {
            $alias = $relation->getAlias();
            //$where = $relation->getWhere();
            if($relation->hasRelation()) {
                $parentAlias = $relation->getRelation()->getAlias();
            }
            else {
                $parentAlias = $this->getTableAlias();
            }
            $this->_query->leftJoin("$parentAlias.{$relation->getName()} $alias");
            /*if(!empty($where)){
                $query->addWhere($where);
            }*/
        }
    }

    /**
     *
     * @param Datagrid_Column $column
     * @param string $order
     * @param array $relations
     */
    public function sort($column, $order)
    {
        $order = ($order == Datagrid::DESC_ORDER) ? 'desc' : 'asc';
        if($column->hasRelation()) {
            $relation = $column->getRelation();
            $this->_query->addOrderBy("{$relation->getAlias()}.{$column->getName()} $order");
        }
        else {
            $this->_query->addOrderBy("{$this->getTableAlias()}.{$column->getName()} $order");
        }
    }

    public function filter($column, $filter, $matchMode)
    {
        if($column->hasRelation()) {
            $columnLabel = "{$column->getRelation()->getAlias()}.{$column->getName()}";
        }
        else {
            $columnLabel = "{$this->getTableAlias()}.{$column->getName()}";
        }

        switch($matchMode) {
            case Datagrid_Filter::MATCH_BEGINS:
            $where = "$columnLabel LIKE '$filter%'";
            break;

            case Datagrid_Filter::MATCH_CONTAINS:
            $where = "$columnLabel LIKE '%$filter%'";
            break;

            default:
            $where = "$columnLabel LIKE '$filter%'";
        }
        $this->_query->addWhere($where);
    }

    /**
     * Pour mémoire ! No more used.
     */
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

}
