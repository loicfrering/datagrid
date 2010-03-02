<?php
/**
 * TODO:
 * - passer elementDecoratore en membre de la classe et du coup relation ne sera plus un tableau
 * - sortable et relation : TODO
 */
class Datagrid_Column
{
    protected $_datagrid;
    protected $_name;
    protected $_column;
    protected $_displayedName;
    protected $_title;
    protected $_renderingTemplate;
    protected $_renderingFunction;
    protected $_relation;
    protected $_decorator = array(
        'prepend' => '',
        'append' => '',
    );
    protected $_sortable = true;
    protected $_sorted = false;
    protected $_currentSortedOrder;
    protected $_groupSortedRecords = false;

    public function __construct($spec, $options = null)
    {
        if (is_string($spec)) {
            $this->setName($spec);
        } elseif (is_array($spec)) {
            $this->setOptions($spec);
        } elseif ($spec instanceof Zend_Config) {
            $this->setConfig($spec);
        } 
        
        if (is_string($spec) && is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($spec) && ($options instanceof Zend_Config)) {
            $this->setConfig($options);
        }
        
        if (empty($this->_name)) {
            throw new Exception('Column requires a non empty name');
        }

        if(!isset($this->_displayedName)) {
            $this->_displayedName = $this->_name;
        }
        if(!isset($this->_title)) {
            $this->_title = ucfirst($this->_name);
        }
    }
    
