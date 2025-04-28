<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-bar-chart"></i> {l s='Attribute-Feature Analytics' mod='attributefeatureconnector'}
            <span class="panel-heading-action">
                <a href="{$connector_url}" class="btn btn-default">
                    <i class="icon-cogs"></i> {l s='Back to Connector' mod='attributefeatureconnector'}
                </a>
            </span>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-dashboard"></i> {l s='Overview' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <div class="stat-item">
                            <span class="stat-label">{l s='Total Operations' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.total_operations}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{l s='Products Processed' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.total_products_processed}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{l s='Products Updated' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.total_products_updated}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{l s='Avg Execution Time' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.avg_execution_time} s</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{l s='Max Execution Time' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.max_execution_time} s</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">{l s='Avg Memory Usage' mod='attributefeatureconnector'}</span>
                            <span class="stat-value">{$metrics.avg_memory_usage} MB</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-line-chart"></i> {l s='Performance Metrics' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        {if $has_chart_data}
                            <canvas id="performanceChart" width="800" height="300"></canvas>
                        {else}
                            <div class="alert alert-info">
                                {l s='No performance data available yet. Run operations to collect metrics.' mod='attributefeatureconnector'}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-sliders"></i> {l s='Optimization Suggestions' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <p><i class="icon-lightbulb"></i> {l s='Based on performance data, the optimal batch size for your server is' mod='attributefeatureconnector'} <strong>{$optimal_batch_size}</strong></p>
                            
                            {if $optimal_batch_size != $current_batch_size}
                                <p>{l s='Your current batch size is' mod='attributefeatureconnector'} <strong>{$current_batch_size}</strong></p>
                                <form method="post" action="{$update_batch_url}" class="form-inline">
                                    <button type="submit" name="submit_update_batch" class="btn btn-primary">
                                        <i class="icon-refresh"></i> {l s='Apply Recommended Batch Size' mod='attributefeatureconnector'}
                                    </button>
                                    <input type="hidden" name="batch_size" value="{$optimal_batch_size}">
                                </form>
                            {else}
                                <p class="text-success"><i class="icon-check"></i> {l s='You are already using the optimal batch size.' mod='attributefeatureconnector'}</p>
                            {/if}
                        </div>
                        
                        <h4>{l s='Performance Tips' mod='attributefeatureconnector'}</h4>
                        <ul class="performance-tips">
                            <li>
                                <i class="icon-angle-right"></i> {l s='Schedule CRON jobs during off-peak hours to minimize impact on shop performance.' mod='attributefeatureconnector'}
                            </li>
                            <li>
                                <i class="icon-angle-right"></i> {l s='For large catalogs (10,000+ products), consider running operations in smaller batches.' mod='attributefeatureconnector'}
                            </li>
                            <li>
                                <i class="icon-angle-right"></i> {l s='Organize mappings into categories for better management and improved performance.' mod='attributefeatureconnector'}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-exclamation-triangle"></i> {l s='Mapping Conflicts' mod='attributefeatureconnector'}
                    </div>
                    <div class="panel-body">
                        {if !empty($conflicts)}
                            <div class="alert alert-warning">
                                <p>{l s='The following conflicts were detected in your mappings:' mod='attributefeatureconnector'}</p>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Attribute' mod='attributefeatureconnector'}</th>
                                            <th>{l s='Feature' mod='attributefeatureconnector'}</th>
                                            <th>{l s='Conflicts' mod='attributefeatureconnector'}</th>
                                            <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$conflicts item=conflict}
                                            <tr>
                                                <td>{$conflict.attribute_name}</td>
                                                <td>{$conflict.feature_name}</td>
                                                <td>
                                                    <ul class="conflict-values">
                                                        {foreach from=$conflict.mappings item=mapping}
                                                            <li>{$mapping.feature_value}</li>
                                                        {/foreach}
                                                    </ul>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical">
                                                        {foreach from=$conflict.mappings item=mapping}
                                                            <a href="{$resolve_conflict_url}&id_attribute={$conflict.id_attribute}&id_feature={$conflict.id_feature}&keep_mapping_id={$mapping.id_mapping}" class="btn btn-xs btn-default" onclick="return confirm('{l s='This will remove the attribute from other conflicting mappings. Continue?' mod='attributefeatureconnector'}');">
                                                                {l s='Keep' mod='attributefeatureconnector'} "{$mapping.feature_value}"
                                                            </a>
                                                        {/foreach}
                                                    </div>
                                                </td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        {else}
                            <div class="alert alert-success">
                                <i class="icon-check"></i> {l s='No mapping conflicts detected.' mod='attributefeatureconnector'}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-lightbulb"></i> {l s='Auto-Attribute Suggestions' mod='attributefeatureconnector'}
                <span class="panel-heading-action">
                    <a href="{$analyze_product_url}" class="btn btn-default">
                        <i class="icon-refresh"></i> {l s='Analyze Products' mod='attributefeatureconnector'}
                    </a>
                </span>
            </div>
            <div class="panel-body">
                {if !empty($suggestions)}
                    <div class="alert alert-info">
                        <p>{l s='The following potential attributes were detected in your product descriptions:' mod='attributefeatureconnector'}</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{l s='Product' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Suggested Attribute' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Confidence' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Actions' mod='attributefeatureconnector'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$suggestions item=suggestion}
                                    <tr>
                                        <td>{$suggestion.product_name}</td>
                                        <td><strong>{$suggestion.text}</strong></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{$suggestion.confidence*100}" aria-valuemin="0" aria-valuemax="100" style="width: {$suggestion.confidence*100}%;">
                                                    {math equation="x*100" x=$suggestion.confidence format="%.0f"}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{$process_suggestion_url}&id_suggestion={$suggestion.id_suggestion}" class="btn btn-xs btn-success">
                                                    <i class="icon-check"></i> {l s='Process' mod='attributefeatureconnector'}
                                                </a>
                                                <a href="{$ignore_suggestion_url}&id_suggestion={$suggestion.id_suggestion}" class="btn btn-xs btn-default">
                                                    <i class="icon-ban"></i> {l s='Ignore' mod='attributefeatureconnector'}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <div class="alert alert-info">
                        <p>{l s='No attribute suggestions available. Run product analysis to generate suggestions.' mod='attributefeatureconnector'}</p>
                        <a href="{$analyze_product_url}" class="btn btn-primary">
                            <i class="icon-search"></i> {l s='Analyze Product Descriptions' mod='attributefeatureconnector'}
                        </a>
                    </div>
                {/if}
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-history"></i> {l s='Recent Performance Logs' mod='attributefeatureconnector'}
            </div>
            <div class="panel-body">
                {if !empty($performance_logs)}
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{l s='Date' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Operation' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Mapping ID' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Products Processed' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Products Updated' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Execution Time' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Batch Size' mod='attributefeatureconnector'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$performance_logs item=log}
                                    <tr>
                                        <td>{$log.date_add}</td>
                                        <td>{$log.operation}</td>
                                        <td>{if $log.id_mapping}{$log.id_mapping}{else}-{/if}</td>
                                        <td>{$log.products_processed}</td>
                                        <td>{$log.products_updated}</td>
                                        <td>{$log.execution_time} s</td>
                                        <td>{$log.batch_size}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <div class="alert alert-info">
                        <p>{l s='No performance logs available yet.' mod='attributefeatureconnector'}</p>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

{if $has_chart_data}
<script type="text/javascript">
$(document).ready(function() {
    // Performance chart data
    var chartData = {$performance_graph_data};
    
    // Create performance chart
    var ctx = document.getElementById('performanceChart').getContext('2d');
    var performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: '{l s='Execution Time (s)' mod='attributefeatureconnector'}',
                    data: chartData.execution_times,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y-axis-1'
                },
                {
                    label: '{l s='Products Processed' mod='attributefeatureconnector'}',
                    data: chartData.products_processed,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y-axis-2'
                },
                {
                    label: '{l s='Batch Size' mod='attributefeatureconnector'}',
                    data: chartData.batch_sizes,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    yAxisID: 'y-axis-2'
                }
            ]
        },
        options: {
            responsive: true,
            hoverMode: 'index',
            stacked: false,
            title: {
                display: true,
                text: '{l s='Module Performance Over Time' mod='attributefeatureconnector'}'
            },
            scales: {
                xAxes: [{
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: '{l s='Time' mod='attributefeatureconnector'}'
                    }
                }],
                yAxes: [
                    {
                        id: 'y-axis-1',
                        type: 'linear',
                        display: true,
                        position: 'left',
                        scaleLabel: {
                            display: true,
                            labelString: '{l s='Execution Time (s)' mod='attributefeatureconnector'}'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    },
                    {
                        id: 'y-axis-2',
                        type: 'linear',
                        display: true,
                        position: 'right',
                        gridLines: {
                            drawOnChartArea: false
                        },
                        scaleLabel: {
                            display: true,
                            labelString: '{l s='Count' mod='attributefeatureconnector'}'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }
                ]
            }
        }
    });
});
</script>
{/if}

<style>
.stat-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    display: block;
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 18px;
    color: #00aff0;
}

.performance-tips li {
    margin-bottom: 10px;
}

.conflict-values {
    margin: 0;
    padding-left: 15px;
}

.btn-group-vertical .btn {
    margin-bottom: 5px;
}
</style>