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
        // Both filters attach to the same event and work together
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'handleFilters']
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

    /**
     * Handle both date range and file type filters in one method
     */
    public function handleFilters(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        $queryBuilder = $event->getParam('queryBuilder');
        
        // Apply date range filter if present
        if (isset($query['date_start']) && isset($query['date_end'])) {
            $this->applyDateRangeFilter($queryBuilder, $query);
        }
        
        // Apply file type filter if present
        if (isset($query['file_type']) && !empty($query['file_type'])) {
            $this->applyFileTypeFilter($queryBuilder, $query);
        }
        
        // Apply collection filter if present
        if (isset($query['collection']) && !empty($query['collection'])) {
            $this->applyCollectionFilter($queryBuilder, $query);
        }
    }
    
    /**
     * Apply date range filter
     */
    protected function applyDateRangeFilter($queryBuilder, $query)
    {
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
                $alias = 'date_filter';
                
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
    
    /**
     * Apply file type filter
     */
    protected function applyFileTypeFilter($queryBuilder, $query)
    {
        $fileTypes = $query['file_type'];
        if (!is_array($fileTypes)) {
            $fileTypes = [$fileTypes];
        }
        
        // Check if we already joined media table
        $joins = $queryBuilder->getDQLPart('join');
        $mediaAlias = null;
        
        if (!empty($joins['omeka_root'])) {
            foreach ($joins['omeka_root'] as $join) {
                if (strpos($join->getJoin(), '.media') !== false) {
                    $mediaAlias = $join->getAlias();
                    break;
                }
            }
        }
        
        // Only join if not already joined
        if (!$mediaAlias) {
            $mediaAlias = 'media_filter';
            $queryBuilder->innerJoin(
                'omeka_root.media',  // Use the relationship, not the table
                $mediaAlias
            );
        }
        
        // Build conditions for selected file types
        $conditions = [];
        $paramCount = 0;
        
        foreach ($fileTypes as $type) {
            $paramName = 'file_type_' . $paramCount++;
            $conditions[] = $queryBuilder->expr()->eq($mediaAlias . '.mediaType', ':' . $paramName);
            $queryBuilder->setParameter($paramName, $type);
        }
        
        if (!empty($conditions)) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
            // Group by item ID to prevent duplicates
            $queryBuilder->groupBy('omeka_root.id');
        }
    }
    
    /**
     * Apply collection filter
     */
    protected function applyCollectionFilter($queryBuilder, $query)
    {
        $collections = $query['collection'];
        if (!is_array($collections)) {
            $collections = [$collections];
        }
        
        // Join with item_sets through relationship
        $collectionAlias = 'collection_filter';
        
        $queryBuilder->innerJoin(
            'omeka_root.itemSets',
            $collectionAlias
        );
        
        // Build conditions for selected collections
        $conditions = [];
        $paramCount = 0;
        
        foreach ($collections as $collectionId) {
            $paramName = 'collection_' . $paramCount++;
            $conditions[] = $queryBuilder->expr()->eq($collectionAlias . '.id', ':' . $paramName);
            $queryBuilder->setParameter($paramName, (int)$collectionId);
        }
        
        if (!empty($conditions)) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
            $queryBuilder->groupBy('omeka_root.id');
        }
    }
}