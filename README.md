# SearchFilterPlus Module for Omeka S

A comprehensive filtering module for Omeka S digital collections, enabling users to filter search results by date ranges, file types, and collections through intuitive interfaces.

## Overview

SearchFilterPlus enhances the Omeka S search functionality by adding visual filters that work together seamlessly. Originally developed for the Rosenwald Fund Collection (RWCF) at Fisk University, this module provides a reusable solution for any Omeka S installation requiring advanced filtering capabilities.

## Features

### 🗓️ Date Range Filter

- Interactive slider interface for selecting date ranges
- Auto-filtering - results update automatically when slider is adjusted
- Configurable date ranges - admin can set minimum and maximum years
- Handles date formats - supports date ranges (1917/1938)

### 📁 File Type Filter

- Checkbox interface for selecting multiple file types
- Automatic detection of available file types in your collection
- Support for common formats: JPEG/JPG, PNG, PDF
- Visual indicators showing file counts per type

### 📚 Collection Filter

- Item Set filtering - filter by Omeka S item sets/collections
- "Coming Soon" support - displays informative message for empty collections
- Dynamic counts - shows number of items per collection
- All collections visible - includes both active and future collections

### ⚡ Advanced Features

- Combined filtering - all filters work together simultaneously
- Search context preservation - maintains existing search parameters
- Responsive design - adapts to mobile, tablet, and desktop layouts
- Performance optimized - efficient database queries with proper indexing

## Requirements

- **Omeka S**: 3.0.0 or higher
- **PHP**: 7.4 or higher
- **Database**: MySQL with proper indexing on media_type, extension, and value fields
- **jQuery/jQuery UI**: Automatically loaded by the module

## Installation

1. Download the SearchFilterPlus module

2. Extract to your Omeka S modules directory:

   ```bash
   cd /path/to/omeka/modules
   unzip SearchFilterPlus.zip
   ```

3. Set permissions:

   ```bash
   chmod -R 755 SearchFilterPlus
   chown -R www-data:www-data SearchFilterPlus  # Linux
   # OR
   chown -R _www:_www SearchFilterPlus  # macOS
   ```

4. Install via Admin Panel:
   - Navigate to Admin → Modules
   - Find SearchFilterPlus and click "Install"
   - Click "Activate"

## Configuration

### Admin Settings

Configure the module at **Admin → Modules → SearchFilterPlus → Configure**:

- **Minimum Year**: Starting year for date range slider (default: 1910)
- **Maximum Year**: Ending year for date range slider (default: 1950)
- **Date Property**: Metadata property to filter on (default: dcterms:date)

### Theme Integration

Add filters to your theme's browse template:

```php
<!-- In your theme's browse.phtml -->
<div class="search-filters-sidebar">
    <?php 
    // Date Range Filter
    $filterVars = [
        'minYear' => 1910,
        'maxYear' => 1950,
        'startYear' => 1910,
        'endYear' => 1950,
        'property' => 'dcterms:date'
    ];
    echo $this->partial('date-range-filter/common/date-range-slider.phtml', $filterVars);
    
    // File Type Filter
    echo $this->partial('file-type-filter/common/file-type-filter.phtml');
    
    // Collection Filter  
    echo $this->partial('collection-filter/common/collection-filter.phtml');
    ?>
</div>
```

## Usage

### For End Users

1. **Date Filtering**: Drag the slider handles to select your desired date range
2. **File Type Filtering**: Check boxes for file types you want to include
3. **Collection Filtering**: Select specific collections/item sets to browse
4. **Combined Filtering**: Use multiple filters together for precise results
5. **Clear Filters**: Uncheck boxes or reset slider to remove filters

### Filter Behavior

- **AND Logic**: Date range AND file type AND collection filters work together
- **OR Logic**: Within each filter type (e.g., "JPEG OR PNG" for file types)
- **Preserved Context**: Filters maintain existing search terms and pagination
- **Real-time Updates**: Results refresh automatically when filters change

