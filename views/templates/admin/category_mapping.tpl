<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-tags"></i> {l s='Category-Feature Mapping' mod='attributefeatureconnector'}
            <div class="panel-heading-action">
                <a href="{$attribute_connector_url}" class="btn btn-default">
                    <i class="icon-exchange"></i> {l s='Attribute Mapping' mod='attributefeatureconnector'}
                </a>
                <a href="{$analytics_url}" class="btn btn-default">
                    <i class="icon-bar-chart"></i> {l s='Analytics' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
        
        <div class="alert alert-info">
            {l s='Category-Feature mappings allow you to automatically assign features to all products in a specific category.' mod='attributefeatureconnector'}
            <p class="help-block">{l s='This is useful for assigning common features to entire product categories.' mod='attributefeatureconnector'}</p>
        </div>
        
        {if isset($confirmation)}
            <div class="alert alert-success">{$confirmation}</div>
        {/if}
        
        {if $mapping_to_edit}
            <form id="edit_mapping_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
                <div class="panel-heading">
                    <i class="icon-pencil"></i> {l s='Edit Category-Feature Mapping' mod='attributefeatureconnector'}: {$mapping_to_edit.feature_name} - {$mapping_to_edit.value}
                </div>
                <input type="hidden" name="id_mapping" value="{$mapping_to_edit.id_mapping}" />
                <input type="hidden" name="id_feature_value" value="{$mapping_to_edit.id_feature_value}" />
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Feature Value' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <p class="form-control-static"><strong>{$mapping_to_edit.feature_name} - {$mapping_to_edit.value}</strong></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Category' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="id_category" id="edit_category_select" class="form-control">
                            {foreach $category_options as $category}
                                <option value="{$category.id}" {if $mapping_to_edit.id_category == $category.id}selected="selected"{/if}>{$category.name}</option>
                            {/foreach}
                        </select>
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
            <form id="category_mapping_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
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
                    <label class="control-label col-lg-3">{l s='Select Category' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="id_category" id="category_select" class="form-control">
                            <option value="">{l s='-- Select Category --' mod='attributefeatureconnector'}</option>
                            {foreach $category_options as $category}
                                <option value="{$category.id}">{$category.name}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='All products in this category will receive the selected feature value.' mod='attributefeatureconnector'}</p>
                    </div>
                </div>
                
                <div class="panel-footer">
                    <button type="submit" name="submitCategoryMapping" class="btn btn-default pull-right">
                        <i class="process-icon-save"></i> {l s='Save Mapping' mod='attributefeatureconnector'}
                    </button>
                </div>
            </form>
        {/if}
    </div>
    
    {if !empty($mappings)}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-list"></i> {l s='Current Category-Feature Mappings' mod='attributefeatureconnector'}
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Feature' mod='attributefeatureconnector'}</th>
                            <th>{l s='Feature Value' mod='attributefeatureconnector'}</th>
                            <th>{l s='Category' mod='attributefeatureconnector'}</th>
                            <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mappings as $mapping}
                            <tr>
                                <td>{$mapping.feature_name}</td>
                                <td>{$mapping.value}</td>
                                <td>{$mapping.category_name}</td>
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
                <a href="{$generate_url}" class="btn btn-primary" onclick="return confirm('{l s='This will apply all category-feature mappings to your products. Continue?' mod='attributefeatureconnector'}');">
                    <i class="icon-refresh"></i> {l s='Generate ALL Features' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
    {/if}
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Enable select2 for better selection UI
    if ($.fn.select2) {
        $('#feature_value_select, #category_select').select2();
        $('#edit_category_select').select2();
    }
    
    // Add tooltips to action buttons
    $('.btn-action').tooltip({
        placement: 'top',
        container: 'body'
    });
});
</script>