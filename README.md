# Overview

DMC_Performance is a module that summarizes some tweaks found for Magento 2.
It does so without aiming to remove functionality (apart from the reports and visitor logging...).

This results in a slightly faster page load though it may not be noticeable when not comparing stats.

# Magento versions compatibility

Currently the module was tested with Magento Open Source 2.2.2 and is depending on this version at least.

# Features

* Removal of report events and visitor logging
* Tweaking of import bunch sizes to speed up imports (use with care, it may result in higher memory usage)
* Enhances batch sizes for product import types (use with care, it may result in higher memory usage)
* Command dmc:catalog:images:resize for faster image resizing using an image ID whitelist
* Aggregated product data attachment on product lists for data that would be called with single queries otherwise
* Default store configuration settings for flat catalog, async indexing, cron process usage (DB savings on the values will overwrite this)
* General cache helper (not PSR-6, but it's usable for quick usage) to save and load custom caches
* Cloudflare module fix (to be removed here, it should not make problems at all though)
* Add a store_id to anonymous block caches, so they will be cacheable for their distinct stores
* Topmenu category data custom cache (if you know how to use a collection cache, please let me know)
* Caching of product attribute options on the frontend using a custom cache

# Installation

The package should be available via packagist.org, so it can be installed directly.
```
composer require dmc/m2-module-performance:^1.0
php bin/magento module:enable DMC_Base
php bin/magento module:enable DMC_Performance
php bin/magento setup:upgrade
```

Optionally do all the things required for production usage.

# Usage

There are a few features, which can be utilized here.

## Faster image resizing 

When rendering a page with product images, Magento will generate product cache images on the fly which can be time intensive.
On the other hand, the image cache generation from Magento using `catalog:images:resize` will usually generate too many images
not required for sure. Because of this, the generation will take a long time and create images you may not use at all.

Using the command `dmc:catalog:images:resize` you can start the image resizing process which will 
create cached images for Magento much faster. To do this, it will use a product collection instead of loading products one by one using load() and filter the images to create.

Beware, this is currently using a whitelist in the view.xml!
There are 2 nodes you can use and you have to understand how images will be generated. Please consult the Magento documentation for image properties in themes first:
http://devdocs.magento.com/guides/v2.2/frontend-dev-guide/themes/theme-images.html

This is an example configuration you an use in your view.xml:
```        <!-- Generate with command dmc:catalog:image:resize -->
           <var name="generate_image_ids">
               <var name="cart_cross_sell_products">cart_cross_sell_products</var>
               <var name="cart_page_product_thumbnail">cart_page_product_thumbnail</var>
               <var name="category_page_grid">category_page_grid</var>
               <var name="category_page_list">category_page_list</var>
               <var name="customer_account_product_review_page">customer_account_product_review_page</var>
               <var name="product_base_image">product_base_image</var>
               <var name="product_thumbnail_image">product_thumbnail_image</var>
               <var name="mini_cart_product_thumbnail">mini_cart_product_thumbnail</var>
               <var name="related_products_list">related_products_list</var>
               <var name="recently_viewed_products_grid_content_widget">recently_viewed_products_grid_content_widget</var>
               <var name="recently_viewed_products_images_names_widget">recently_viewed_products_images_names_widget</var>
               <var name="recently_viewed_products_list_content_widget">recently_viewed_products_list_content_widget</var>
               <var name="upsell_products_list">upsell_products_list</var>
               <var name="wishlist_thumbnail">wishlist_thumbnail</var>
               <var name="wishlist_sidebar_block">wishlist_sidebar_block</var>
               <var name="related_product_image">related_product_image</var>
           </var>
           <var name="generate_gallery_image_ids">
               <var name="product_page_image_small">product_page_image_small</var>
               <var name="product_page_image_medium_no_frame">product_page_image_medium_no_frame</var>
               <var name="product_page_image_large_no_frame">product_page_image_large_no_frame</var>
               <var name="product_base_image">product_base_image</var>
               <var name="product_thumbnail_image">product_thumbnail_image</var>
           </var>
```

For `generate_image_ids`, only the corresponding attached images to the attribute will be geenrated.
For `generate_gallery_image_ids`, all images will be generated as the media gallery will load them as as well.

To know which image IDs will be used, you can search your code or log calls for the image cache helpers.

If you never have to remove the cached images, you can just rerun this command as a cronjob using `php bin/magento dmc:catalog:images:resize`.

## Collection data preloading

The collection data enhancement will collect and add data that would be loaded in single queries when visiting a list block:

1. Add DMC\Performance\Helper\Catalog via dependency injection
2. Use your collection with the helper method:
`$this->performanceHelper->attachPreloadDataToCollection($collection);`

Depending on how customized your code is, this can save quite some sql queries.
Currently it will attache the following data:
* Tier prices
* Stock items (not always needed though, may be separated later)
* Catalog rule prices
* Category IDs

Things that will not be added with this, but could be useful depending on your code:
* URL rewrites
* Website IDs/Names
* General attributes

# History

## 1.0.0

Initial version
