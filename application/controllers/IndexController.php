<?php
/**
 * Description of IndexController
 *
 * @author Loïc Frering <loic.frering@gmail.com>
 */
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {

    }

    public function arrayAction()
    {
        $array = array(
            array(
                'firstname' => 'Loïc',
                'lastname' => 'Frering',
                'age' => 24,
                'job' => 'Responsable d\'Application',
                'Phones' => array(
                    array(
                        'label' => 'home',
                        'number' => '0 954 954 786'
                    ),
                    array(
                        'label' => 'mobile',
                        'number' => '06 48 08 67 79'
                    )
                )
            ),
            array(
                'firstname' => 'Solène',
                'lastname' => 'Weber',
                'age' => 25,
                'job' => 'Business Analyst',
                'Phones' => array(
                    array(
                        'label' => 'home',
                        'number' => '0 954 954 786'
                    ),
                    array(
                        'label' => 'mobile',
                        'number' => '06 47 52 52 95'
                    )
                )
            )
        );
        
        $datagrid = new Datagrid(new Datagrid_Adapter_Array($array), $this->_getAllParams());
        $datagrid->setRecordCountPerPage(1)
                 ->addRelation('Phones')
                 ->addColumn('firstname')
                 ->addColumn('lastname')
                 ->addColumn('age')
                 ->addColumn('job')
                 ->addColumn('number', array(
                         'decorator' => array(
                            'prepend' => '<ul>',
                            'append' => '</ul>'
                         ),
                         'relation' => array(
                            'name' => 'Phones',
                            'elementDecorator' => array(
                                'prepend' => '<li>',
                                'append' => '</li>'
                            )
                         )
                     ))
                 ->addFilter('firstname')
                 ->addFilter('job', array(
                        'matchMode' => Datagrid_Filter::MATCH_CONTAINS
                     ));

        $this->view->datagrid = $datagrid;
    }

    public function doctrineAction()
    {
        $datagrid = new Datagrid(new Datagrid_Adapter_Doctrine('User'), $this->_getAllParams());
        $datagrid->setRecordCountPerPage(1)
                 ->addRelation('Phones')
                 ->addColumn('firstname')
                 ->addColumn('lastname')
                 ->addColumn('age')
                 ->addColumn('job')
                 ->addColumn('number', array(
                         'decorator' => array(
                            'prepend' => '<ul>',
                            'append' => '</ul>'
                         ),
                         'relation' => array(
                            'name' => 'Phones',
                            'elementDecorator' => array(
                                'prepend' => '<li>',
                                'append' => '</li>'
                            )
                         )
                     ))
                 ->addFilter('firstname')
                 ->addFilter('job', array(
                        'matchMode' => Datagrid_Filter::MATCH_CONTAINS
                     ));

        $this->view->datagrid = $datagrid;
    }
}
