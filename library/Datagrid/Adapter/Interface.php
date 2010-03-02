<?php
/**
 * Description of Datagrid_Adapter_Interface
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
interface Datagrid_Adapter_Interface extends Zend_Paginator_Adapter_Interface
{
    public function hasColumn($columnName);
    public function hasRelation($relationName);
    public function prepare(array $columns, array $relations);
    public function sort($column, $order);
    public function filter($column, $filter, $matchMode);
}
