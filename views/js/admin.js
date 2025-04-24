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