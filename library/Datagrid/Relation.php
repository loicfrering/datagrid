<?php
class Datagrid_Relation
{
    protected $_name;
    protected $_alias;
    protected $_type;
    protected $_where;
    protected $_relation;

    public function __construct($spec, $options)
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
            throw new Exception('Relation requires a non empty name');
        }
        
        if(empty($this->_alias)) {
            $this->_alias = strtolower($this->_name);
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
                throw new Exception("Unknown property '$key' for Relation");
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
    
    public function setAlias($alias)
    {
        $this->_alias = $alias;
        
        return $this;
    }

    public function getAlias()
    {
        return $this->_alias;
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

    public function setWhere($where)
    {
        $this->_where = $where;
        
        return $this;
    }

    public function getWhere()
    {
        return $this->_where;
    }

    public function setRelation($relation)
    {
        $this->_relation = $relation;

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
}
