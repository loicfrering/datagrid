<?php
class Datagrid_Command
{
    protected $_name;
    protected $_label;
    protected $_conditionColumn;
    protected $_conditionValue;
    protected $_urlOptions = array();
    protected $_reset = true;
    protected $_params;

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
            throw new Exception('Command requires a non empty name');
        }

        if(!isset($this->_label)) {
            $this->_label = ucfirst($this->_name);
        }
    }
    
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                // Setter exists; use it
                $this->$method($value);
            }
            else {
                throw new Exception("Unknown property '$key' for Command");
            }
        }
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
    
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->_label;
    }
    
    public function setConditionColumn($conditionColumn)
    {
        $this->_conditionColumn = $conditionColumn;

        return $this;
    }

    public function getConditionColumn()
    {
        return $this->_conditionColumn;
    }
    
    public function setConditionValue($conditionValue)
    {
        $this->_conditionValue = $conditionValue;

        return $this;
    }

    public function getConditionValue()
    {
        return $this->_conditionValue;
    }
    
    public function setCondition($condition)
    {
        $this->_condition = $condition;

        return $this;
    }

    public function hasCondition()
    {
        return !empty($this->_conditionColumn) && !empty($this->_conditionValue);
    }
    
    public function setUrlOptions(array $urlOptions)
    {
        $this->_urlOptions = $urlOptions;

        return $this;
    }

    public function getUrlOptions()
    {
        return $this->_urlOptions;
    }

    public function setReset($reset)
    {
        $this->_reset = (bool) $reset;
        return $this;
    }

    public function doReset()
    {
        return $this->_reset;
    }
    
    public function setParams(array $params)
    {
        $this->_params = $params;
        
        return $this;
    }

    public function getParams()
    {
        return $this->_params;
    }
    
    public function render($record, $view)
    {
        if($this->hasCondition()) {
            $conditionColumn = $this->getConditionColumn();
            $conditionValue = $this->getConditionValue();
            
            if(isset($record[$conditionColumn]) && $record[$conditionColumn] == $conditionValue) {
                return '<a href="'.$view->url($this->makeUrlOptions($record), null, $this->doReset()).'" title="'.$this->getLabel().'">'.$this->getLabel().'</a>';
            }
            else {
                return $this->getLabel();
            }
        }
        else {
            return '<a href="'.$view->url($this->makeUrlOptions($record), null, $this->doReset()).'" title="'.$this->getLabel().'">'.$this->getLabel().'</a>';
        }
    }
    
    public function makeUrlOptions($record) {
        $urlOptionsParams = array();
        foreach($this->_params as $key => $param) {
            if(isset($record[$param])) {
                $urlOptionsParams[is_integer($key) ? $param : $key] = $record[$param];
            }
            else {
                throw new Exception("Unknown column '$param' for record");
            }
        }

        return $this->_urlOptions + $urlOptionsParams;
    }
}
