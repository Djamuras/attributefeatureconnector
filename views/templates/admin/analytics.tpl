<div class="bootstrap">

    <div class="afc-module-header">
        <div class="afc-module-header-title">
            <i class="icon-bar-chart"></i> {l s='Attribute-Feature Analytics' mod='attributefeatureconnector'}
        </div>
        <div class="afc-module-header-nav">
            <a href="{$connector_url}" class="btn btn-sm">
                <i class="icon-cogs"></i> {l s='Connector' mod='attributefeatureconnector'}
            </a>
        </div>
    </div>

    <div class="row">
            <div class="col-md-3">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-dashboard"></i> {l s='Overview' mod='attributefeatureconnector'}
                    </div>
                    <div class="afc-stats-grid" style="grid-template-columns: 1fr;">
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Total Operations' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.total_operations}</span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Products Processed' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.total_products_processed}</span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Products Updated' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.total_products_updated}</span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Avg Execution Time' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.avg_execution_time}<span class="afc-stat-sub">seconds</span></span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Max Execution Time' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.max_execution_time}<span class="afc-stat-sub">seconds</span></span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Avg Memory Usage' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value">{$metrics.avg_memory_usage}<span class="afc-stat-sub">MB</span></span>
                        </div>
                        <div class="afc-stat-card">
                            <span class="afc-stat-label">{l s='Unmapped Attributes' mod='attributefeatureconnector'}</span>
                            <span class="afc-stat-value {if $unmapped_count > 0}text-danger{else}text-success{/if}">{$unmapped_count}<span class="afc-stat-sub">of {$total_attributes} total</span></span>
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
            
        </div>
        
        
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-warning"></i> {l s='Unmapped Attributes' mod='attributefeatureconnector'}
                <span class="badge {if $unmapped_count > 0}badge-danger{else}badge-success{/if}" style="margin-left:8px;">{$unmapped_count}</span>
                <span class="panel-heading-action">
                    <a href="{$connector_url}" class="btn btn-primary btn-sm">
                        <i class="icon-plus"></i> {l s='Create Mapping' mod='attributefeatureconnector'}
                    </a>
                </span>
            </div>
            <div class="panel-body">
                {if !empty($unmapped_attributes)}
                    <div class="alert alert-warning">
                        {l s='The following attributes have no feature mapping. Products with these attributes will not have any feature automatically assigned.' mod='attributefeatureconnector'}
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{l s='Attribute Group' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Attribute' mod='attributefeatureconnector'}</th>
                                    <th>{l s='Action' mod='attributefeatureconnector'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$unmapped_attributes item=attr}
                                    <tr {if $attr@index < 10}class="new-attribute"{/if}>
                                        <td>{$attr.group_name}</td>
                                        <td>
                                            {$attr.attribute_name}
                                            {if $attr@index < 10}
                                                <span class="label label-warning" style="margin-left:5px;">{l s='New' mod='attributefeatureconnector'}</span>
                                            {/if}
                                        </td>
                                        <td>
                                            <a href="{$connector_url}" class="btn btn-default btn-xs">
                                                <i class="icon-plus"></i> {l s='Map this attribute' mod='attributefeatureconnector'}
                                            </a>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <div class="alert alert-success">
                        <i class="icon-check"></i> {l s='All attributes are mapped to features.' mod='attributefeatureconnector'}
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

</style>