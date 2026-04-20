# SearchFilterPlus

An [Omeka S](https://omeka.org/s/) module that adds interactive filtering components to the item browse page, enabling visitors to filter search results by date range, file type, and collection (item set).

Developed by [Fisk University](https://www.fisk.edu/) for the [Rosenwald Fund Collection](https://rosenwald.fisk.edu/), a Mellon Foundation-funded digital humanities project. Designed to be reusable for any Omeka S installation.

## Features

**Date Range Filter** — A dual-handle jQuery UI slider that lets users select a year range. Results update automatically when the slider is released. Supports both single-year (`1925`) and range-format (`1917/1938`) date values. The minimum year, maximum year, and target metadata property are configurable from the admin panel.

**File Type Filter** — Checkboxes for filtering by media MIME type (JPEG, PNG, PDF). Selecting a checkbox immediately reloads results showing only items with matching media. Multiple types can be selected (OR logic within the filter).

**Collection Filter** — Checkboxes for filtering by item set. Dynamically lists all item sets with item counts. Collections with no items display a "Coming Soon" indicator.

**Combined Filtering** — All three filters work together using AND logic between filter types. For example, selecting 1920–1930 AND PDF AND "Photographs" returns only PDF items in the Photographs collection dated between 1920 and 1930. Existing search parameters (fulltext search, property searches, pagination) are preserved across all filter interactions.

**Accessibility** — The date range slider includes full keyboard support (arrow keys, Home, End) and ARIA attributes for screen readers.

## Requirements

- Omeka S 4.1.1 or higher
- PHP 8.1 or higher
- jQuery (included with Omeka S)
- jQuery UI (loaded automatically by the module if not present)

## Installation

1. Download the latest release from the [releases page](https://github.com/Fisk-University/SearchFilterPlus/releases).
2. Extract the ZIP file to your Omeka S `/modules` directory.
3. Ensure the extracted folder is named exactly `SearchFilterPlus` (no version numbers or extra nesting).
4. Set file permissions:
   ```
   chmod -R 755 modules/SearchFilterPlus
   chown -R www-data:www-data modules/SearchFilterPlus
   ```
5. Log into the Omeka S admin panel, navigate to **Modules**, find "Search Filter Plus", and click **Install**.

## Configuration

After installation, navigate to **Admin → Modules → Search Filter Plus → Configure**.

Three settings are available:

| Setting | Description | Default |
|---------|-------------|---------|
| **Minimum Year** | The earliest year available on the date range slider. Must be between 1800 and 2100. | 1910 |
| **Maximum Year** | The latest year available on the date range slider. Must be between 1800 and 2100. | 1950 |
| **Date Property** | The metadata property to filter on. Select from any property in your Omeka S vocabulary. | `dcterms:date` |

The date property dropdown lists all available properties from your installed vocabularies. Choose the property that your items use for dates. For most installations, `dcterms:date` (Dublin Core Date) is correct.

## Theme Integration

SearchFilterPlus provides three view partials that you include in your theme's browse template. Each filter is independent — you can use any combination.

### Adding All Three Filters

In your theme's `view/[theme-name]/item/browse.phtml`, add a sidebar container with the filter partials:

```php
<div class="search-filters-sidebar">
    <?php
    // Date Range Filter
    echo $this->partial('date-range-filter/common/date-range-slider.phtml', [
        'minYear'   => 1910,   // Must match or fall within admin config
        'maxYear'   => 1950,   // Must match or fall within admin config
        'startYear' => 1910,   // Initial position of left handle
        'endYear'   => 1950,   // Initial position of right handle
        'property'  => 'dcterms:date'  // Must match admin config
    ]);

    // File Type Filter
    echo $this->partial('file-type-filter/common/file-type-filter.phtml');

    // Collection Filter
    echo $this->partial('collection-filter/common/collection-filter.phtml');
    ?>
</div>
```

### Using Individual Filters

Each filter works independently. To add only the file type filter:

```php
<?php echo $this->partial('file-type-filter/common/file-type-filter.phtml'); ?>
```

To add only the date range slider:

```php
<?php
echo $this->partial('date-range-filter/common/date-range-slider.phtml', [
    'minYear'   => 1800,
    'maxYear'   => 2025,
    'startYear' => 1800,
    'endYear'   => 2025,
    'property'  => 'dcterms:date'
]);
?>
```

### Showing Active Filters

To display a removable indicator when a date filter is active, include the active filters partial:

```php
<?php
$query = $this->params()->fromQuery();
if (isset($query['date_start']) && isset($query['date_end'])):
    echo $this->partial('date-range-filter/common/active-filters.phtml', [
        'dateStart' => $query['date_start'],
        'dateEnd'   => $query['date_end'],
    ]);
endif;
?>
```

## How It Works

### Query Parameters

Each filter adds query parameters to the browse URL:

| Filter | Parameters | Example |
|--------|-----------|---------|
| Date Range | `date_start`, `date_end` | `?date_start=1920&date_end=1935` |
| File Type | `file_type[]` | `?file_type[]=image/jpeg&file_type[]=application/pdf` |
| Collection | `collection[]` | `?collection[]=5&collection[]=12` |

All parameters can be combined: `?date_start=1920&date_end=1935&file_type[]=application/pdf&collection[]=5`

### Filter Logic

- **Between filter types**: AND — an item must match all active filters
- **Within file type filter**: OR — an item matches if it has any of the selected media types
- **Within collection filter**: OR — an item matches if it belongs to any of the selected item sets
- **Search preservation**: All existing query parameters (fulltext search, property search, sort, page) are preserved as hidden form fields when any filter is submitted

### Event Handling

The module attaches to the `api.search.query` event on the `ItemAdapter`. When a browse request includes filter parameters, the module modifies the Doctrine QueryBuilder to add the appropriate JOINs and WHERE clauses:

- **Date filter**: LEFT JOINs the `values` table filtered by the configured property ID, then applies a BETWEEN condition on the year value
- **File type filter**: INNER JOINs the `media` table and filters by `media_type` using OR conditions, with GROUP BY to prevent duplicate items
- **Collection filter**: INNER JOINs the `itemSets` relationship and filters by item set ID using OR conditions, with GROUP BY to prevent duplicates

## File Structure

```
SearchFilterPlus/
├── Module.php                          # Main module class, event listeners, config form handling
├── config/
│   ├── module.ini                      # Module metadata (name, version, author, compatibility)
│   └── module.config.php               # Laminas MVC configuration (routes, services, forms)
├── src/
│   ├── DateRangeHandler.php            # Date range query builder with YYYY and YYYY/YYYY support
│   ├── Form/
│   │   └── ConfigForm.php             # Admin configuration form (min/max year, date property)
│   ├── Controller/
│   │   └── Admin/
│   │       └── IndexController.php    # Admin controller
│   └── Service/
│       ├── DateRangeHandlerFactory.php
│       └── Form/
│           └── ConfigFormFactory.php
├── asset/
│   └── js/
│       ├── date-range-slider.js        # jQuery UI slider initialization with keyboard support
│       └── auto-submit.js              # Form auto-submission on slider change
└── view/
    ├── date-range-filter/
    │   ├── admin/
    │   │   └── config-form.phtml       # Admin configuration form template
    │   └── common/
    │       ├── date-range-slider.phtml # Date range slider template
    │       └── active-filters.phtml    # Active filter indicator with remove link
    ├── file-type-filter/
    │   └── common/
    │       └── file-type-filter.phtml  # File type checkbox template
    └── collection-filter/
        └── common/
            └── collection-filter.phtml # Collection checkbox template
```

## Supported Date Formats

The date range filter handles two formats in the date property value:

| Format | Example | How It Matches |
|--------|---------|----------------|
| Single year | `1925` | Matches if the year falls within the selected range |
| Year range | `1917/1938` | Matches if the item's date range overlaps with the selected range |

For year ranges (`YYYY/YYYY`), the module checks three overlap conditions: the item's start year falls within the selection, the item's end year falls within the selection, or the selection falls entirely within the item's date range.

## Styling

### CSS Classes

The module outputs these CSS classes for styling in your theme:

| Class | Element |
|-------|---------|
| `.date-range-filter` | Wrapper for the entire date range component |
| `.date-range-inputs` | Container for the slider form |
| `.date-range-values` | Display area showing current start/end years |
| `#date-range-slider` | The jQuery UI slider element |
| `.file-type-filter` | Wrapper for file type checkboxes |
| `.file-type-item` | Individual file type label + checkbox |
| `.file-type-label` | Text label for each file type |
| `.collection-filter` | Wrapper for collection checkboxes |
| `.collection-item` | Individual collection label + checkbox |
| `.collection-label` | Text label for each collection |
| `.collection-count` | Item count badge |
| `.collection-status` | "Coming Soon" indicator |
| `.date-range-active-filters` | Active filter display |
| `.active-filter` | Individual active filter tag |
| `.remove-filter` | Remove (×) button for active filters |

### SCSS Variables

If your theme uses SCSS, you can override these variables:

```scss
$color-primary: #653C2C;
$color-white: #FFFFFF;
$btn-primary-hover: #7A5141;
$btn-primary-pressed: #A17756;
$font-sans: 'Montserrat', sans-serif;
$font-serif: 'Sorts Mill Goudy', serif;
$weight-semibold: 600;
$spacing-sm: 8px;
$spacing-md: 16px;
$spacing-lg: 32px;
```

### Responsive Design

The filters use a mobile-first approach. On screens below 768px, the filters display inline above the results. On tablets and desktop (768px+), they appear as a sidebar.

## Performance

### Optional Database Indexes

The module works without additional indexes, but for collections with more than 10,000 items these indexes improve filter response time:

```sql
CREATE INDEX idx_media_type ON media(media_type);
CREATE INDEX idx_media_extension ON media(extension);
CREATE INDEX idx_media_item ON media(item_id);
CREATE INDEX idx_value_property ON value(property_id, value(190));
CREATE INDEX idx_item_set_mapping ON item_item_set(item_set_id, item_id);
```

### Collection Filter Performance Note

The collection filter partial queries the API for each item set to retrieve item counts. For installations with a large number of item sets (50+), consider caching or limiting the displayed collections in a customized template.

## Troubleshooting

**Filters do not appear on the browse page**
Verify the module is installed and activated in Admin → Modules. Confirm that the filter partials are included in your theme's `browse.phtml` template. Check file permissions on the `modules/SearchFilterPlus` directory.

**Date range slider does not render**
Check the browser console for JavaScript errors. The module loads jQuery UI automatically if not present — ensure your server can reach `code.jquery.com` or that jQuery UI is included in your theme. Verify that `$this->headScript()` and `$this->headLink()` are called in your theme's layout.

**Date filtering returns no results**
Confirm that items have metadata in the configured date property (default: `dcterms:date`). Check that values are in `YYYY` or `YYYY/YYYY` format. Verify the property selected in the module configuration matches what your items use.

**File type checkboxes show but don't filter**
The filter expects standard MIME types (`image/jpeg`, `image/png`, `application/pdf`). Check that your media records have `media_type` values set. Items without media will not appear in file type filtered results.

**Duplicate items in results**
This can occur if an item has multiple media of the same type. The module applies `GROUP BY` to prevent this, but if you see duplicates, verify that no other module is removing the GROUP BY clause.

**Active filters partial not showing**
The active filters partial requires `dateStart` and `dateEnd` variables to be passed explicitly. See the "Showing Active Filters" section above.

**Styling conflicts with theme**
Clear the Omeka asset cache: `rm -rf files/asset/*`. Inspect the browser developer tools for CSS specificity conflicts between your theme and the module's default styles. The module's classes are prefixed to minimize conflicts.

## Extending the Module

### Adding Custom File Types

The file type filter currently includes JPEG, PNG, and PDF. To add more types, edit `view/file-type-filter/common/file-type-filter.phtml` and add entries to the `$fileTypes` array:

```php
$fileTypes = [
    ['media_type' => 'image/jpeg', 'label' => 'jpeg/jpg'],
    ['media_type' => 'image/png', 'label' => 'png'],
    ['media_type' => 'application/pdf', 'label' => 'pdf'],
    ['media_type' => 'image/tiff', 'label' => 'tiff'],
    ['media_type' => 'audio/mpeg', 'label' => 'mp3'],
];
```

### Customizing the Slider Appearance

The slider uses jQuery UI's default theme loaded from CDN. To use a custom theme, either override the CSS in your theme's stylesheet targeting `#date-range-slider` and `.ui-slider` classes, or modify `asset/js/date-range-slider.js` to load a different jQuery UI theme URL.

## Debugging

Enable Omeka's logger in `config/local.config.php`:

```php
'logger' => ['log' => true],
```

Add debug output to `Module.php` in the `handleFilters` method:

```php
error_log('=== SearchFilterPlus Debug ===');
error_log('Query params: ' . json_encode($query));
error_log('Generated SQL: ' . $queryBuilder->getQuery()->getSQL());
```

## License

This module is published under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html) (GPL-3.0).

## Credits

Developed by the Fisk University Library team at [Fisk University](https://www.fisk.edu/academics/library/) as part of the Rosenwald Fund Collection project, funded by the [Mellon Foundation](https://mellon.org/).

**Authors:** LaTaevia Berry, Saikiran Boppana

## Support

- **Issues & feature requests:** [GitHub Issues](https://github.com/Fisk-University/SearchFilterPlus/issues)
- **Source code:** [GitHub Repository](https://github.com/Fisk-University/SearchFilterPlus)
