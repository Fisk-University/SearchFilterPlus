<?php
namespace SearchFilterPlus;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractResourceEntityAdapter;

class DateRangeHandler
{
    protected $propertyManager;
    protected $settings;

    public function __construct($propertyManager, $settings)
    {
        $this->propertyManager = $propertyManager;
        $this->settings = $settings;
    }

    public function applyDateRangeFilter(QueryBuilder $qb, $startYear, $endYear)
    {
        // Get the property ID for date property
        $propertyTerm = $this->settings->get('daterange_filter_property', 'dcterms:date');
        $property = $this->getPropertyByTerm($propertyTerm);
        
        if (!$property) {
            return;
        }
        
        $propertyId = $property->getId();
        $alias = $this->createAlias();
        
        // Build complex query to handle different date formats
        $qb->leftJoin(
            'omeka_root.values', $alias,
            'WITH', $qb->expr()->eq($alias . '.property', $propertyId)
        );
        
        // Create a complex expression to handle different date formats
        $dateExpressions = [];
        
        // Handle single year (YYYY) format
        $dateExpressions[] = $qb->expr()->andX(
            $qb->expr()->isNotNull($alias . '.value'),
            $qb->expr()->gte($alias . '.value', ':date_start_year'),
            $qb->expr()->lte($alias . '.value', ':date_end_year')
        );
        
        // Handle year range (YYYY/YYYY) format
        $dateExpressions[] = $qb->expr()->andX(
            $qb->expr()->isNotNull($alias . '.value'),
            $qb->expr()->like($alias . '.value', ':date_range_pattern')
        );
        
        // Add the combined expression to the query
        $qb->andWhere($qb->expr()->orX(...$dateExpressions))
           ->setParameter('date_start_year', (string) $startYear)
           ->setParameter('date_end_year', (string) $endYear)
           ->setParameter('date_range_pattern', '%/%');
        
        // Add a having clause to filter out items where no date matches
        $this->addDateRangeHavingClause($qb, $alias, $startYear, $endYear);
    }

    private function addDateRangeHavingClause(QueryBuilder $qb, $alias, $startYear, $endYear)
    {
        // This function adds a HAVING clause to filter date ranges (YYYY/YYYY)
        // by checking if the ranges overlap with the selected range
        
        $havingExpr = $qb->expr()->orX(
            // Case 1: Single year value is within range
            $qb->expr()->andX(
                $qb->expr()->notLike($alias . '.value', '%/%'),
                $qb->expr()->gte($alias . '.value', ':start_year'),
                $qb->expr()->lte($alias . '.value', ':end_year')
            ),
            
            // Case 2: Date range overlaps with selected range
            $qb->expr()->andX(
                $qb->expr()->like($alias . '.value', '%/%'),
                
                // Max logic to check if ranges overlap
                $qb->expr()->orX(
                    // Start year of range falls within selection
                    $qb->expr()->andX(
                        $qb->expr()->gte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', 1)', ':start_year'),
                        $qb->expr()->lte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', 1)', ':end_year')
                    ),
                    
                    // End year of range falls within selection
                    $qb->expr()->andX(
                        $qb->expr()->gte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', -1)', ':start_year'),
                        $qb->expr()->lte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', -1)', ':end_year')
                    ),
                    
                    // Selection range is completely inside item's date range
                    $qb->expr()->andX(
                        $qb->expr()->lte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', 1)', ':start_year'),
                        $qb->expr()->gte('SUBSTRING_INDEX(' . $alias . '.value, \'/\', -1)', ':end_year')
                    )
                )
            )
        );
        
        $qb->having($havingExpr)
           ->setParameter('start_year', (string) $startYear)
           ->setParameter('end_year', (string) $endYear);
    }

    private function getPropertyByTerm($term)
    {
        $properties = $this->propertyManager->getPropertyIds();
        if (isset($properties[$term])) {
            return $properties[$term];
        }
        return null;
    }

    private function createAlias()
    {
        return 'date_range_' . substr(md5(random_bytes(10)), 0, 10);
    }
}