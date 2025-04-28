<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Attribute-Feature Connector' mod='attributefeatureconnector'}
            <span class="panel-heading-action">
                <a href="{$analytics_url}" class="btn btn-default">
                    <i class="icon-bar-chart"></i> {l s='Analytics Dashboard' mod='attributefeatureconnector'}
                </a>
            </span>
        </div>
        
        <div class="alert alert-info">
            {l s='This module allows you to automatically assign features to products based on their attributes.' mod='attributefeatureconnector'}
            <p class="help-block">{l s='If you need help please contact developer amurdato@gmail.com' mod='attributefeatureconnector'}</p>
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
                    <label class="control-label col-lg-3">{l s='Category' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="id_category" class="form-control">
                            {foreach $categories as $category}
                                <option value="{$category.id_category}" {if isset($mapping_to_edit.id_category) && $mapping_to_edit.id_category == $category.id_category}selected="selected"{/if}>{$category.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="selected_attributes[]" id="edit_attributes_select" class="form-control" multiple="multiple" style="height: 250px;">
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
                        <select name="id_feature_value" id="feature_value_select" class="form-control">
                            <option value="">{l s='-- Select Feature Value --' mod='attributefeatureconnector'}</option>
                            {foreach $feature_options as $feature}
                                <option value="{$feature.id}">{$feature.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Category' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="id_category" class="form-control">
                            {foreach $categories as $category}
                                <option value="{$category.id_category}">{$category.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Select Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <select name="selected_attributes[]" id="attributes_select" class="form-control" multiple="multiple" style="height: 250px;">
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
                <span class="panel-heading-action">
                    <a href="{$manage_categories_url}" class="btn btn-default">
                        <i class="icon-folder-open"></i> {l s='Manage Categories' mod='attributefeatureconnector'}
                    </a>
                </span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <form id="filter_form" class="form-inline" method="get" action="{$smarty.server.REQUEST_URI|regex_replace:'/(&page=\d+)?(&category_filter=\d+)?$/':''}">
                        <input type="hidden" name="controller" value="AdminAttributeFeatureConnector">
                        <div class="form-group">
                            <label class="control-label">{l s='Filter by Category:' mod='attributefeatureconnector'}</label>
                            <div class="input-group">
                                <select name="category_filter" class="form-control">
                                    <option value="0"{if $selected_category == 0} selected="selected"{/if}>{l s='All Categories' mod='attributefeatureconnector'}</option>
                                    {foreach $categories as $category}
                                        <option value="{$category.id_category}"{if $selected_category == $category.id_category} selected="selected"{/if}>{$category.name} ({$category.mappings_count})</option>
                                    {/foreach}
                                </select>
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-default">
                                        <i class="icon-filter"></i> {l s='Filter' mod='attributefeatureconnector'}
                                    </button>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Feature' mod='attributefeatureconnector'}</th>
                            <th>{l s='Feature Value' mod='attributefeatureconnector'}</th>
                            <th>{l s='Category' mod='attributefeatureconnector'}</th>
                            <th>{l s='Linked Attributes' mod='attributefeatureconnector'}</th>
                            <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mappings as $mapping}
                            <tr>
                                <td>{$mapping.feature_name}</td>
                                <td>{$mapping.value}</td>
                                <td><span class="badge">{$mapping.category_name}</span></td>
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
                            <a href="#doc-categories" aria-controls="categories" role="tab" data-toggle="tab">{$documentation.categories.title}</a>
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
                        
                        <div role="tabpanel" class="tab-pane" id="doc-categories">
                            <div class="panel">
                                <div class="panel-body">
                                    <p>{$documentation.categories.content}</p>
                                    <h4>{l s='Tips:' mod='attributefeatureconnector'}</h4>
                                    <ul>
                                        {foreach from=$documentation.categories.tips item=tip}
                                            <li>{$tip}</li>
                                        {/foreach}
                                    </ul>
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