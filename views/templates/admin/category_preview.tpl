<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-eye"></i> {l s='Preview Category-Feature Mapping' mod='attributefeatureconnector'}: {$preview_results.feature_name} - {$preview_results.feature_value}
            <span class="badge">{$preview_results.total_affected} {l s='products' mod='attributefeatureconnector'}</span>
            
            <div class="panel-heading-action">
                <a href="{$back_url}" class="btn btn-default">
                    <i class="process-icon-back"></i> {l s='Back to mappings' mod='attributefeatureconnector'}
                </a>
            </div>
        </div>
        
        <div class="alert alert-info">
            <p><i class="icon-info-circle"></i> {l s='This preview shows products from the category "%s" that will receive the feature "%s - %s".' sprintf=[$preview_results.category_name, $preview_results.feature_name, $preview_results.feature_value] mod='attributefeatureconnector'}</p>
            <p>{l s='Only the first %d products are displayed.' sprintf=[$preview_results.showing_limit] mod='attributefeatureconnector'}</p>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='ID' mod='attributefeatureconnector'}</th>
                        <th>{l s='Product Name' mod='attributefeatureconnector'}</th>
                        <th>{l s='Status' mod='attributefeatureconnector'}</th>
                        <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$preview_results.affected_products item=product}
                        <tr>
                            <td>{$product.id_product}</td>
                            <td>{$product.product_name}</td>
                            <td>
                                {if $product.already_has_feature}
                                    <span class="label label-success">{l s='Feature already assigned' mod='attributefeatureconnector'}</span>
                                {else}
                                    <span class="label label-warning">{l s='Feature will be assigned' mod='attributefeatureconnector'}</span>
                                {/if}
                            </td>
                            <td>
                                <a href="{$product.edit_url}" class="btn btn-default btn-xs" target="_blank">
                                    <i class="icon-pencil"></i> {l s='Edit Product' mod='attributefeatureconnector'}
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        
        <div class="panel-footer">
            <div class="row">
                <div class="col-md-6">
                    <a href="{$back_url}" class="btn btn-default">
                        <i class="process-icon-back"></i> {l s='Back to mappings' mod='attributefeatureconnector'}
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{$back_url}&action=generateFeatures&id_mapping={$mapping_id}" class="btn btn-success">
                        <i class="icon-refresh"></i> {l s='Apply This Mapping' mod='attributefeatureconnector'}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>