<?php
namespace SearchFilterPlus\Service;

use SearchFilterPlus\DateRangeHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DateRangeHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $propertyManager = $services->get('Omeka\ApiManager')->search('properties')->getContent();
        $settings = $services->get('Omeka\Settings');
        
        return new DateRangeHandler($propertyManager, $settings);
    }
}