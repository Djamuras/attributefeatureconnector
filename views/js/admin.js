$(document).ready(function() {
    initAttributePickers();

    // Enable select2 for regular single selects
    if ($.fn.select2) {
        $('#feature_value_select, #category_select').select2();
    }
    
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
    
});

function initAttributePickers() {
    $('.afc-attribute-picker').each(function() {
        var $picker = $(this);
        var inputName = $picker.data('input-name') || 'selected_attributes[]';
        var selected = {};

        $picker.find('.afc-attribute-option').each(function() {
            var $option = $(this);
            var id = String($option.data('id'));
            var group = String($option.data('group') || '');
            var label = String($option.data('label') || $option.text().trim());

            $option.data('id', id);
            $option.data('group', group);
            $option.data('label', label);

            if ($option.data('selected') == 1) {
                selected[id] = {
                    id: id,
                    label: label,
                    group: group
                };
            }
        });

        populateAttributeGroups($picker);
        renderAttributePicker($picker, selected, inputName);

        $picker.on('input change', '.afc-picker-search, .afc-picker-group', function() {
            renderAttributePicker($picker, selected, inputName);
        });

        $picker.on('click', '.afc-attribute-option', function() {
            var $option = $(this);
            var id = String($option.data('id'));
            selected[id] = {
                id: id,
                label: String($option.data('label')),
                group: String($option.data('group'))
            };
            renderAttributePicker($picker, selected, inputName);
        });

        $picker.on('click', '.afc-selected-remove', function() {
            var id = String($(this).closest('.afc-selected-item').data('id'));
            delete selected[id];
            renderAttributePicker($picker, selected, inputName);
        });

        $picker.on('click', '.afc-picker-clear', function() {
            selected = {};
            renderAttributePicker($picker, selected, inputName);
        });

        $picker.on('click', '.afc-picker-add-visible', function() {
            $picker.find('.afc-attribute-option:visible').each(function() {
                var $option = $(this);
                var id = String($option.data('id'));
                selected[id] = {
                    id: id,
                    label: String($option.data('label')),
                    group: String($option.data('group'))
                };
            });
            renderAttributePicker($picker, selected, inputName);
        });
    });
}

function populateAttributeGroups($picker) {
    var groups = {};
    var $groupSelect = $picker.find('.afc-picker-group');

    $picker.find('.afc-attribute-option').each(function() {
        var group = String($(this).data('group') || '');
        if (group) {
            groups[group] = true;
        }
    });

    Object.keys(groups).sort().forEach(function(group) {
        $('<option>').val(group).text(group).appendTo($groupSelect);
    });
}

function renderAttributePicker($picker, selected, inputName) {
    var query = String($picker.find('.afc-picker-search').val() || '').toLowerCase();
    var groupFilter = String($picker.find('.afc-picker-group').val() || '');
    var availableCount = 0;
    var selectedIds = Object.keys(selected);
    var $selectedList = $picker.find('.afc-picker-selected');
    var $inputs = $picker.find('.afc-picker-inputs');

    $picker.find('.afc-attribute-option').each(function() {
        var $option = $(this);
        var id = String($option.data('id'));
        var group = String($option.data('group') || '');
        var label = String($option.data('label') || '');
        var matchesGroup = !groupFilter || group === groupFilter;
        var matchesSearch = !query || (label + ' ' + group).toLowerCase().indexOf(query) !== -1;
        var isSelected = !!selected[id];
        var isVisible = matchesGroup && matchesSearch && !isSelected;

        $option.toggle(isVisible);
        if (isVisible) {
            availableCount++;
        }
    });

    $selectedList.empty();
    $inputs.empty();

    selectedIds.sort(function(a, b) {
        var left = selected[a].group + selected[a].label;
        var right = selected[b].group + selected[b].label;
        return left.localeCompare(right);
    }).forEach(function(id) {
        var item = selected[id];
        $('<input>').attr({
            type: 'hidden',
            name: inputName,
            value: item.id
        }).appendTo($inputs);

        var $row = $('<div>').addClass('afc-selected-item').attr('data-id', item.id);
        $('<span>').addClass('afc-selected-text')
            .append($('<strong>').text(item.label))
            .append($('<small>').text(item.group))
            .appendTo($row);
        $('<button>').attr('type', 'button')
            .addClass('btn btn-default btn-xs afc-selected-remove')
            .html('<i class="icon-remove"></i>')
            .appendTo($row);
        $row.appendTo($selectedList);
    });

    $picker.find('.afc-selected-count').text(selectedIds.length);
    $picker.find('.afc-picker-empty-available').toggle(availableCount === 0);
    $picker.find('.afc-picker-empty-selected').toggle(selectedIds.length === 0);
    $picker.find('.afc-picker-clear').prop('disabled', selectedIds.length === 0);
}

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
