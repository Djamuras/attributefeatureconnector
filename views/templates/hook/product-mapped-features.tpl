{if !empty($afc_features)}
    <section class="afc-product-features" aria-label="{$afc_title|escape:'html':'UTF-8'}">
        <h3 class="afc-product-features__title">{$afc_title|escape:'html':'UTF-8'}</h3>
        <dl class="afc-product-features__list">
            {foreach from=$afc_features item=feature}
                <div class="afc-product-features__item">
                    <dt>{$feature.feature_name|escape:'html':'UTF-8'}</dt>
                    <dd>{$feature.feature_value|escape:'html':'UTF-8'}</dd>
                </div>
            {/foreach}
        </dl>
    </section>
{/if}
