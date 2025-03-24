<?php
namespace SearchFilterPlus\Service\Form;

use SearchFilterPlus\Form\ConfigForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Create form without using setTranslator
        $form = new ConfigForm();
        // The form will use the MvcTranslator automatically through the FormElementManager
        return $form;
    }
}