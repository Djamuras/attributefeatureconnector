$(document).ready(function() {
    // Enable select2 for better multiple selection
    if ($.fn.select2) {
        $('select[name="selected_attributes[]"]').select2();
    }
});