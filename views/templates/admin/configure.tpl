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