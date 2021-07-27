# Commerce Insights plugin for Craft CMS 3.x

Better stats for Craft Commerce.

## Local Project Install

Copy source to a folder named `plugin` in a Craft project.

Add a symbolic link to that path in `composer.json`:

```
"repositories": [
    {
        "type": "path",
        "symlink": false,
        "url": "./plugins/"
    }
]
```

Require with `fostercommerce/commerce-insights`.

Install via command line with `./craft install/plugin commerceinsights` or from the Craft CMS control panel.

## Twig Templates

Twig templates are found in `src/templates/vue`. These control each of the pages for the plugin in the CP.

## Component Overview

Each control panel view is an instance of a fairly simple Vue app that uses the custom components in `src/resources/components`. The goal of these components is to facilitate a consistent design language, plugin-specific UX, and keep properties are deliberately flexible and simple so that backend connections can be neatly joined to the components with minimal friction.

The most important components are the extensively-used Pane, the complex Chart which wraps and preconfigures Chart.js variations, and the DateRangePicker that determines the range of data that's needed for each control panel view.

### Pane

Every bordered white box visible in the Craft CMS control panel views is some form of Pane. Panes can have titles and trends with stuff in them. That's it. The simple tables seen in the control panel views are just tables with normal `<table>` element markup in them.

### Chart

The Chart component simplifies the format needed for data on its way to Chart.js, and preconfigures a few different chart types for style and orientation within Panels that were designed for them.

### DateRangePicker

The DateRangePicker combines the popular `daterangepicker/daterangepicker.js` jQuery plugin (isolated in the DatePicker component) with custom UI for selecting and saving preset date ranges.

## Filters

Each of the pages within the plugin has a set of filters that can be applied to the results.

### Order Status

This filter lets you choose between any or all custom order statuses. Examples would be "New", "Shipped", and "Completed", but they can be anything depending on what custom order status you have in Commerce. Selecting an option from this filter clears all other filters.

### Payment Status

The Payment Status filter lets you choose between "Paid", "Partial", "Unpaid", or "All". Selecting an option from this filter clears all other filters.

### Search

The search box lets you search orders by the following criteria: Customer name, customer email, order number, order status, or payment status. Using the search will not clear any other filters you have applied.

## Export to CSV

This feature allows you to export the results to a CSV spreadsheet. Any filters applied to the results will apply to the exported CSV document.

## Orders Page

Shows a high-level overview of orders within the selected date range.

### Top Paragraph

The top paragraph shows stats for revenue and number of orders at a glance. It also compares this data to the previous period and shows the percentage change.

### Total Orders Chart

The Total Orders chart shows the number of orders in the selected range, the percentage change vs. the previous period, and a graph showing the trend over time during the selected date period.

### Average Value Chart

The Average Value chart shows the average order value in the selected range, the percentage change vs. the previous period, and a graph showing the trend over time during the selected date period.

### Average Order Quantity Chart

The Average Order Quantity chart shows the average number of items across all orders in the selected range, the percentage change vs. the previous period, and a graph showing the trend over time during the selected date period.

### Results table

The results table shows all orders for the specified date range which match any criteria applied via the filters.

## Items Sold Page

Shows the products that have been sold during the specified date period, ordered from most sold to least.

### Item Column

The name of the product.

### SKU Column

The product SKU.

### Product Type Column

The product type.

### Total Sold Column

The total times that this product has been sold across all orders in the selected date range. Also shows the number of orders that this product was sold in; clicking this will show a list of all orders containing this product.

### Total Sales Column

The item price multiplied by the total sold for the given date range.
