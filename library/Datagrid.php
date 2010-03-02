<?php
/**
 * Description of Datagrid
 *
 * Pensez à :
 * - Echapper les sorties
 * - Renommer template en mask ?
 * - Permettre les relations de relations
 * - i18n
 * - Filtres sur des relations
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
class Datagrid
{
    /**
     * DoctrineDatagrid constants
     */
    const ONE_RELATION  = 'ONE_RELATION';
    const MANY_RELATION = 'MANY_RELATION';
    const ASC_ORDER     = 'asc';
    const DESC_ORDER    = 'desc';

    const FILTER_SUBMIT_LABEL   = 'Filter';
    const FILTER_ALL_LABEL      = 'All';
    const FILTER_FIELDSET_LABEL = 'Filter';
    const FILTER_HIDE_LABEL     = 'Hide';
    const FILTER_SHOW_LABEL     = 'Show';
    const FILTER_RESET_LABEL    = 'Reset';
    const NO_RESULT_LABEL       = 'No result!';

    /**
     * Adapter
     * @var Datagrid_Adapter_Interface
     */
    protected $_adapter = null;

    /**
     * The caption for the table rendered by datagrid
     * @var string
     */
    protected $_caption;

    /**
     * Columns to display in datagrid
     * @var array
     */
    protected $_columns = array();
    
    /**
     * Relations used by the datagrid
     * @var array
     */
    protected $_relations = array();
    
    /**
     * Filters for organizing the datagrid
     * @var array
     */
    protected $_filters = array();
    
    /**
     * Commands to add on each datagrid row
     * @var array
     */
    protected $_commands = array();
    
    /**
     * OrderBy clauses for sorting the datagrid
     * @var array
     */
    protected $_orderby = array();

    /**
     * Datagrid current sorted column
     * @var string 
     */
    protected $_currentSortedColumn;
    
    /**
     * Datagrid current sort: ascending or descending
     * Use constants ASC_ORDER and DESC_ORDER
     * @var <type> 
     */
    protected $_currentSort;

    /**
     * The number on displayed records per page
     * @var integer
     */
    protected $_recordCountPerPage = 20;

    /**
     * Request parameters for organizing the datagrid
     * @var array
     */
    protected $_params = array();

    /**
     * The module of the incoming request
     * @var string
     */
    protected $_module;

    /**
     * The action of the incoming request
     * @var string
     */
    protected $_action;
    
    /**
     * The controller of the incoming request
     * @var string
     */
    protected $_controller;

    
    /**
     * The Zend View used to render datagrid
     * @var Zend_View
     */
    protected $_view;

    /**
     * A where clause to permanently filter the records displayed by the datagrid
     * @var string
     */
    protected $_where;

    /**
     * Datagrid i18n
     * @var Zend_Translate
     */
    protected $_translator;

    protected $_noResultLabel;
    
    /**
     * A form to be displayed in the Datagrid
     * @var Zend_Form
     */
    protected $_form;

    protected $_saveFiltersInSession;


    /**
     * Datagrid constructor
     *
     * @param string|Doctrine_Table $doctrineTable
     * @param array $params
     * @param mixed $options
     */
    public function __construct(Datagrid_Adapter_Interface $adapter, array $params = array(), $options = null)
    {
        $this->_adapter = $adapter;

        $this->setParams($params);
        
        $this->_view = new Zend_View();
        $this->_view->setScriptPath('../library/Datagrid');
        
        $this->_translator = new Zend_Translate('array', array());
        
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
    }

    public function  __toString()
    {
        return $this->render();
    }

    /**
     * Set datagrid state from options array
     * 
     * @param array $options
     * @return Datagrid
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                // Setter exists; use it
                $this->$method($value);
            }
            else {
                throw new Datagrid_Exception("Unknown property '$key' for Datagrid");
            }
        }
        return $this;
    }

    /**
     * Set the datagrid translator object
     *
     * @param Zend_Translate $translate
     * @return Datagrid
     */
    public function setTranslator($translator)
    {
        if($translator instanceof Zend_Translate) {
            $this->_translator = $translator;
        }
        else {
            throw new Datagrid_Exception('Translator must be a Zend_Translate instance');
        }
        return $this;
    }

    /**
     * Get the datagrid translator object
     *
     * @return Zend_Translate
     */
    public function getTranslator()
    {
        return $this->_translator;
    }

    /**
     *
     * @param string $noResultLabel
     * @return Datagrid
     */
    public function setNoResultLabel($noResultLabel)
    {
        $this->_noResultLabel = $noResultLabel;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getNoResultLabel()
    {
        return $this->_noResultLabel;
    }

    /**
     * Set table's caption
     *
     * @param string $caption
     * @return Datagrid
     */
    public function setCaption($caption)
    {
        $this->_caption = $caption;
        return $this;
    }

    /**
     * Get the table's caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->_caption;
    }

    /**
     * Set the where clause
     *
     * @param string $where
     * @return Datagrid
     */
    public function setWhere($where)
    {
        $this->_where = $where;
        return $this;
    }

    /**
     * Return the where clause
     *
     * @return string
     */
    public function getWhere()
    {
        return $this->_where;
    }
    
    /**
     * Set the request parameters for organizing the datagrid
     * 
     * @param array $params 
     * @return Datagrid
     */
    public function setParams(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Load the request parameters for organizing the datagrid
     * 
     * @return Datagrid
     */
    protected function _loadParams()
    {
        if(isset($this->_params['module'])) {
            $this->_module = $this->_params['module'];
        }
        if(isset($this->_params['controller'])) {
            $this->_controller = $this->_params['controller'];
        }
        if(isset($this->_params['action'])) {
            $this->_action = $this->_params['action'];
        }

        foreach($this->_params as $key => $value) {

            if($key == 'sort') {

                $sort = explode('-', $value, 2);
                $columnDisplayedName = $sort[0];
                $order = isset($sort[1]) ? strtolower($sort[1]) : self::ASC_ORDER;
                if(array_key_exists($columnDisplayedName, $this->_columns)) {
                    if($order == self::ASC_ORDER || $order == self::DESC_ORDER) {
                        $this->_currentSortedColumn = $columnDisplayedName;
                        $this->_currentSort = $order;
                    }
                    else {
                        throw new Datagrid_Exception('Invalid order provided in params');
                    }
                }
                else {
                    throw new Datagrid_Exception("Unknown column provided in order param: $columnDisplayedName");
                }

            }
        }

        return $this;
    }

    /**
     * @todo Find a new session namespace
     *
     * @return string
     */
    protected function _getSessionNamespace()
    {
        //return strtolower($this->_table->getTableName()).'-datagrid';
        return 'datagrid';
    }

    protected function _doResetFilters()
    {
        return isset($this->_params['filters']) && $this->_params['filters'] == 'reset';
    }

    protected function _loadFiltersFromSession()
    {
        $session = new Zend_Session_Namespace($this->_getSessionNamespace());

        // Bug in PHP 5.2:
        /*if(isset($session->filters)) {
            foreach($session->filters as $filterName => $filterValue) {
                $this->_params[$filterName] = $filterValue;
            }
        }*/
        // Patch:
        $sessionFilters = $session->filters;
        if(isset($sessionFilters)) {
            foreach($sessionFilters as $filterName => $filterValue) {
                $this->_params[$filterName] = $filterValue;
            }
        }

        return $this;
    }

    protected function _saveFiltersInSession()
    {
        $session = new Zend_Session_Namespace($this->_getSessionNamespace());
        $session->filters = array();

        foreach($this->_filters as $filter) {
            if(isset($this->_params[$filter->getName()])) {
                // Bug in PHP 5.2:
                //$session->filters[$filter->getName()] = $this->_params[$filter->getName()];
                // Patch:
                $sessionFilters = $session->filters;
                $sessionFilters[$filter->getName()] = $this->_params[$filter->getName()];
                $session->filters = $sessionFilters;
                
            }
        }

        return $this;
    }

    protected function _isFiltering()
    {
        foreach($this->_filters as $filter) {
            if(isset($this->_params[$filter->getName()])) {
                return true;
            }
        }

        return false;
    }

    protected function _isSorting()
    {
        return !empty($this->_currentSortedColumn);
    }

    protected function _prepare()
    {
        if($this->_isFiltering() || $this->_doResetFilters()) {
            $this->_saveFiltersInSession();
        }
        else if(!$this->_doResetFilters()) {
            $this->_loadFiltersFromSession();
        }

        $this->_loadParams();

        
        $this->_adapter->prepare();
        foreach($this->_filters as $filter) {
            if(isset($this->_params[$filter->getName()])) {
                $this->_adapter->filter($filter->getColumn(), $this->_params[$filter->getName()], $filter->getMatchMode(), $this->_columns[$filter->getColumn()]->getRelations($this->_relations));
            }
        }
        if($this->_isSorting()) {
            $this->_adapter->sort($this->_currentSortedColumn, $this->_currentSort, $this->_columns[$this->_currentSortedColumn]->getRelations($this->_relations));
        }

        /**
         * prepare
         * selectColumns
         * filter
         * sort
         */
    }
    
    public function render()
    {
        /*if(empty($this->_columns)) {
            foreach($this->_table->getColumnNames() as $columnName) {
                $this->addColumn($columnName);
            }
        }
        $columns = $this->_columns;*/

        $this->_prepare();



        // Paginate
        $paginator = new Zend_Paginator($this->_adapter);
        $paginator->setItemCountPerPage($this->_recordCountPerPage);
        $paginator->setCurrentPageNumber(isset($this->_params['page']) ? $this->_params['page'] : 1 );

        // Render
        $this->_view->action = $this->_action;
        $this->_view->controller = $this->_controller;
        $this->_view->module = $this->_module;
        $this->_view->translator = $this->_translator;
        $this->_view->noResultLabel = $this->_noResultLabel;
        $this->_view->caption = $this->_caption;
        $this->_view->relations = $this->_relations;
        $this->_view->columns = $this->_columns;
        if(!empty($this->_currentSortedColumn))
            $this->_view->currentSortedColumn = $this->_columns[$this->_currentSortedColumn];
        $this->_view->currentSort = $this->_currentSort;
        $this->_view->paginator = $paginator;
        $this->_view->filtersForm = $this->getFiltersForm();
        $this->_view->commands = $this->_commands;
        $this->_view->form = $this->getForm();

        return $this->_view->render('datagrid.phtml');
    }
    
    public function getFiltersForm()
    {
        if(empty($this->_filters)) {
            return null;
        }

        $form = new Zend_Form();
        $form->setName('filtersform')
             ->setMethod('get')
             ->setAction($this->_view->url(array('page' => 1)))
             ->addElementPrefixPath('Atos_Form_Decorator', 'Atos/Form/Decorator/', 'decorator')
             ->setDecorators(array(
                     'FormElements',
                     'Form'
                 ));

        foreach($this->_filters as $filter) {
            $element = $filter->getFormElement($this->_params, $this->_translator);
            $element->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    'Label',
                    array('HtmlTag', array('tag' => 'div'))
                ));

            $form->addElement($element);
        }

        $form->addElement('submit', 'submit', array(
                'label' => isset($this->_translator) ? $this->_translator->_(self::FILTER_SUBMIT_LABEL) : 'Filter',
                'decorators' => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'p'))
                )
            ));

        return $form;
    }

    // Form

    /**
     * Set the form to be displayed in the Datagrid
     * 
     * @param Zend_Form $form
     * @return Datagrid
     */
    public function setForm($form)
    {
        //$form = new Zend_Form();

        $form->setDecorators(array(
                'FormElements',
                array('HtmlTag', array('tag' => 'tr')),
                'Form'
            ));

        $form->setDisableLoadDefaultDecorators(true);
        $form->setElementDecorators(array(
                'ViewHelper',
                array('HtmlTag', array('tag' => 'td'))
            ));
        $form->setSubFormDecorators(array());
        $form->setDisplayGroupDecorators(array());        

        $this->_form = $form;
        return $this;
    }
    
    /**
     * Return the form to be displayed in the Datagrid
     * 
     * @return Zend_Form
     */
    public function getForm()
    {
        return $this->_form;
    }

    // Relations
    
    /**
     * Add a relation
     *
     * @param string|Datagrid_Relation $relation
     * @param array $options
     * @return Datagrid
     */
    public function addRelation($relation, $options = null)
    {
        if (!($relation instanceof Datagrid_Relation)) {
            $relation = new Datagrid_Relation($relation, $options);
        }

        if($relation->hasRelation() && !array_key_exists($relation->getRelation(), $this->_relations)) {
            throw new Datagrid_Exception("Datagrid does not have a relation named {$relation->getRelation()}");
        }
        else if(!$relation->hasRelation() && !$this->_adapter->hasRelation($relation->getName())) {
            //throw new Datagrid_Exception("Adapter '{$this->_table->getTableName()}' does not have a relation named {$relation->getName()}");
            // TODO: Find a descent name
            throw new Datagrid_Adapter_Exception('Adapter \''.get_class($this->_adapter).'\' does not have a relation named '.$relation->getName().'\'');
        }
        
        if(array_key_exists($relation->getName(), $this->_relations)) {
            throw new Datagrid_Exception("Relation {$relation->getName()} already exists");
        }

        // TODO:
        /*if($relation->hasRelation()) {
            $relation->setType(self::ONE_RELATION);
        }
        else {
            $relation->setType($this->_table->getRelation($relation->getName())->getType() ? self::MANY_RELATION : self::ONE_RELATION);
        }*/
        $this->_relations[$relation->getName()] = $relation;

        return $this;
    }
    
    /**
     * Add multiple relations at once
     *
     * @param array $relations
     * @return Datagrid
     */
    public function addRelations(array $relations)
    {
        foreach ($relations as $relationInfo) {
            if (is_string($relationInfo)) {
                $this->addRelation($relationInfo);
            } elseif ($relationInfo instanceof Datagrid_Relation) {
                $this->addRelation($relationInfo);
            } elseif (is_array($relationInfo)) {
                $argc    = count($relationInfo);
                $options = array();
                if (isset($relationInfo['relation'])) {
                    $relation = $relationInfo['relation'];
                    if (isset($relationInfo['options'])) {
                        $options = $relationInfo['options'];
                    }
                    $this->addRelation($relation, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $relation  = array_shift($relationInfo);
                        case (2 <= $argc):
                            $options = array_shift($relationInfo);
                        default:
                            $this->addRelation($relation, $options);
                            break;
                    }
                }
            } else {
                throw new Datagrid_Exception('Invalid relation passed to addRelations()');
            }
        }

        return $this;
    }

    /**
     * Remove all relations
     * 
     * @return Datagrid
     */
    public function clearRelations()
    {
        $this->_relations = array();
        return $this;
    }
    
    /**
     * Set relations (overwrites existing ones)
     *
     * @param <type> $relations
     * @return Datagrid
     */
    public function setRelations(array $relations)
    {
        $this->clearRelations();
        return $this->addRelations($relations);
    }

    // Columns
    
    /**
     * Add a column to the datagrid
     * 
     * @param array|Datagrid_Column $column
     * @param array $options
     * @return Datagrid
     */
    public function addColumn($column, $options = null)
    {
        if (!($column instanceof Datagrid_Column)) {
            $column = new Datagrid_Column($column, $options);
        }
        
        $relation = $column->getRelation();
        if($column->hasRelation() && !array_key_exists($relation['name'], $this->_relations)) {
            throw new Datagrid_Exception("Relation {$relation['name']} for column {$column->getDisplayedName()} does not exist");
        }

        if(!$column->hasRelation() && !$this->_adapter->hasColumn($column->getName())) {
             //throw new Datagrid_Exception("Table '{$this->_table->getTableName()}' does not have a column named {$column->getName()}");
             throw new Datagrid_Adapter_Exception('Adapter \''.get_class($this->_adapter).'\' does not have a column named \''.$column->getName().'\'');
        }
        
        if(array_key_exists($column->getDisplayedName(), $this->_columns)) {
            throw new Datagrid_Exception("Column '{$column->getDisplayedName()}' already exists");
        }
        
        $this->_columns[$column->getDisplayedName()] = $column;

        return $this;
    }
    
    /**
     * Add multiple columns at once
     * 
     * @param array $columns
     * @return Datagrid
     */
    public function addColumns(array $columns)
    {
        foreach ($columns as $columnInfo) {
            if (is_string($columnInfo)) {
                $this->addColumn($columnInfo);
            } elseif ($columnInfo instanceof Datagrid_Column) {
                $this->addColumn($columnInfo);
            } elseif (is_array($columnInfo)) {
                $argc    = count($columnInfo);
                $options = array();
                if (isset($columnInfo['column'])) {
                    $column = $columnInfo['column'];
                    if (isset($columnInfo['options'])) {
                        $options = $columnInfo['options'];
                    }
                    $this->addColumn($column, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $column  = array_shift($columnInfo);
                        case (2 <= $argc):
                            $options = array_shift($columnInfo);
                        default:
                            $this->addColumn($column, $options);
                            break;
                    }
                }
            } else {
                throw new Datagrid_Exception('Invalid column passed to addColumns()');
            }
        }

        return $this;
    }

    /**
     * Remove all columns
     *
     * @return Datagrid
     */
    public function clearColumns()
    {
        $this->_columns = array();
        return $this;
    }
    
    /**
     * Set columns (overwrites existing ones)
     * 
     * @param array $columns
     * @return Datagrid
     */
    public function setColumns(array $columns)
    {
        $this->clearColumns();
        return $this->addColumns($columns);
    }
    
    // Filters
    
    /**
     * Add a filter to the datagrid
     * 
     * @param array|Datagrid_Filter $filter
     * @param array $options
     * @return Datagrid
     */
    public function addFilter($filter, $options = null)
    {
        if (!$filter instanceof Datagrid_Filter) {
            $filter = new Datagrid_Filter($filter, $options);
        }
        
        $relation = $filter->getRelation();
        if($filter->hasRelation() && !array_key_exists($relation, $this->_relations)) {
            throw new Datagrid_Exception("Relation {$relation} for filter {$filter->getName()} does not exist");
        }

        if(!$filter->hasRelation() && !$this->_adapter->hasColumn($filter->getColumn())) {
             //throw new Datagrid_Exception("Table '{$this->_table->getTableName()}' does not have a column named {$filter->getColumn()} for filtering");
             throw new Datagrid_Adapter_Exception('Adapter \''.get_class($this->_adapter).'\' does not have a column named \''.$column->getName().'\'');
        }
        
        if(array_key_exists($filter->getName(), $this->_filters)) {
            throw new Datagrid_Exception("Filter '{$filter->getName()}' already exists");
        }
        
        $this->_filters[$filter->getName()] = $filter;

        return $this;
    }
    
    /**
     * Add multiple filters at once
     * 
     * @param array $filters
     * @return Datagrid
     */
    public function addFilters(array $filters)
    {
        foreach ($filters as $filterInfo) {
            if (is_string($filterInfo)) {
                $this->addFilter($filterInfo);
            } elseif ($filterInfo instanceof Datagrid_Filter) {
                $this->addFilter($filterInfo);
            } elseif (is_array($filterInfo)) {
                $argc    = count($filterInfo);
                $options = array();
                if (isset($filterInfo['column'])) {
                    $filter = $filterInfo['column'];
                    if (isset($filterInfo['options'])) {
                        $options = $filterInfo['options'];
                    }
                    $this->addFilter($filter, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $filter  = array_shift($filterInfo);
                        case (2 <= $argc):
                            $options = array_shift($filterInfo);
                        default:
                            $this->addFilter($filter, $options);
                            break;
                    }
                }
            } else {
                throw new Datagrid_Exception('Invalid filter passed to addFilters()');
            }
        }

        return $this;
    }

    /**
     * Remove all filters
     *
     * @return Datagrid
     */
    public function clearFilters()
    {
        $this->_filters = array();
        return $this;
    }
    
    /**
     * Set filters (overwrites existing ones)
     * 
     * @param array $filters
     * @return Datagrid
     */
    public function setFilters(array $filters)
    {
        $this->clearFilters();
        return $this->addFilters($filters);
    }

    public function setSaveFiltersInSession($saveFiltersInSession)
    {
        $this->_saveFiltersInSession = ($saveFiltersInSession == true);
        return $this;
    }

    public function doSaveFiltersInSession()
    {
        return $this->_saveFiltersInSession;
    }
    
    // Commands
    
    /**
     * Add a command to the datagrid
     * 
     * @param array|Datagrid_Command $command
     * @param array $options
     * @return Datagrid
     */
    public function addCommand($command, $options = null)
    {
        if ($command instanceof Datagrid_Command) {
            $name = $command->getName();
        }
        else {
            $command = new Datagrid_Command($command, $options);
        }
        
        if(array_key_exists($command->getName(), $this->_commands)) {
            throw new Datagrid_Exception("Command '{$command->getName()}' already exists");
        }
        
        $this->_commands[$command->getName()] = $command;

        return $this;
    }
    
    /**
     * Add multiple commands at once
     * 
     * @param array $commands
     * @return Datagrid
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $commandInfo) {
            if (is_string($commandInfo)) {
                $this->addCommand($commandInfo);
            } elseif ($commandInfo instanceof Datagrid_Command) {
                $this->addCommand($commandInfo);
            } elseif (is_array($commandInfo)) {
                $argc    = count($commandInfo);
                $options = array();
                if (isset($commandInfo['column'])) {
                    $command = $commandInfo['column'];
                    if (isset($commandInfo['options'])) {
                        $options = $commandInfo['options'];
                    }
                    $this->addCommand($command, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $command  = array_shift($commandInfo);
                        case (2 <= $argc):
                            $options = array_shift($commandInfo);
                        default:
                            $this->addCommand($command, $options);
                            break;
                    }
                }
            } else {
                throw new Datagrid_Exception('Invalid command passed to addCommands()');
            }
        }

        return $this;
    }

    /**
     * Remove all commands
     *
     * @return Datagrid
     */
    public function clearCommands()
    {
        $this->_commands = array();
        return $this;
    }
    
    /**
     * Set commands (overwrites existing ones)
     * 
     * @param array $commands
     * @return Datagrid
     */
    public function setCommands(array $commands)
    {
        $this->clearCommands();
        return $this->addCommands($commands);
    }

    // Sorting

    /**
     * Set current datagrid columns sortable property
     *
     * @param bool $sortable
     * @return Datagrid
     */
    public function setColumnsSortable($sortable)
    {
        foreach($this->_columns as $column) {
            $column->setSortable($sortable);
        }

        return $this;
    }

    /**
     * Get the current sorted column
     *
     * @return string
     */
    public function getCurrentSortedColumn()
    {
        return $this->_currentSortedColumn;
    }

    /**
     * Get current sorting type: ascending or descending
     * Uses constants ASC_ORDER and DESC_ORDER
     *
     * @return string
     */
    public function getCurrentSort()
    {
        return $this->_currentSort;
    }

    public function addOrderby($column, $order = self::ASC_ORDER)
    {
        if($this->_adapter->hasColumn($column)) {
            $this->_orderby[] = "$column $order";
        }

        return $this;
    }

    // Pagination

    /**
     * Set the number or records displayed per page
     * 
     * @param integer $recordCountPerPage 
     * @return Datagrid
     */
    public function setRecordCountPerPage($recordCountPerPage)
    {
        $this->_recordCountPerPage = $recordCountPerPage;
        return $this;
    }
    
    /**
     * Return the number or records displayed per page
     * 
     * @return integer
     */
    public function getRecordCountPerPage()
    {
        return $this->_recordCountPerPage;
    }

    // Construct query

    protected function _getQuery()
    {
        $this->_loadParams();
        
        $tableAlias = $this->_tableAlias;

        $query = $this->_table->createQuery($tableAlias);

        if(!empty($this->_where)) {
            $query->addWhere($this->_where);
        }

        foreach($this->_relations as $key => $relation) {
            $alias = $relation->getAlias();
            $where = $relation->getWhere();
            if($relation->hasRelation()) {
                $parentRelation = $this->_relations[$relation->getRelation()];
                $query->leftJoin("{$parentRelation->getAlias()}.{$relation->getName()} $alias");
            }
            else {
                $query->leftJoin("$tableAlias.{$relation->getName()} $alias");
            }
            if(!empty($where)){
                $query->addWhere($where);
            }
        }

        foreach($this->_filters as $filter) {
            if($filter->hasRelation()) {
                $where = $filter->getWhereClause($this->_tableAlias, $this->_params, $this->_relations[$filter->getRelation()]);
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
            $currentSortedColumn = $this->_columns[$this->_currentSortedColumn];
            if($currentSortedColumn->hasRelation()) {
                $relation = $currentSortedColumn->getRelation();
                $relation = $relation['name'];
                $query->addOrderBy($this->_columns[$this->_currentSortedColumn]->getOrderByClause($this->_relations[$relation]->getAlias(), $this->_currentSort));
            }
            else {
                $query->addOrderBy($this->_columns[$this->_currentSortedColumn]->getOrderByClause($this->_tableAlias, $this->_currentSort));
            }
        }
        //$query->addOrderBy('m.Seasons.id ASC');
        
        $query->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        //echo $query->getSqlQuery();

        return $query;
    }
}
