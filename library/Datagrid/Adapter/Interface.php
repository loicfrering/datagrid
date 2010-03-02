<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Datagrid_Adapter_Interface
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
interface Datagrid_Adapter_Interface extends Zend_Paginator_Adapter_Interface
{
    public function hasColumn($columnName);
    public function hasRelation($relationName);
    public function prepare();
    public function selectColumn($column, array $relations = array());
    public function sort($column, $order, array $relations = array());
    public function filter($column, $filter, $matchMode, array $relations = array());
}
