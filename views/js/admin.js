$(document).ready(function() {
    // Enable select2 for better multiple selection
    if ($.fn.select2) {
        $('select[name="selected_attributes[]"]').select2({
            placeholder: "Select attributes",
            allowClear: true
        });
        
        $('select[name="id_feature_value"]').select2({
            placeholder: "Select a feature value",
            allowClear: true
        });
    }
    
    // Handle edit form selection highlighting
    $('#edit_mapping_form select[multiple]').each(function() {
        const selected = $(this).data('selected');
        if (selected) {
            $(this).val(selected.split(','));
        }
    });
    
    // Client-side attribute filtering for edit form
    $('#attribute_filter').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('select[name="selected_attributes[]"] option').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Handle tabs
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Auto-focus search boxes
    if ($('#feature_search').length) {
        $('#feature_search').focus();
    }
    
    // Show confirmation when running batch processes
    $('.batch-process').on('click', function(e) {
        if (!confirm('This operation may take some time. Are you sure you want to continue?')) {
            e.preventDefault();
        }
    });
    
    // Preview loading effect
    $('.preview-btn').on('click', function() {
        var $btn = $(this);
        $btn.html('<i class="icon-spinner icon-spin"></i> Loading preview...');
        $btn.prop('disabled', true);
        // The actual redirection happens naturally through the href
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

// Real-time filter for select elements
function filterSelectOptions(selectElement, filterValue) {
    var options = $(selectElement).find('option');
    var filterText = filterValue.toLowerCase();
    
    options.each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(filterText) > -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}