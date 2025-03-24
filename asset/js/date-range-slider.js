/**
 * Initialize the date range slider
 * @param {Object} options Configuration options
 */
function initDateRangeSlider(options) {
    const minYear = options.minYear || 1910;
    const maxYear = options.maxYear || 1950;
    const startYear = options.startYear || minYear;
    const endYear = options.endYear || maxYear;
    const autoSubmit = options.autoSubmit || false;
    
    // Load jQuery UI if not already loaded
    if (typeof jQuery.ui === 'undefined') {
        loadJQueryUI(() => {
            setupSlider(minYear, maxYear, startYear, endYear, autoSubmit);
        });
    } else {
        setupSlider(minYear, maxYear, startYear, endYear, autoSubmit);
    }
}

/**
 * Load jQuery UI if not already loaded
 * @param {Function} callback Function to call when loaded
 */
function loadJQueryUI(callback) {
    // Load CSS
    if (!document.getElementById('jquery-ui-css')) {
        const cssLink = document.createElement('link');
        cssLink.id = 'jquery-ui-css';
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css';
        document.head.appendChild(cssLink);
    }
    
    // Load JS
    if (typeof jQuery.ui === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    } else {
        callback();
    }
}

/**
 * Set up the slider with jQuery UI
 */
function setupSlider(minYear, maxYear, startYear, endYear, autoSubmit) {
    jQuery("#date-range-slider").slider({
        range: true,
        min: minYear,
        max: maxYear,
        values: [startYear, endYear],
        slide: function(event, ui) {
            updateDateDisplay(ui.values[0], ui.values[1]);
        },
        change: function(event, ui) {
            if (autoSubmit && !event.originalEvent) {
                return; // Don't auto-submit when programmatically setting values
            }
            
            updateDateDisplay(ui.values[0], ui.values[1]);
            
            if (autoSubmit && event.originalEvent) {
                // Only auto-submit if the change was from a user interaction
                jQuery("#date-range-form").submit();
            }
        }
    });
    
    // Initialize display
    updateDateDisplay(startYear, endYear);
    
    // Handle reset button
    jQuery(".reset-date-range").on('click', function() {
        jQuery("#date-range-slider").slider('values', [minYear, maxYear]);
        updateDateDisplay(minYear, maxYear);
        
        if (autoSubmit) {
            jQuery("#date-range-form").submit();
        }
    });
    
    // Add keyboard accessibility
    addKeyboardSupport();
}

/**
 * Update the date display and hidden inputs
 */
function updateDateDisplay(start, end) {
    jQuery("#date-start-display").text(start);
    jQuery("#date-end-display").text(end);
    jQuery("#date-start").val(start);
    jQuery("#date-end").val(end);
}
/**
 * Add keyboard accessibility to the slider
 */
function addKeyboardSupport() {
    const slider = document.getElementById('date-range-slider');
    const handles = slider.querySelectorAll('.ui-slider-handle');
    
    // Add tabindex and ARIA attributes
    handles.forEach((handle, index) => {
        handle.setAttribute('tabindex', '0');
        handle.setAttribute('role', 'slider');
        handle.setAttribute('aria-valuemin', jQuery("#date-range-slider").slider('option', 'min'));
        handle.setAttribute('aria-valuemax', jQuery("#date-range-slider").slider('option', 'max'));
        handle.setAttribute('aria-label', index === 0 ? 'Start year' : 'End year');
        
        // Update ARIA value when slider changes
        jQuery("#date-range-slider").on('slide', function(event, ui) {
            handle.setAttribute('aria-valuenow', ui.values[index]);
        });
        
        // Handle keyboard events
        handle.addEventListener('keydown', function(e) {
            const values = jQuery("#date-range-slider").slider('values');
            let newValue = values[index];
            
            switch(e.key) {
                case 'ArrowLeft':
                case 'ArrowDown':
                    newValue = Math.max(newValue - 1, index === 0 ? 
                        jQuery("#date-range-slider").slider('option', 'min') : 
                        values[0]);
                    e.preventDefault();
                    break;
                case 'ArrowRight':
                case 'ArrowUp':
                    newValue = Math.min(newValue + 1, index === 1 ? 
                        jQuery("#date-range-slider").slider('option', 'max') : 
                        values[1]);
                    e.preventDefault();
                    break;
                case 'Home':
                    newValue = index === 0 ? 
                        jQuery("#date-range-slider").slider('option', 'min') : 
                        values[0];
                    e.preventDefault();
                    break;
                case 'End':
                    newValue = index === 1 ? 
                        jQuery("#date-range-slider").slider('option', 'max') : 
                        values[1];
                    e.preventDefault();
                    break;
                default:
                    return;
            }
            
            // Update slider values
            values[index] = newValue;
            jQuery("#date-range-slider").slider('values', values);
            updateDateDisplay(values[0], values[1]);
        });
    });
}