    public function setOptions(array $options)
    {
        if (isset($options['relation'])) {
            $this->setRelation($options['relation']);
            unset($options['relation']);
        }
        
        if (isset($options['decorator'])) {
            $this->setDecorator($options['decorator']);
            unset($options['decorator']);
        }

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                // Setter exists; use it
                $this->$method($value);
            }
            else {
                throw new Exception("Unknown property '$key' for Column");
            }
        }
        return $this;
    }

    public function setDatagrid($datagrid)
    {
        $this->_datagrid = $datagrid;

        return $this;
    }
    
    public function setName($name)
    {
        $this->_name = $name;
        
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }
    
    public function setDisplayedName($displayedName)
    {
        $this->_displayedName = $displayedName;
        
        return $this;
    }

    public function getDisplayedName()
    {
        return $this->_displayedName;
    }
    
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }
    
    public function getFormattedTitle()
    {
        return self::formatTitle($this->_title);
    }

    final public static function formatTitle($title)
    {
        return strtolower($title);
    }
    
    public function setRenderingTemplate($renderingTemplate)
    {
        $this->_renderingTemplate = $renderingTemplate;

        return $this;
    }

    public function getRenderingTemplate()
    {
        return $this->_renderingTemplate;
    }
    
    public function setRenderingFunction($renderingFunction)
    {
        $this->_renderingFunction = $renderingFunction;

        return $this;
    }

    public function getRenderingFunction()
    {
        return $this->_renderingFunction;
    }

    public function setRelation($relation)
    {
        $this->_relation = $relation;

        /*$relation = array(
            'name' => $options['name'],
            'elementDecorator' => array('prepend' => null, 'append' => null, 'glue' => null)
        );

        foreach (array_keys($relation) as $key) {
            if(!empty($options[$key])) {
                if(is_array($relation[$key])) {
                    
                    foreach (array_keys($relation[$key]) as $subkey) {
                        if(!empty($options[$key][$subkey])) {
                            $relation[$key][$subkey] = $options[$key][$subkey];
                        }
                    }
                }
                else {
                    $relation[$key] = $options[$key];
                }
            }
        }
        
        $this->_relation = $relation;*/
        
        return $this;
    }

    public function getRelation()
    {
        return $this->_relation;
    }

    public function hasRelation()
    {
        return !empty($this->_relation);
    }

    public function getRelations($relations)
    {
        if($this->hasRelation()) {
            $relation = $this->_relation;
            $relationsArray = array($relation->getName());
            while($relation->hasRelation()) {
                $relation = $relation->getRelation();
                $relation = $relations[$relation['name']];
                $relationsArray[] = $relation->getName();
            }
            return $relationsArray;
        }

        return array();
    }

    public function setDecorator(array $options)
    {
        $decorator = array(
            'prepend' => '',
            'append' => '',
        );

        foreach (array_keys($decorator) as $key) {
            if(!empty($options[$key]) && is_string($options[$key])) {
                $decorator[$key] = $options[$key];
            }
        }
        
        $this->_decorator = $decorator;
        
        return $this;
    }

    public function getDecorator()
    {
        return $this->_decorator;
    }

    public function setSortable($sortable)
    {
        $this->_sortable = (bool) $sortable;
        return $this;
    }

    public function isSortable()
    {
        return $this->_sortable;
    }

    public function isSorted()
    {
        return $this->_sorted;
    }

    public function setSorted($sorted)
    {
        $this->_sorted = (bool) $sorted;
        return $this;
    }

    public function getCurrentSortOrder()
    {
        return $this->_currentSortedOrder;
    }

    public function setCurrentSortedOrder($currentSortedOrder)
    {
        $this->_currentSortedOrder = $currentSortedOrder;
        return $this;
    }
    
    public function setGroupSortedRecords($groupSortedRecords)
    {
        $this->_groupSortedRecords = (bool) $groupSortedRecords;
        return $this;
    }

    public function doGroupSortedRecords()
    {
        return $this->_groupSortedRecords;
    }
    
    public function recordsEquals($record1, $record2, $relation = null)
    {
        return $this->render($record1, $relation) == $this->render($record2, $relation);
    }

    // TODO: Delete thic Doctrine specific method once in the adapter
    public function getOrderByClause($classAlias, $sort)
    {
        return "$classAlias.{$this->_name} $sort";
    }

    /**
     * TODO: implement isSorted() and getCurrentSortOrder().
     * Set currentSortedOrder in the concerned column in Datagrid class
     * @return string
     */
    public function renderTitle()
    {
        $view = $this->_datagrid->getView();
        $translator = $this->_datagrid->getTranslator();
        $params = $this->_datagrid->getParams();

        if($this->isSortable()) {
            if($this->isSorted() && $this->getCurrentSortOrder() == Datagrid::ASC_ORDER) {
                $sort = $this->getDisplayedName() . '-' . Datagrid::DESC_ORDER;
                $title = $translator->_(Datagrid::SORT_DESCENDING_LABEL);
            }
            else {
                $sort = $this->getDisplayedName();
                $title = $translator->_(Datagrid::SORT_ASCENDING_LABEL);
            }

            $url = $view->url(array('page' => 1, 'sort' => $sort));
            if(!empty($params)) {
                $url .= '?'.http_build_query($params);
            }

            return '<a href="'.$url.'" title="'.$title.'">'.$view->escape($this->getTitle()).'</a>';
        }
        return $view->escape($this->getTitle());
    }
    
    public function render($record, $relations = null)
    {
        if($this->hasRelation()) {
            $relation = $this->_relation;

            if($relation->hasRelation()) {
                $relationNames = array($this->_relation->getName());
                while($relation->hasRelation()) {
                    $relationNames[] = $relation->getRelation();
                    $relation = $relations[$relation->getRelation()];
                }

                $relationRecord = $record;
                for($i = count($relationNames) - 1; $i >= 0; $i--) {
                    $relationRecord = $relationRecord[$relationNames[$i]];
                }
            }
            else {
                $relationRecord = $record[$this->_relation->getName()];
            }

            return $this->_renderRelation($relationRecord, $relation->getType());
        }
        else {
            if(!empty($this->_renderingTemplate)) {
                return $this->_renderTemplate($record, $this->_renderingTemplate);
            }
            else if(!empty($this->_renderingFunction)) {
                return $this->_renderFunction($record, $this->_renderingFunction);
            }
            else {
                return $record[$this->_name];
            }
        }
    }
    
    protected function _renderRelation($relationRecord, $relationType)
    {
        if($relationType == Datagrid::ONE_RELATION) {
            $relationRecord = array($relationRecord);
        }
        
        $out = '';
        $count = count($relationRecord);
        if($count) {
            $out .= $this->_decorator['prepend'];
            for($i=0; $i<$count-1; $i++) {
                //$out .= $this->_relation['elementDecorator']['prepend'];
                if(!empty($this->_renderingTemplate)) {
                    $out .= $this->_renderTemplate($relationRecord[$i], $this->_renderingTemplate);
                }
                else if(!empty($this->_renderingFunction)) {
                    $out .= $this->_renderFunction($relationRecord[$i], $this->_renderingFunction);
                }
                else {
                    $out .= $relationRecord[$i][$this->_name];
                }
                //$out .= $this->_relation['elementDecorator']['append'].$this->_relation['elementDecorator']['glue'];
            }
            //$out .= $this->_relation['elementDecorator']['prepend'];
            if(!empty($this->_renderingTemplate)) {
                $out .= $this->_renderTemplate($relationRecord[$i], $this->_renderingTemplate);
            }
            else if(!empty($this->_renderingFunction)) {
                $out .= $this->_renderFunction($relationRecord[$i], $this->_renderingFunction);
            }
            else {
                $out .= $relationRecord[$i][$this->_name];
            }
            //$out .= $this->_relation['elementDecorator']['append'];

            $out .= $this->_decorator['append'];
        }
        
        return $out;
    }

    protected function _renderTemplate($record, $template)
    {
        $out = $template;
        $out = preg_replace('/{%([^}\[\]\<\>]+)}/e', "array_key_exists('\\1', \$record) ? \$record['\\1'] : '{%\\1}'", $out);
        return $out;
    }

    protected function _renderFunction($record, $function)
    {
        return $function($record);
    }
}
