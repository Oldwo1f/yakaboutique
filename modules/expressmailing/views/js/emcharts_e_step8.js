/**
* 2014-2015 (c) Axalone France - Express-Mailing
*
* This file is a commercial module for Prestashop
* Do not edit or add to this file if you wish to upgrade PrestaShop or
* customize PrestaShop for your needs please refer to
* http://www.express-mailing.com for more information.
*
* @author    Axalone France <info@express-mailing.com>
* @copyright 2014-2015 (c) Axalone France
* @license   http://www.express-mailing.com
*/

// Donut juste avant la validation d'un emailing

$(function ()
{
    nv.addGraph(function ()
    {
        var chart = nv.models.pieChart()
                .x(function (d) { return d.label; })
                .y(function (d) { return d.value; })
                .showLabels(true)     // Display pie labels
                .labelThreshold(.05)  // Configure the minimum slice size for labels to show up
                .labelType("value")   // Configure what type of data to show in the label. Can be "key", "value" or "percent"
                .donut(true)          // Turn on Donut mode. Makes pie chart look tasty!
                .donutRatio(0.3)      // Configure how big you want the donut hole size to be.
				.color(['#CC0000', '#333333', '#CCCC00', '#00CC00']);

        d3.select("#chart")
                .datum(chartData)
                .transition().duration(350)
                .call(chart);

        return chart;
    });
});