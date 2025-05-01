<?php
namespace SearchFilterPlus\Form;

use Laminas\Form\Form;
use Laminas\Form\Element\Number;
use Omeka\Form\Element\PropertySelect;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'daterange_filter_min_year',
            'type' => Number::class,
            'options' => [
                'label' => 'Minimum Year', // @translate
                'info' => 'The earliest year that can be selected in the date range slider', // @translate
            ],
            'attributes' => [
                'id' => 'daterange_filter_min_year',
                'min' => 1800,
                'max' => 2100,
                'step' => 1,
                'required' => true,
            ],
        ]);
        
        $this->add([
            'name' => 'daterange_filter_max_year',
            'type' => Number::class,
            'options' => [
                'label' => 'Maximum Year', // @translate
                'info' => 'The latest year that can be selected in the date range slider', // @translate
            ],
            'attributes' => [
                'id' => 'daterange_filter_max_year',
                'min' => 1800,
                'max' => 2100,
                'step' => 1,
                'required' => true,
            ],
        ]);
        
        $this->add([
            'name' => 'daterange_filter_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Date Property', // @translate
                'info' => 'Select which property to use for date filtering', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'daterange_filter_property',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a date property', // @translate
                'required' => true,
            ],
        ]);
        
        $this->getInputFilter()->add([
            'name' => 'daterange_filter_min_year',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
        ]);
        
        $this->getInputFilter()->add([
            'name' => 'daterange_filter_max_year',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
        ]);
        
        $this->getInputFilter()->add([
            'name' => 'daterange_filter_property',
            'required' => true,
        ]);
    }
}