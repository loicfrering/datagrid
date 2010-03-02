<?php
/**
 * Cette classe doit se comporter comme la classe Column notamment en ce qui concerne les relations.
 * Cependant un filtre ne doit pas être attaché à une instance de Column
 */
class Datagrid_Filter
{
    const TYPE_TEXT      = 'TYPE_TEXT';
    const TYPE_SELECT    = 'TYPE_SELECT';
    const MATCH_BEGINS   = 'MATCH_BEGINS';
    const MATCH_CONTAINS = 'MATCH_CONTAINS';
    const SELECT_ALL     = '';
    
    protected $_name;
    protected $_field;
    protected $_type = self::TYPE_TEXT;
    protected $_label;
    protected $_description;
    protected $_selectValues = array();
    protected $_matchMode = self::MATCH_BEGINS;
    
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
            throw new Exception('Filter requires a non empty name');
        }

        if(!isset($this->_label)) {
            $this->_label = ucfirst($this->_name.':');
        }
        if(!isset($this->_field)) {
            $this->_field = $this->_name;
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
                throw new Exception("Unknown property '$key' for Filter");
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

    public function setField($field)
    {
        $this->_field = $field;

        return $this;
    }

    public function getField()
    {
        return $this->_field;
    }
    
    public function setType($type)
    {
        $this->_type = $type;
        
        return $this;
    }

    public function getType()
    {
        return $this->_type;
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

    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }
    
    public function setSelectValues(array $selectValues)
    {
        $this->_selectValues = $selectValues;

        return $this;
    }

    public function getSelectValues()
    {
        return $this->_selectValues;
    }
    
    public function setMatchMode($matchMode)
    {
        $this->_matchMode = $matchMode;

        return $this;
    }

    public function getMatchMode()
    {
        return $this->_matchMode;
    }
    
    public function getFormElement($params = null, $translator = null)
    {
        $param = isset($params[$this->_name]) ? $params[$this->_name] : '';

        if($this->_type == self::TYPE_TEXT) {
            $element = new Zend_Form_Element_Text($this->_name, array(
                    'label' => $this->_label,
                    'value' => $param
                ));
        }
        else if($this->_type == self::TYPE_SELECT) {
            $element = new Zend_Form_Element_Select($this->_name, array(
                    'multiOptions' => array(self::SELECT_ALL => isset($translator) ? $translator->_(Datagrid::FILTER_ALL_LABEL) : 'All', '----' => $this->_selectValues),
                    'label' => $this->_label,
                    'value' => $param
                ));
        }
        else {
            $element = new Zend_Form_Element_Text($this->_name, array(
                    'label' => $this->_label,
                    'value' => $param
                ));
        }

        if(!empty($this->_description)) {
            $element->setDescription($this->_description);
        }


        return $element;
    }

}