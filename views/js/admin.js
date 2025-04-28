$(document).ready(function() {
    // Enable select2 for better multiple selection
    if ($.fn.select2) {
        $('select[name="selected_attributes[]"]').select2();
        $('#feature_value_select, #category_select').select2();
    }
    
    // Handle edit form selection highlighting
    $('#edit_mapping_form select[multiple]').each(function() {
        const selected = $(this).data('selected');
        if (selected) {
            $(this).val(selected.split(','));
        }
    });
    
    // Add tooltips to action buttons
    $('.btn-action').tooltip({
        placement: 'top',
        container: 'body'
    });
    
    // Handle batch size changes validation
    $('#batch_form').on('submit', function(e) {
        const batchSize = parseInt($('input[name="batch_size"]').val());
        if (isNaN(batchSize) || batchSize < 10) {
            e.preventDefault();
            alert('Batch size must be at least 10');
            return false;
        }
        return true;
    });
    
    // Documentation tabs
    $('#documentationModal').on('shown.bs.modal', function() {
        // Ensure first tab is active when modal opens
        $('#documentationModal .nav-tabs a:first').tab('show');
    });
    
    // Auto-submit category filter form on change
    $('select[name="category_filter"]').on('change', function() {
        $('#filter_form').submit();
    });
    
    // Removed: Initialize tooltips for suggestion items
    // $('.suggestion-item [data-toggle="tooltip"]').tooltip();
    
    // Run CRON job animation
    $('.run-cron-now').hover(
        function() { $(this).find('i').addClass('icon-spin'); },
        function() { $(this).find('i').removeClass('icon-spin'); }
    );
    
    // Analytics dashboard interactions
    if ($('#performanceChart').length > 0) {
        // Handled by Chart.js in the template
    }
    
    // Removed: Process suggestion confirmation
    // $('.process-suggestion').on('click', function(e) { ... });
    
    // Removed: Ignore suggestion confirmation
    // $('.ignore-suggestion').on('click', function(e) { ... });
    
    // Conflict resolution confirmation
    $('.resolve-conflict').on('click', function(e) {
        if (!confirm('This will remove the attribute from other conflicting mappings. Continue?')) {
            e.preventDefault();
        }
    });
    
    // Auto-filter submit on category filter change
    $('#category_filter').on('change', function() {
        $('#filter_form').submit();
    });
});

// Function to copy text to clipboard
function copyToClipboard(element) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(element).val()).select();
    document.execCommand("copy");
    $temp.remove();
    
    // Show a brief success message
    showCopySuccess();
}

// Display a temporary success message
function showCopySuccess() {
    var $message = $('<div class="alert alert-success copy-alert" style="position: fixed; top: 10%; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 10px 20px;">Copied to clipboard!</div>');
    $('body').append($message);
    
    setTimeout(function() {
        $message.fadeOut(300, function() {
            $(this).remove();
        });
    }, 2000);
}