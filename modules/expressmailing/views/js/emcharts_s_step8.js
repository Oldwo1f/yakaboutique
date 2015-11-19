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
* @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

// Donut juste avant la validation d'un sms mailing

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
				.color(['#00CC00', '#CC0000']);

        d3.select("#chart")
                .datum(chartData)
                .transition().duration(350)
                .call(chart);

        return chart;
    });
});