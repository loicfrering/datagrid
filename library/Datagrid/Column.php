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
    protected $_data;
    protected $_title;
    /*protected $_renderingTemplate;
    protected $_renderingFunction;*/
    protected $_decorator = array(
        'prepend' => '',
        'append' => '',
    );
    protected $_sortable = true;
    protected $_sortingField;
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

        if(!isset($this->_data)) {
            $this->_data = $this->_name;
        }
        if(!isset($this->_title)) {
            $this->_title = ucfirst($this->_name);
        }
        if(!isset($this->_sortingField)) {
            if(!$this->_hasRenderingFunction($this->getData()) && !$this->_hasRenderingTemplate($this->getData())) {
                $this->_sortingField = $this->_data;
            }
        }
    }
    
    public function setOptions(array $options)
    {
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

    public function setData($data)
    {
        $this->_data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->_data;
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
        return $this->getSortingField() !== null && $this->_sortable;
    }

    public function setSortingField($sortingField)
    {
        $this->_sortingField = $sortingField;
        return $this;
    }

    public function getSortingField()
    {
        return $this->_sortingField;
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
    
    public function recordsEquals($record1, $record2)
    {
        return $this->render($record1) == $this->render($record2);
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
                $sort = $this->getName() . '-' . Datagrid::DESC_ORDER;
                $title = $translator->_(Datagrid::SORT_DESCENDING_LABEL);
            }
            else {
                $sort = $this->getName();
                $title = $translator->_(Datagrid::SORT_ASCENDING_LABEL);
            }

            $url = $view->url(array('page' => 1, 'sort' => $sort));
            if(!empty($params)) {
                unset($params['module'], $params['controller'], $params['action']);
                $url .= '?'.http_build_query($params);
            }

            return '<a href="'.$url.'" title="'.$title.'">'.$view->escape($this->getTitle()).'</a>';
        }
        return $view->escape($this->getTitle());
    }
    
    public function render($item)
    {
        return $this->_render($item, $this->getData());
    }

    protected function _render($item, $data)
    {
        if($this->_hasRenderingTemplate($data)) {
            return $this->_renderTemplate($item, $data);
        }
        else if($this->_hasRenderingFunction($data)) {
            $function = $data;
            return $this->_renderFunction($item, $function);
        }
        else {
            $values = $this->_datagrid->getAdapter()->get($item, $data);
            if(null === $values) {
                return $data;
            }
            else if(is_array($values)) {
                return implode(' - ', $values);
            }
            else {
                return $values;
            }
        }
    }

    protected function _hasRenderingTemplate($data)
    {
        return is_string($data) && strpos($data, '{%') !== false;
    }

    protected function _hasRenderingFunction($data)
    {
        return is_callable($data);
    }
    
    protected function _renderTemplate($item, $template)
    {
        $adapter = $this->_datagrid->getAdapter();

        // Extract required fields in template
        preg_match_all('/{%([^}\[\]\<\>]+)}/', $template, $matches);
        $fields = $matches[1];

        $outputArray = array($template);
        foreach($fields as $field) {
            // Get field value or values from adapter
            $values = $adapter->get($item, $field);

            if(null !== $values) {
                // Adapter returned multiple values
                if(is_array($values)) {
                    if(count($outputArray) == 1 && count($values) > 1) {
                        $outputArray = array_fill(0, count($values), $outputArray[0]);
                    }
                    else if(count($values) > 1 && count($values) != count($outputArray)) {
                        throw new Datagrid_Exception('Incompatible fields in template value \'' . $template . '\' for column \'' . $this->getName() . '\'.');
                    }
                    $i = 0;
                    foreach($values as $value) {
                        $outputArray[$i] = str_replace('{%' . $field . '}', $value, $outputArray[$i]);
                        $i++;
                    }
                }
                // Adapter returned one value
                else {
                    $value = $values;
                    foreach($outputArray as $key => $out) {
                        $outputArray[$key] = str_replace('{%' . $field . '}', $value, $out);
                    }
                }
            }

        }

        return implode(' - ', $outputArray);

    }

    protected function _renderFunction($item, $function)
    {
        $adapter = $this->_datagrid->getAdapter();
        $get = function($field) use ($item, $adapter) {
            return $adapter->get($item, $field);
        };

        $template = $function($get);

        return $this->_render($item, $template);
    }
}
