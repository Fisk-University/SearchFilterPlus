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
        // Add date range filter to browse views
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.before',
            [$this, 'addDateRangeFilter']
        );
        
        // Modify search query to handle date range
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'handleDateRangeQuery']
        );
        
        // Add active filters display
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.after',
            [$this, 'displayActiveFilters']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.layout',
            [$this, 'addThemeStyles']
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

    public function addDateRangeFilter(Event $event)
    {
        $view = $event->getTarget();
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        
        $minYear = $settings->get('daterange_filter_min_year', 1910);
        $maxYear = $settings->get('daterange_filter_max_year', 1950);
        $property = $settings->get('daterange_filter_property', 'dcterms:date');
        
        // Get current filter values if set
        $query = $view->params()->fromQuery();
        $startYear = isset($query['date_start']) ? $query['date_start'] : $minYear;
        $endYear = isset($query['date_end']) ? $query['date_end'] : $maxYear;
        
        $vars = [
            'minYear' => $minYear,
            'maxYear' => $maxYear,
            'startYear' => $startYear,
            'endYear' => $endYear,
            'property' => $property,
        ];
        
        echo $view->partial('date-range-filter/common/date-range-slider', $vars);
    }

    public function handleDateRangeQuery(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        $queryBuilder = $event->getParam('queryBuilder');
        
        // Check if date range filter is active
        if (isset($query['date_start']) && isset($query['date_end'])) {
            $dateStart = (int) $query['date_start'];
            $dateEnd = (int) $query['date_end'];
            
            $services = $this->getServiceLocator();
            $dateRangeHandler = $services->get('SearchFilterPlus\DateRangeHandler');
            
            // Apply the date range filter
            $dateRangeHandler->applyDateRangeFilter($queryBuilder, $dateStart, $dateEnd);
        }
    }

    public function displayActiveFilters(Event $event)
    {
        $view = $event->getTarget();
        $query = $view->params()->fromQuery();
        
        // Check if date range filter is active
        if (isset($query['date_start']) && isset($query['date_end'])) {
            $vars = [
                'dateStart' => $query['date_start'],
                'dateEnd' => $query['date_end'],
            ];
            
            echo $view->partial('date-range-filter/common/active-filters', $vars);
        }
    }
    public function addThemeStyles(Event $event)
    {
        $view = $event->getTarget();
        
        // Check if we're on a browse or search page
        $params = $view->params();
        $routeName = $params->fromRoute('__ROUTE__');
        
        if (strpos($routeName, 'site/item') !== false) {
            // Check if search-page.css exists in the active theme
            $theme = $view->site()->theme();
            $assetUrl = $view->plugin('assetUrl');
            
            // Check if theme has search-page.css
            $themeCssPath = "themes/$theme/asset/css/search-page.css";
            if (file_exists(OMEKA_PATH . "/themes/$theme/asset/css/search-page.css")) {
                $view->headLink()->appendStylesheet($assetUrl('css/search-page.css'));
            }
        }
    }
}