<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Attribute-Feature Connector' mod='attributefeatureconnector'}
        </div>
        
        <div class="alert alert-info">
            {l s='This module allows you to automatically assign features to products based on their attributes.' mod='attributefeatureconnector'}
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
                        <select name="selected_attributes[]" class="form-control" multiple="multiple" style="height: 250px;">
                            {foreach $attribute_options as $attribute}
                                <option value="{$attribute.id}" {if in_array($attribute.id, $selected_attributes)}selected="selected"{/if}>{$attribute.name}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Hold Ctrl/Cmd to select multiple attributes' mod='attributefeatureconnector'}</p>
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
                        <select name="id_feature_value" class="form-control">
                            <option value="">{l s='-- Select Feature Value --' mod='attributefeatureconnector'}</option>
                            {foreach $feature_options as $feature}
                                <option value="{$feature.id}">{$feature.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="selected_attributes[]" class="form-control" multiple="multiple" style="height: 250px;">
                            {foreach $attribute_options as $attribute}
                                <option value="{$attribute.id}">{$attribute.name}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Hold Ctrl/Cmd to select multiple attributes' mod='attributefeatureconnector'}</p>
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
    
    {if !empty($mappings)}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-list"></i> {l s='Current Mappings' mod='attributefeatureconnector'}
            </div>
            
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
                                    <a href="{$edit_url}&edit_mapping={$mapping.id_mapping}" class="btn btn-default btn-action">
                                        <i class="icon-pencil"></i> {l s='Edit' mod='attributefeatureconnector'}
                                    </a>
                                    <a href="{$delete_url}&id_mapping={$mapping.id_mapping}" class="btn btn-default btn-action" onclick="return confirm('{l s='Are you sure?' mod='attributefeatureconnector'}');">
                                        <i class="icon-trash"></i> {l s='Delete' mod='attributefeatureconnector'}
                                    </a>
                                    <a href="{$generate_mapping_url}{$mapping.id_mapping}" class="btn btn-success btn-action">
                                        <i class="icon-refresh"></i> {l s='Generate Features' mod='attributefeatureconnector'}
                                    </a>
                                    <a href="{$undo_mapping_url}{$mapping.id_mapping}" class="btn btn-warning btn-action" onclick="return confirm('{l s='Are you sure you want to remove these features from products?' mod='attributefeatureconnector'}');">
                                        <i class="icon-undo"></i> {l s='Undo Mapping' mod='attributefeatureconnector'}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            
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
                <a href="{$generate_url}" class="btn btn-primary">
                    <i class="icon-refresh"></i> {l s='Generate ALL Features' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
    {/if}
</div>