## Styling Customization

The module uses SCSS variables for easy theming. Override these in your theme:

```scss
// Colors
$color-primary: #653C2C;
$color-white: #FFFFFF;
$btn-primary-hover: #7A5141;
$btn-primary-pressed: #A17756;

// Typography
$font-sans: 'Montserrat', sans-serif;
$font-serif: 'Sorts Mill Goudy', serif;
$weight-semibold: 600;

// Spacing
$spacing-sm: 8px;
$spacing-md: 16px;
$spacing-lg: 32px;
```

### Responsive Breakpoints

```scss
// Mobile first approach
@media screen and (min-width: 576px) { /* Small tablets */ }
@media screen and (min-width: 768px) { /* Tablets - sidebar appears */ }
@media screen and (min-width: 992px) { /* Desktop */ }
@media screen and (min-width: 1200px) { /* Large desktop */ }
```

## Database Schema

The module works with standard Omeka S tables: (Below database optimization is "_Optional_", without this the module works perfectly fine)

### Date Filtering

- **Tables**: `value` ← `property`
- **Key Fields**: `property_id` (dcterms:date = 7), `value` (year data)

### File Type Filtering

- **Tables**: `media`
- **Key Fields**: `media_type` (MIME types), `extension`, `item_id`

### Collection Filtering

- **Tables**: `item_item_set` ← `item_set`
- **Key Fields**: `item_id`, `item_set_id`

## Performance Considerations

### Database Optimization

```sql
-- Recommended indexes for optimal performance
CREATE INDEX idx_media_type ON media(media_type);
CREATE INDEX idx_media_extension ON media(extension);  
CREATE INDEX idx_media_item ON media(item_id);
CREATE INDEX idx_value_property ON value(property_id, value);
CREATE INDEX idx_item_set_mapping ON item_item_set(item_set_id, item_id);
```

### Caching Strategy

- **Item counts**: Cached per collection to avoid repeated queries
- **File type discovery**: Cached list of available MIME types
- **Property lookups**: Cached property ID resolution

## Troubleshooting

### Common Issues

**Filters not appearing**

- Verify module is activated in Admin → Modules
- Check that partials are included in your theme's `browse.phtml`
- Ensure proper file permissions on module directory

**Incorrect result counts**

- Verify database indexes are in place
- Check for proper JOIN relationships in query building
- Clear Omeka's cache: `rm -rf files/cache/*`

**Styling issues**

- Clear asset cache: `rm -rf files/asset/*`
- Check browser developer tools for CSS conflicts
- Verify SCSS variable overrides are properly loaded

**Date filtering not working**

- Confirm items have `dcterms:date` metadata
- Verify date format in database (expects `YYYY` or `YYYY/YYYY`)
- Check configured date property in admin settings

**JavaScript errors**

- Verify jQuery UI is loading correctly
- Check browser console for JavaScript conflicts
- Ensure proper event binding in `date-range-slider.js`

### Debug Mode

Add debugging to `Module.php`:

```php
// In handleFilters method
error_log('=== SearchFilterPlus Debug ===');
error_log('Query params: ' . json_encode($query));
error_log('Generated SQL: ' . $queryBuilder->getQuery()->getSQL());
```

## Development

### Testing Locally

```bash
# Navigate to your Omeka installation
cd /path/to/omeka

# Enable error reporting for debugging
# In config/local.config.php, set:
'logger' => ['log' => true]
```

### Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-filter`)
3. Make your changes following PSR-12 coding standards
4. Add tests for new functionality
5. Submit a pull request

## Support

For issues, questions, or contributions:

- **GitHub Issues**: [https://github.com/Fisk-University/SearchFilterPlus/issues]

---

*This module represents collaborative work between academic institutions and digital humanities initiatives to make historical collections more accessible and discoverable.*
