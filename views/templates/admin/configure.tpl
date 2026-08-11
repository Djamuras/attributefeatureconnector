<div class="bootstrap">

    <div class="afc-module-header">
        <div class="afc-module-header-title">
            <i class="icon-cogs"></i> {l s='Attribute-Feature Connector' mod='attributefeatureconnector'}
        </div>
        <div class="afc-module-header-nav">
            <a href="{$category_mapping_url}" class="btn btn-sm">
                <i class="icon-tags"></i> {l s='Category Mapping' mod='attributefeatureconnector'}
            </a>
            <a href="{$analytics_url}" class="btn btn-sm">
                <i class="icon-bar-chart"></i> {l s='Analytics' mod='attributefeatureconnector'}
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Attribute Mapping' mod='attributefeatureconnector'}
        </div>

        <div class="alert alert-info">
            {l s='This module allows you to automatically assign features to products based on their attributes or categories.' mod='attributefeatureconnector'}
            <p class="help-block">
                {l s='Use the Attribute Mapping tab (current) to assign features based on product attributes, or the Category Mapping tab to assign features based on product categories.' mod='attributefeatureconnector'}<br>
                {l s='If you need help please contact developer amurdato@gmail.com' mod='attributefeatureconnector'}
            </p>
            <button type="button" class="btn btn-info btn-xs pull-right" data-toggle="modal" data-target="#documentationModal">
                <i class="icon-book"></i> {l s='Documentation' mod='attributefeatureconnector'}
            </button>
        </div>
        
        {if isset($confirmation)}
            <div class="alert alert-success">{$confirmation}</div>
        {/if}
        
        {if $mapping_to_edit}
            <form id="edit_mapping_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
                <div class="panel-heading">
                    <i class="icon-pencil"></i> {l s='Edit Mapping' mod='attributefeatureconnector'}: {$mapping_to_edit.feature_name} - {$mapping_to_edit.value}
                </div>
                <input type="hidden" name="id_mapping" value="{$mapping_to_edit.id_mapping}" />
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <div class="afc-attribute-picker" data-input-name="selected_attributes[]">
                            <div class="afc-picker-toolbar">
                                <select class="form-control afc-picker-group">
                                    <option value="">{l s='All attribute groups' mod='attributefeatureconnector'}</option>
                                </select>
                                <input type="text" class="form-control afc-picker-search" placeholder="{l s='Search attributes...' mod='attributefeatureconnector'}">
                            </div>
                            <div class="afc-picker-grid">
                                <div class="afc-picker-column">
                                    <div class="afc-picker-head">
                                        <strong>{l s='Available attributes' mod='attributefeatureconnector'}</strong>
                                        <button type="button" class="btn btn-default btn-xs afc-picker-add-visible">
                                            <i class="icon-plus"></i> {l s='Add visible' mod='attributefeatureconnector'}
                                        </button>
                                    </div>
                                    <div class="afc-picker-list afc-picker-available">
                                        {foreach $attribute_options as $attribute}
                                            <button type="button"
                                                    class="afc-attribute-option"
                                                    data-id="{$attribute.id|intval}"
                                                    data-label="{$attribute.attribute_name|escape:'html':'UTF-8'}"
                                                    data-group="{$attribute.group_name|escape:'html':'UTF-8'}"
                                                    data-selected="{if in_array($attribute.id, $selected_attributes)}1{else}0{/if}">
                                                <span class="afc-attribute-name">{$attribute.attribute_name|escape:'html':'UTF-8'}</span>
                                                <span class="afc-attribute-group">{$attribute.group_name|escape:'html':'UTF-8'}</span>
                                            </button>
                                        {/foreach}
                                    </div>
                                    <div class="afc-picker-empty afc-picker-empty-available">{l s='No attributes found' mod='attributefeatureconnector'}</div>
                                </div>
                                <div class="afc-picker-column afc-picker-column-selected">
                                    <div class="afc-picker-head">
                                        <strong>{l s='Selected attributes' mod='attributefeatureconnector'} <span class="badge afc-selected-count">0</span></strong>
                                        <button type="button" class="btn btn-default btn-xs afc-picker-clear">
                                            <i class="icon-trash"></i> {l s='Clear' mod='attributefeatureconnector'}
                                        </button>
                                    </div>
                                    <div class="afc-picker-list afc-picker-selected"></div>
                                    <div class="afc-picker-empty afc-picker-empty-selected">{l s='Nothing selected yet' mod='attributefeatureconnector'}</div>
                                </div>
                            </div>
                            <div class="afc-picker-inputs"></div>
                        </div>
                        <p class="help-block">{l s='Filter by attribute group, add the values you need, then review the selected list on the right.' mod='attributefeatureconnector'}</p>
                    </div>
                </div>
                
                <div class="panel-footer">
                    <a href="{$cancel_url}" class="btn btn-default">
                        <i class="process-icon-cancel"></i> {l s='Cancel' mod='attributefeatureconnector'}
                    </a>
                    <button type="submit" name="submitEditMapping" class="btn btn-default pull-right">
                        <i class="process-icon-save"></i> {l s='Update Mapping' mod='attributefeatureconnector'}
                    </button>
                </div>
            </form>
        {else}
            <form id="mapping_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Feature Value' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="id_feature_value" id="feature_value_select" class="form-control">
                            <option value="">{l s='-- Select Feature Value --' mod='attributefeatureconnector'}</option>
                            {foreach $feature_options as $feature}
                                <option value="{$feature.id}">{$feature.name}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Only unmapped feature values are shown in this list.' mod='attributefeatureconnector'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <div class="afc-attribute-picker" data-input-name="selected_attributes[]">
                            <div class="afc-picker-toolbar">
                                <select class="form-control afc-picker-group">
                                    <option value="">{l s='All attribute groups' mod='attributefeatureconnector'}</option>
                                </select>
                                <input type="text" class="form-control afc-picker-search" placeholder="{l s='Search attributes...' mod='attributefeatureconnector'}">
                            </div>
                            <div class="afc-picker-grid">
                                <div class="afc-picker-column">
                                    <div class="afc-picker-head">
                                        <strong>{l s='Available attributes' mod='attributefeatureconnector'}</strong>
                                        <button type="button" class="btn btn-default btn-xs afc-picker-add-visible">
                                            <i class="icon-plus"></i> {l s='Add visible' mod='attributefeatureconnector'}
                                        </button>
                                    </div>
                                    <div class="afc-picker-list afc-picker-available">
                                        {foreach $attribute_options as $attribute}
                                            <button type="button"
                                                    class="afc-attribute-option"
                                                    data-id="{$attribute.id|intval}"
                                                    data-label="{$attribute.attribute_name|escape:'html':'UTF-8'}"
                                                    data-group="{$attribute.group_name|escape:'html':'UTF-8'}"
                                                    data-selected="0">
                                                <span class="afc-attribute-name">{$attribute.attribute_name|escape:'html':'UTF-8'}</span>
                                                <span class="afc-attribute-group">{$attribute.group_name|escape:'html':'UTF-8'}</span>
                                            </button>
                                        {/foreach}
                                    </div>
                                    <div class="afc-picker-empty afc-picker-empty-available">{l s='No attributes found' mod='attributefeatureconnector'}</div>
                                </div>
                                <div class="afc-picker-column afc-picker-column-selected">
                                    <div class="afc-picker-head">
                                        <strong>{l s='Selected attributes' mod='attributefeatureconnector'} <span class="badge afc-selected-count">0</span></strong>
                                        <button type="button" class="btn btn-default btn-xs afc-picker-clear">
                                            <i class="icon-trash"></i> {l s='Clear' mod='attributefeatureconnector'}
                                        </button>
                                    </div>
                                    <div class="afc-picker-list afc-picker-selected"></div>
                                    <div class="afc-picker-empty afc-picker-empty-selected">{l s='Nothing selected yet' mod='attributefeatureconnector'}</div>
                                </div>
                            </div>
                            <div class="afc-picker-inputs"></div>
                        </div>
                        <p class="help-block">{l s='Filter by attribute group, add the values you need, then review the selected list on the right.' mod='attributefeatureconnector'}</p>
                    </div>
                </div>
                
                <div class="panel-footer">
                    <button type="submit" name="submitMapping" class="btn btn-default pull-right">
                        <i class="process-icon-save"></i> {l s='Save Mapping' mod='attributefeatureconnector'}
                    </button>
                </div>
            </form>
        {/if}
    </div>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-exchange"></i> {l s='Import / Export Attribute Mappings' mod='attributefeatureconnector'}
        </div>

        <div class="alert alert-info">
            {l s='Export your attribute mappings before reinstalling the module, then import them back after installation.' mod='attributefeatureconnector'}
            <p class="help-block">{l s='Import works by feature, feature value, attribute group, and attribute names, so it does not depend on old database IDs.' mod='attributefeatureconnector'}</p>
        </div>

        <div class="afc-import-export-grid">
            <div class="afc-import-export-box">
                <h4>{l s='Export mappings' mod='attributefeatureconnector'}</h4>
                <p>{l s='Download all current Attribute Mapping rules as a JSON file.' mod='attributefeatureconnector'}</p>
                <a href="{$export_url}" class="btn btn-primary">
                    <i class="icon-download"></i> {l s='Export JSON' mod='attributefeatureconnector'}
                </a>
            </div>

            <div class="afc-import-export-box">
                <h4>{l s='Import mappings' mod='attributefeatureconnector'}</h4>
                <p>{l s='Upload an exported JSON file to validate it first. Existing mappings are kept and missing attribute links are added only after confirmation.' mod='attributefeatureconnector'}</p>
                <form action="{$smarty.server.REQUEST_URI}" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="file" name="mapping_import_file" class="form-control" accept="application/json,.json" required>
                    </div>
                    <button type="submit" name="preview_import_mappings" class="btn btn-default">
                        <i class="icon-search"></i> {l s='Preview Import' mod='attributefeatureconnector'}
                    </button>
                </form>
            </div>
        </div>

        {if !empty($import_preview)}
            <div class="afc-import-preview">
                <div class="afc-import-summary">
                    <span><strong>{$import_preview.summary.create|intval}</strong> {l s='new mappings' mod='attributefeatureconnector'}</span>
                    <span><strong>{$import_preview.summary.update|intval}</strong> {l s='existing mappings' mod='attributefeatureconnector'}</span>
                    <span><strong>{$import_preview.summary.link|intval}</strong> {l s='attributes to link' mod='attributefeatureconnector'}</span>
                    <span><strong>{$import_preview.summary.duplicate|intval}</strong> {l s='duplicates' mod='attributefeatureconnector'}</span>
                    <span><strong>{$import_preview.summary.skipped|intval}</strong> {l s='skipped' mod='attributefeatureconnector'}</span>
                </div>

                <div class="table-responsive">
                    <table class="table afc-import-report-table">
                        <thead>
                            <tr>
                                <th>{l s='Feature' mod='attributefeatureconnector'}</th>
                                <th>{l s='Feature Value' mod='attributefeatureconnector'}</th>
                                <th>{l s='Attribute Group' mod='attributefeatureconnector'}</th>
                                <th>{l s='Attribute' mod='attributefeatureconnector'}</th>
                                <th>{l s='Status' mod='attributefeatureconnector'}</th>
                                <th>{l s='Message' mod='attributefeatureconnector'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$import_preview.rows item=row}
                                <tr>
                                    <td>{$row.feature|escape:'html':'UTF-8'}</td>
                                    <td>{$row.feature_value|escape:'html':'UTF-8'}</td>
                                    <td>{$row.group|escape:'html':'UTF-8'}</td>
                                    <td>{$row.attribute|escape:'html':'UTF-8'}</td>
                                    <td><span class="label label-default afc-import-status afc-import-status-{$row.status|escape:'html':'UTF-8'}">{$row.status|escape:'html':'UTF-8'}</span></td>
                                    <td>{$row.message|escape:'html':'UTF-8'}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {if $import_preview.summary.link > 0}
                    <form action="{$smarty.server.REQUEST_URI}" method="post" class="afc-import-confirm-form">
                        <input type="hidden" name="import_payload" value="{$import_payload|escape:'html':'UTF-8'}">
                        <button type="submit" name="confirm_import_mappings" class="btn btn-primary">
                            <i class="icon-check"></i> {l s='Confirm Import' mod='attributefeatureconnector'}
                        </button>
                    </form>
                {/if}
            </div>
        {/if}
    </div>
    
    <!-- Batch Processing Panel -->
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cog"></i> {l s='Batch Processing Settings' mod='attributefeatureconnector'}
        </div>
        
        <div class="alert alert-info">
            {l s='Adjust batch size for large catalogs to prevent timeout issues during processing.' mod='attributefeatureconnector'}
            <span class="help-block">{l s='Smaller values are safer but slower, larger values are faster but may cause timeouts.' mod='attributefeatureconnector'}</span>
        </div>
        
        <form id="batch_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Batch Size' mod='attributefeatureconnector'}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="number" name="batch_size" class="form-control" value="{$batch_size}" min="10" step="10">
                        <span class="input-group-btn">
                            <button type="submit" name="update_batch_size" class="btn btn-default">
                                <i class="icon-refresh"></i> {l s='Update' mod='attributefeatureconnector'}
                            </button>
                        </span>
                    </div>
                    <p class="help-block">{l s='Recommended: 50 for shared hosting, 100-200 for dedicated servers' mod='attributefeatureconnector'}</p>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Real-Time Processing Panel -->
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-bolt"></i> {l s='Real-Time Processing' mod='attributefeatureconnector'}
        </div>

        <div class="alert alert-info">
            {l s='When enabled, features are automatically assigned to products as soon as they are saved or their attributes/category change — no cron or manual generation needed.' mod='attributefeatureconnector'}
            <p class="help-block">{l s='Disable this on large catalogs where every product save triggers heavy processing. Use the CRON job instead.' mod='attributefeatureconnector'}</p>
        </div>

        <form action="{$update_realtime_url}" method="post">
            <div class="panel-body">
                <label class="afc-toggle-wrap">
                    <div class="afc-toggle">
                        <input type="checkbox" name="realtime_enabled" value="1" {if $realtime_enabled}checked="checked"{/if}>
                        <span class="afc-toggle-track"></span>
                    </div>
                    <div class="afc-toggle-label">
                        <strong>{l s='Enable Real-Time Processing' mod='attributefeatureconnector'}</strong>
                        <span>{l s='Automatically apply mappings when products are saved or their attributes/category change.' mod='attributefeatureconnector'}</span>
                    </div>
                </label>
            </div>
            <div class="panel-footer">
                <button type="submit" name="update_realtime" class="btn btn-primary pull-right">
                    <i class="icon-save"></i> {l s='Save' mod='attributefeatureconnector'}
                </button>
            </div>
        </form>
    </div>

    <!-- CRON Job Panel -->
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-clock-o"></i> {l s='CRON Job Configuration' mod='attributefeatureconnector'}
        </div>
        
        <div class="alert alert-info">
            {l s='Set up a CRON job to automatically generate features for all products on a scheduled basis.' mod='attributefeatureconnector'}
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='CRON Job URL' mod='attributefeatureconnector'}</label>
            <div class="col-lg-9">
                <div class="input-group">
                    <input type="text" class="form-control" id="cron_url" value="{$cron_url}" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button" onclick="copyToClipboard('#cron_url')">
                            <i class="icon-copy"></i> {l s='Copy' mod='attributefeatureconnector'}
                        </button>
                    </span>
                </div>
                <p class="help-block">
                    {l s='Add this URL to your server\'s CRON jobs to automatically generate features for all products.' mod='attributefeatureconnector'}
                </p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='CRON Token' mod='attributefeatureconnector'}</label>
            <div class="col-lg-9">
                <div class="input-group">
                    <input type="text" class="form-control" id="cron_token" value="{$cron_token}" readonly>
                    <span class="input-group-btn">
                        <form method="post" action="{$smarty.server.REQUEST_URI}" style="display:inline;">
                            <button class="btn btn-warning" type="submit" name="regenerate_cron_token" onclick="return confirm('{l s='Are you sure you want to regenerate the token? Any existing CRON jobs will need to be updated.' mod='attributefeatureconnector'}');">
                                <i class="icon-refresh"></i> {l s='Regenerate Token' mod='attributefeatureconnector'}
                            </button>
                        </form>
                    </span>
                </div>
                <p class="help-block">
                    {l s='This token is used for security. Keep it secret and include it in your CRON job URL.' mod='attributefeatureconnector'}
                </p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Example CRON Command' mod='attributefeatureconnector'}</label>
            <div class="col-lg-9">
                <div class="input-group">
                    <input type="text" class="form-control" id="cron_example" value="0 */6 * * * wget -q -O /dev/null '{$cron_url}'" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button" onclick="copyToClipboard('#cron_example')">
                            <i class="icon-copy"></i> {l s='Copy' mod='attributefeatureconnector'}
                        </button>
                    </span>
                </div>
                <p class="help-block">
                    {l s='This example runs every 6 hours. Adjust according to your needs.' mod='attributefeatureconnector'}
                </p>
            </div>
        </div>
        
        <div class="panel-footer">
            <a href="{$cron_url}" target="_blank" class="btn btn-primary" onclick="return confirm('{l s='This will execute the CRON job now. Continue?' mod='attributefeatureconnector'}');">
                <i class="icon-play"></i> {l s='Run CRON Job Now' mod='attributefeatureconnector'}
            </a>
        </div>
    </div>
    
    {if !empty($mappings)}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-list"></i> {l s='Current Mappings' mod='attributefeatureconnector'}
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Feature' mod='attributefeatureconnector'}</th>
                            <th>{l s='Feature Value' mod='attributefeatureconnector'}</th>
                            <th>{l s='Linked Attributes' mod='attributefeatureconnector'}</th>
                            <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mappings as $mapping}
                            <tr>
                                <td>{$mapping.feature_name}</td>
                                <td>{$mapping.value}</td>
                                <td>{$mapping.attributes}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{$edit_url}&edit_mapping={$mapping.id_mapping}" class="btn btn-default btn-action" title="{l s='Edit' mod='attributefeatureconnector'}">
                                            <i class="icon-pencil"></i>
                                        </a>
                                        <a href="{$delete_url}&id_mapping={$mapping.id_mapping}" class="btn btn-default btn-action" onclick="return confirm('{l s='Are you sure?' mod='attributefeatureconnector'}');" title="{l s='Delete' mod='attributefeatureconnector'}">
                                            <i class="icon-trash"></i>
                                        </a>
                                        <a href="{$preview_url}{$mapping.id_mapping}" class="btn btn-info btn-action" title="{l s='Preview Affected Products' mod='attributefeatureconnector'}">
                                            <i class="icon-eye"></i>
                                        </a>
                                        <a href="{$generate_mapping_url}{$mapping.id_mapping}" class="btn btn-success btn-action" title="{l s='Generate Features' mod='attributefeatureconnector'}">
                                            <i class="icon-refresh"></i>
                                        </a>
                                        <a href="{$undo_mapping_url}{$mapping.id_mapping}" class="btn btn-warning btn-action" onclick="return confirm('{l s='Are you sure you want to remove these features from products?' mod='attributefeatureconnector'}');" title="{l s='Undo Mapping' mod='attributefeatureconnector'}">
                                            <i class="icon-undo"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            
            {* Pagination *}
            {if $total_pages > 1}
                <div class="pagination">
                    <ul class="pagination">
                        {if isset($pagination_links.prev)}
                            <li>
                                <a href="{$pagination_links.prev}">
                                    <i class="icon-chevron-left"></i>
                                </a>
                            </li>
                        {else}
                            <li class="disabled">
                                <span><i class="icon-chevron-left"></i></span>
                            </li>
                        {/if}
                        
                        {foreach from=$pagination_links.pages key=p item=url}
                            <li {if $p == $current_page}class="active"{/if}>
                                <a href="{$url}">{$p}</a>
                            </li>
                        {/foreach}
                        
                        {if isset($pagination_links.next)}
                            <li>
                                <a href="{$pagination_links.next}">
                                    <i class="icon-chevron-right"></i>
                                </a>
                            </li>
                        {else}
                            <li class="disabled">
                                <span><i class="icon-chevron-right"></i></span>
                            </li>
                        {/if}
                    </ul>
                </div>
                <div class="pagination-info">
                    {l s='Showing %d to %d of %d mappings' sprintf=[$items_per_page * ($current_page-1) + 1, min($items_per_page * $current_page, $total_mappings), $total_mappings] mod='attributefeatureconnector'}
                </div>
            {/if}
            
            <div class="panel-footer">
                <a href="{$generate_url}" class="btn btn-primary" onclick="return confirm('{l s='This will apply all mappings to your products. Continue?' mod='attributefeatureconnector'}');">
                    <i class="icon-refresh"></i> {l s='Generate ALL Features' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
    {/if}
    
    <!-- Documentation Modal -->
    <div class="modal fade" id="documentationModal" tabindex="-1" role="dialog" aria-labelledby="documentationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="documentationModalLabel"><i class="icon-book"></i> {l s='Documentation' mod='attributefeatureconnector'}</h4>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#doc-general" aria-controls="general" role="tab" data-toggle="tab">{$documentation.general.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-mappings" aria-controls="mappings" role="tab" data-toggle="tab">{$documentation.mappings.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-category-mapping" aria-controls="category-mapping" role="tab" data-toggle="tab">{$documentation.categoryMapping.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-preview" aria-controls="preview" role="tab" data-toggle="tab">{$documentation.preview.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-batch" aria-controls="batch" role="tab" data-toggle="tab">{$documentation.batch.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-cron" aria-controls="cron" role="tab" data-toggle="tab">{$documentation.cron.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-analytics" aria-controls="analytics" role="tab" data-toggle="tab">{$documentation.analytics.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-best-practices" aria-controls="best-practices" role="tab" data-toggle="tab">{$documentation.bestPractices.title}</a>
                        </li>
                        <li role="presentation">
                            <a href="#doc-support" aria-controls="support" role="tab" data-toggle="tab">{$documentation.support.title}</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="doc-general">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.general.content}</p>
                                    <div class="alert alert-info">
                                        <p>{$documentation.general.contact}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-mappings">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.mappings.content}</p>
                                    <h4>{l s='Steps:' mod='attributefeatureconnector'}</h4>
                                    <ol>
                                        {foreach from=$documentation.mappings.steps item=step}
                                            <li>{$step}</li>
                                        {/foreach}
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-category-mapping">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.categoryMapping.content}</p>
                                    <h4>{l s='Steps:' mod='attributefeatureconnector'}</h4>
                                    <ol>
                                        {foreach from=$documentation.categoryMapping.steps item=step}
                                            <li>{$step}</li>
                                        {/foreach}
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-preview">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.preview.content}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-batch">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.batch.content}</p>
                                    <h4>{l s='Tips:' mod='attributefeatureconnector'}</h4>
                                    <ul>
                                        {foreach from=$documentation.batch.tips item=tip}
                                            <li>{$tip}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-cron">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.cron.content}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-analytics">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.analytics.content}</p>
                                    <h4>{l s='Features:' mod='attributefeatureconnector'}</h4>
                                    <ul>
                                        {foreach from=$documentation.analytics.features item=feature}
                                            <li>{$feature}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-best-practices">
                            <div class="panel">
                                <div class="panel-body">
                                    <h4>{l s='Tips:' mod='attributefeatureconnector'}</h4>
                                    <ul>
                                        {foreach from=$documentation.bestPractices.tips item=tip}
                                            <li>{$tip}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="doc-support">
                            <div class="panel">
                                <div class="panel-body">
                                    <div class="alert alert-info">
                                        <p>{$documentation.support.content}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='attributefeatureconnector'}</button>
                </div>
            </div>
        </div>
    </div>
</div>
