<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Attribute-Feature Connector' mod='attributefeatureconnector'}
            
            <!-- Tab Navigation -->
            <div class="pull-right">
                <a href="{$current_url}" class="btn btn-default {if !$documentation_tab && !$preview_tab}active{/if}">
                    <i class="icon-list"></i> {l s='Mappings' mod='attributefeatureconnector'}
                </a>
                <a href="{$preview_url}" class="btn btn-default {if $preview_tab}active{/if}">
                    <i class="icon-eye"></i> {l s='Preview' mod='attributefeatureconnector'}
                </a>
                <a href="{$help_link}" class="btn btn-default {if $documentation_tab}active{/if}">
                    <i class="icon-book"></i> {l s='Documentation' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
        
        <div class="alert alert-info">
            {l s='This module allows you to automatically assign features to products based on their attributes.' mod='attributefeatureconnector'}
        </div>
        
        {if isset($confirmation)}
            <div class="alert alert-success">{$confirmation}</div>
        {/if}
        
        {if $documentation_tab}
            <!-- Documentation Tab Content -->
            <div class="documentation-content">
                <h3>{l s='How to use Attribute-Feature Connector' mod='attributefeatureconnector'}</h3>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-info-circle"></i> {l s='Overview' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <p>{l s='Attribute-Feature Connector automates the process of assigning features to products based on their attributes. This is particularly useful when:' mod='attributefeatureconnector'}</p>
                        <ul>
                            <li>{l s='You have a large product catalog with many attributes' mod='attributefeatureconnector'}</li>
                            <li>{l s='You want to maintain consistency between attributes and features' mod='attributefeatureconnector'}</li>
                            <li>{l s='You need to improve your store\'s filtering capabilities' mod='attributefeatureconnector'}</li>
                        </ul>
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-lightbulb-o"></i> {l s='Step-by-Step Guide' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <ol>
                            <li>
                                <strong>{l s='Create a mapping:' mod='attributefeatureconnector'}</strong>
                                <p>{l s='Select a feature value and associate it with one or more attributes. For example, map the feature "Material: Cotton" to the attribute "Fabric: Cotton".' mod='attributefeatureconnector'}</p>
                            </li>
                            <li>
                                <strong>{l s='Preview affected products:' mod='attributefeatureconnector'}</strong>
                                <p>{l s='Use the preview function to see which products will have the feature applied before actually making changes.' mod='attributefeatureconnector'}</p>
                            </li>
                            <li>
                                <strong>{l s='Generate features:' mod='attributefeatureconnector'}</strong>
                                <p>{l s='Click "Generate Features" for a specific mapping or "Generate ALL Features" to apply all mappings to your product catalog.' mod='attributefeatureconnector'}</p>
                            </li>
                            <li>
                                <strong>{l s='Set up automation:' mod='attributefeatureconnector'}</strong>
                                <p>{l s='Configure the CRON job to automatically apply mappings on a schedule.' mod='attributefeatureconnector'}</p>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-lightbulb-o"></i> {l s='Best Practices' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <ul>
                            <li><strong>{l s='Plan your mappings:' mod='attributefeatureconnector'}</strong> {l s='Consider how features and attributes should relate to each other before creating mappings.' mod='attributefeatureconnector'}</li>
                            <li><strong>{l s='Use batch processing:' mod='attributefeatureconnector'}</strong> {l s='For large catalogs, adjust the batch size settings to prevent timeouts.' mod='attributefeatureconnector'}</li>
                            <li><strong>{l s='Preview before applying:' mod='attributefeatureconnector'}</strong> {l s='Always use the preview function to check which products will be affected.' mod='attributefeatureconnector'}</li>
                            <li><strong>{l s='Regular maintenance:' mod='attributefeatureconnector'}</strong> {l s='Set up a CRON job to keep features synchronized with attributes as new products are added.' mod='attributefeatureconnector'}</li>
                        </ul>
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-question-circle"></i> {l s='FAQs' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <dl>
                            <dt>{l s='Can I map multiple attributes to a single feature?' mod='attributefeatureconnector'}</dt>
                            <dd>{l s='Yes, you can select multiple attributes for each feature value mapping.' mod='attributefeatureconnector'}</dd>
                            
                            <dt>{l s='Will this affect existing product features?' mod='attributefeatureconnector'}</dt>
                            <dd>{l s='No, the module only adds new features to products. It does not remove existing features unless you explicitly use the "Undo Mapping" function.' mod='attributefeatureconnector'}</dd>
                            
                            <dt>{l s='How do I handle timeouts with large catalogs?' mod='attributefeatureconnector'}</dt>
                            <dd>{l s='Use the batch size configuration to process products in smaller chunks, or set up the CRON job to run the process automatically in the background.' mod='attributefeatureconnector'}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        
        {elseif $preview_tab}
            <!-- Preview Tab Content -->
            <div class="preview-content">
                <h3>{l s='Preview Products Affected by Mapping' mod='attributefeatureconnector'}</h3>
                
                {if $preview_feature}
                    <div class="alert alert-info">
                        <p><strong>{l s='Feature:' mod='attributefeatureconnector'}</strong> {$preview_feature.feature_name}</p>
                        <p><strong>{l s='Value:' mod='attributefeatureconnector'}</strong> {$preview_feature.value}</p>
                    </div>
                    
                    {if empty($preview_products)}
                        <div class="alert alert-warning">
                            {l s='No products found with the selected attributes.' mod='attributefeatureconnector'}
                        </div>
                    {else}
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{l s='ID' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Reference' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Product Name' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $preview_products as $product}
                                        <tr>
                                            <td>{$product.id_product}</td>
                                            <td>{$product.reference}</td>
                                            <td>{$product.name}</td>
                                            <td>
                                                <a href="{$product.link}" target="_blank" class="btn btn-default btn-xs">
                                                    <i class="icon-eye"></i> {l s='View Product' mod='attributefeatureconnector'}
                                                </a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info">
                            {l s='Showing up to 10 products. There may be more products affected by this mapping.' mod='attributefeatureconnector'}
                        </div>
                    {/if}
                    
                    <div class="panel-footer">
                        <a href="{$generate_mapping_url}{$preview_mapping_id}" class="btn btn-success" onclick="return confirm('{l s='Are you sure you want to apply this mapping to all matching products?' mod='attributefeatureconnector'}');">
                            <i class="icon-refresh"></i> {l s='Apply This Mapping' mod='attributefeatureconnector'}
                        </a>
                        <a href="{$current_url}" class="btn btn-default">
                            <i class="icon-arrow-left"></i> {l s='Back to Mappings' mod='attributefeatureconnector'}
                        </a>
                    </div>
                {else}
                    <div class="alert alert-info">
                        {l s='Select "Preview" for a mapping from the main screen to see which products will be affected.' mod='attributefeatureconnector'}
                    </div>
                {/if}
            </div>
            
        {elseif $mapping_to_edit}
            <form id="edit_mapping_form" class="form-horizontal" action="{$smarty.server.REQUEST_URI}" method="post">
                <div class="panel-heading">
                    <i class="icon-pencil"></i> {l s='Edit Mapping' mod='attributefeatureconnector'}: {$mapping_to_edit.feature_name} - {$mapping_to_edit.value}
                </div>
                <input type="hidden" name="id_mapping" value="{$mapping_to_edit.id_mapping}" />
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Filter Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="icon-search"></i></span>
                            <input type="text" id="attribute_filter" class="form-control" placeholder="{l s='Type to filter attributes...' mod='attributefeatureconnector'}">
                        </div>
                    </div>
                </div>
                
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
                    <a href="{$preview_url}{$mapping_to_edit.id_mapping}" class="btn btn-info">
                        <i class="icon-eye"></i> {l s='Preview Products' mod='attributefeatureconnector'}
                    </a>
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
                    <label class="control-label col-lg-3">{l s='Filter Features' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="icon-search"></i></span>
                            <input type="text" name="feature_search" id="feature_search" class="form-control" placeholder="{l s='Type to filter features...' mod='attributefeatureconnector'}" value="{$feature_search}">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">
                                    <i class="icon-search"></i> {l s='Search' mod='attributefeatureconnector'}
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                
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
                    <label class="control-label col-lg-3">{l s='Filter Attributes' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="icon-search"></i></span>
                            <input type="text" name="attribute_search" id="attribute_search" class="form-control" placeholder="{l s='Type to filter attributes...' mod='attributefeatureconnector'}" value="{$attribute_search}">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">
                                    <i class="icon-search"></i> {l s='Search' mod='attributefeatureconnector'}
                                </button>
                            </span>
                        </div>
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
                
                <!-- Batch Processing Configuration -->
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Batch Size' mod='attributefeatureconnector'}</label>
                    <div class="col-lg-9">
                        <form method="post" action="{$smarty.server.REQUEST_URI}" class="form-inline">
                            <div class="input-group">
                                <input type="number" class="form-control" name="batch_size" value="{$batch_size}" min="10" max="500">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="submit" name="update_batch_size">
                                        <i class="icon-save"></i> {l s='Save' mod='attributefeatureconnector'}
                                    </button>
                                </span>
                            </div>
                            <p class="help-block">
                                {l s='Number of products to process in each batch. Lower values prevent timeouts on large catalogs.' mod='attributefeatureconnector'}
                            </p>
                        </form>
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
                                            <a href="{$preview_url}{$mapping.id_mapping}" class="btn btn-info btn-action">
                                                <i class="icon-eye"></i> {l s='Preview' mod='attributefeatureconnector'}
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
                        <a href="{$generate_url}" class="btn btn-primary" onclick="return confirm('{l s='This will apply all mappings to your products. For large catalogs, this may take some time. Continue?' mod='attributefeatureconnector'}');">
                            <i class="icon-refresh"></i> {l s='Generate ALL Features' mod='attributefeatureconnector'}
                        </a>
                    </div>
                </div>
            {/if}
        {/if}
    </div>
</div>