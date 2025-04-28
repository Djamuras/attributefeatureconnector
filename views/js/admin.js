$(document).ready(function() {
    // Enable select2 for better multiple selection
    if ($.fn.select2) {
        $('select[name="selected_attributes[]"]').select2();
    }
    
    // Handle edit form selection highlighting
    $('#edit_mapping_form select[multiple]').each(function() {
        const selected = $(this).data('selected');
        if (selected) {
            $(this).val(selected.split(','));
        }
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