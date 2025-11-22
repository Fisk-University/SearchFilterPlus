<?php
namespace SearchFilterPlus;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Modify search query to handle date range
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'handleDateRangeQuery']
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $form = $services->get('FormElementManager')->get(Form\ConfigForm::class);
        $settings = $services->get('Omeka\Settings');
        
        $data = [
            'daterange_filter_min_year' => $settings->get('daterange_filter_min_year', 1910),
            'daterange_filter_max_year' => $settings->get('daterange_filter_max_year', 1950),
            'daterange_filter_property' => $settings->get('daterange_filter_property', 'dcterms:date'),
        ];
        
        $form->setData($data);
        return $renderer->render('date-range-filter/admin/config-form', [
            'form' => $form
        ]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(Form\ConfigForm::class);
        
        $params = $controller->getRequest()->getPost();
        $form->setData($params);
        
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }
        
        $formData = $form->getData();
        $settings->set('daterange_filter_min_year', $formData['daterange_filter_min_year']);
        $settings->set('daterange_filter_max_year', $formData['daterange_filter_max_year']);
        $settings->set('daterange_filter_property', $formData['daterange_filter_property']);
        
        $controller->messenger()->addSuccess('Date Range Filter settings updated.');
        return true;
    }

    public function handleDateRangeQuery(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        $queryBuilder = $event->getParam('queryBuilder');
        
        // Check if date range filter is active
        if (isset($query['date_start']) && isset($query['date_end'])) {
            $dateStart = (int) $query['date_start'];
            $dateEnd = (int) $query['date_end'];
            
            // Implementation that filters by date property
            // This will work for items that have dcterms:date values
            
            // Get the property ID for dcterms:date
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $propertyTerm = $settings->get('daterange_filter_property', 'dcterms:date');
            
            // Get the property from the API
            $api = $services->get('Omeka\ApiManager');
            
            try {
                // Search for the property by term
                $response = $api->search('properties', [
                    'term' => $propertyTerm,
                    'limit' => 1
                ]);
                
                $properties = $response->getContent();
                
                if (!empty($properties)) {
                    $property = $properties[0];
                    $propertyId = $property->id();
                    
                    // Create an alias for the values join
                    $alias = 'date_filter_' . uniqid();
                    
                    // Join with values table
                    $queryBuilder->leftJoin(
                        'omeka_root.values',
                        $alias,
                        'WITH',
                        $queryBuilder->expr()->eq($alias . '.property', $propertyId)
                    );
                    
                    // Create date range conditions
                    // This handles simple year values (YYYY)
                    $dateExpr = $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->isNotNull($alias . '.value'),
                        $queryBuilder->expr()->gte($alias . '.value', ':date_start'),
                        $queryBuilder->expr()->lte($alias . '.value', ':date_end')
                    );
                    
                    $queryBuilder->andWhere($dateExpr)
                        ->setParameter('date_start', (string) $dateStart)
                        ->setParameter('date_end', (string) $dateEnd);
                }
                
            } catch (\Exception $e) {
                // If there's an error getting the property
                error_log('Date filter error: ' . $e->getMessage());
            }
        }
    }
}