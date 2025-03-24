/**
 * This script adds auto-submit functionality to the date range filter
 */
(function($) {
    $(document).ready(function() {
        // Auto-submit form when date range changes
        $('#date-range-slider').on('slidechange', function(event, ui) {
            // Only submit if the change was triggered by user interaction
            if (event.originalEvent) {
                $('#date-range-form').submit();
            }
        });
        
        // Handle form submission
        $('#date-range-form').on('submit', function() {
            // Update the hidden inputs before submission
            var values = $('#date-range-slider').slider('values');
            $('#date-start').val(values[0]);
            $('#date-end').val(values[1]);
        });
    });
})(jQuery);