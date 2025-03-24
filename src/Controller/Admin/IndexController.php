<?php
namespace SearchFilterPlus\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $settings;
    
    public function __construct($settings)
    {
        $this->settings = $settings;
    }
    
    public function indexAction()
    {
        $view = new ViewModel();
        return $view;
    }
}