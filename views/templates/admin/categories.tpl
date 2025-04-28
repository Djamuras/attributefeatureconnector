<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-folder-open"></i> {l s='Mapping Categories' mod='attributefeatureconnector'}
            <span class="panel-heading-action">
                <a href="{$back_url}" class="btn btn-default">
                    <i class="icon-chevron-left"></i> {l s='Back to Mappings' mod='attributefeatureconnector'}
                </a>
            </span>
        </div>
        
        <div class="alert alert-info">
            <p>{l s='Categories help you organize your attribute-feature mappings for better management.' mod='attributefeatureconnector'}</p>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-list"></i> {l s='Categories List' mod='attributefeatureconnector'}
                    </div>
                    
                    {if !empty($categories)}
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{l s='Name' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Description' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Mappings' mod='attributefeatureconnector'}</th>
                                        <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$categories item=category}
                                        <tr>
                                            <td>{$category.name}</td>
                                            <td>{$category.description|truncate:100}</td>
                                            <td><span class="badge">{$category.mappings_count}</span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{$edit_category_url}{$category.id_category}" class="btn btn-default btn-xs">
                                                        <i class="icon-pencil"></i> {l s='Edit' mod='attributefeatureconnector'}
                                                    </a>
                                                    
                                                    {if $category.name != 'Default'}
                                                        <a href="{$delete_category_url}&id_category={$category.id_category}" class="btn btn-default btn-xs" onclick="return confirm('{l s='Are you sure you want to delete this category? Associated mappings will be moved to the Default category.' mod='attributefeatureconnector'}');">
                                                            <i class="icon-trash"></i> {l s='Delete' mod='attributefeatureconnector'}
                                                        </a>
                                                    {/if}
                                                    
                                                    <a href="{$back_url}&category_filter={$category.id_category}" class="btn btn-default btn-xs">
                                                        <i class="icon-filter"></i> {l s='View Mappings' mod='attributefeatureconnector'}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="alert alert-warning">
                            <p>{l s='No categories found. Create one using the form on the right.' mod='attributefeatureconnector'}</p>
                        </div>
                    {/if}
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-heading">
                        {if isset($category_to_edit)}
                            <i class="icon-pencil"></i> {l s='Edit Category' mod='attributefeatureconnector'}
                        {else}
                            <i class="icon-plus"></i> {l s='Add New Category' mod='attributefeatureconnector'}
                        {/if}
                    </div>
                    
                    <form method="post" action="{$smarty.server.REQUEST_URI}" class="form-horizontal">
                        {if isset($category_to_edit)}
                            <input type="hidden" name="id_category" value="{$category_to_edit.id_category}">
                        {/if}
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3 required">{l s='Name' mod='attributefeatureconnector'}</label>
                            <div class="col-lg-9">
                                <input type="text" name="category_name" class="form-control" required 
                                    value="{if isset($category_to_edit)}{$category_to_edit.name}{/if}">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Description' mod='attributefeatureconnector'}</label>
                            <div class="col-lg-9">
                                <textarea name="category_description" class="form-control" rows="5">{if isset($category_to_edit)}{$category_to_edit.description}{/if}</textarea>
                            </div>
                        </div>
                        
                        <div class="panel-footer">
                            <a href="{$back_url}" class="btn btn-default">
                                <i class="process-icon-cancel"></i> {l s='Cancel' mod='attributefeatureconnector'}
                            </a>
                            
                            <button type="submit" name="{if isset($category_to_edit)}submitEditCategory{else}submitNewCategory{/if}" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> {l s='Save' mod='attributefeatureconnector'}
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-info-circle"></i> {l s='Help' mod='attributefeatureconnector'}
                    </div>
                    
                    <div class="alert alert-info">
                        <h4>{l s='Tips for Category Management:' mod='attributefeatureconnector'}</h4>
                        <ul>
                            <li>{l s='Create categories based on product types or departments' mod='attributefeatureconnector'}</li>
                            <li>{l s='Keep category names simple and descriptive' mod='attributefeatureconnector'}</li>
                            <li>{l s='The Default category cannot be deleted' mod='attributefeatureconnector'}</li>
                            <li>{l s='When a category is deleted, its mappings are moved to the Default category' mod='attributefeatureconnector'}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>