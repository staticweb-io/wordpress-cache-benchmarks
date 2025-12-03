<?php
/**
 * Deterministic WooCommerce product generator
 *
 * Run with:
 *   wp eval-file generate-products.php
 */

// -----------------------------------------------
// CONFIG
// -----------------------------------------------
$SEED            = 12345;
$TOTAL_PRODUCTS  = 1000;
$CATEGORY_COUNT  = 20;

// -------------------------------
// Deterministic RNG (stable)
// -------------------------------
function seeded_rand(string $key, int $seed): int {
    $data = $seed . '-' . $key;
    return hexdec(substr(hash('sha256', $data), 0, 8));
}

// -----------------------------------------------
// CREATE PRODUCT CATEGORIES (deterministically named)
// -----------------------------------------------
echo "Creating $CATEGORY_COUNT product categories...\n";

$cat_ids = [];
for ($i = 1; $i <= $CATEGORY_COUNT; $i++) {
    $name = "Product Category $i";
    $slug = "product-category-$i";

    // Check if category exists
    $existing = get_term_by('slug', $slug, 'product_cat');
    if ($existing) {
        $cat_ids[] = $existing->term_id;
        continue;
    }

    $result = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
    if (is_wp_error($result)) {
        echo "Error creating product category $i: " . $result->get_error_message() . "\n";
        exit(1);
    }

    $cat_ids[] = $result['term_id'];
}

echo "Created/loaded " . count($cat_ids) . " product categories.\n";


// -----------------------------------------------
// CREATE PRODUCTS
// -----------------------------------------------
echo "Creating $TOTAL_PRODUCTS products...\n";

for ($i = 1; $i <= $TOTAL_PRODUCTS; $i++) {

    // Category selection with pseudo-Zipf distribution
    $r = seeded_rand("cat-$i", $SEED);
    $cat_index = $r % count($cat_ids);
    $cat_id = $cat_ids[$cat_index];

    // Generate deterministic publish date
    $rdate = seeded_rand("date-$i", $SEED);
    $year  = 2015 + ($rdate % 10);
    $month = 1 + ($rdate % 12);
    $day   = 1 + (int)(($rdate / 31) % 28);
    $hour  = $rdate % 24;
    $min   = $rdate % 60;
    $sec   = (int)(($rdate / 7) % 60);
    $post_date = sprintf(
        '%04d-%02d-%02d %02d:%02d:%02d',
        $year, $month, $day, $hour, $min, $sec
    );

    // Title
    $title = "Sample Product $i";

    // Description
    $rlen = seeded_rand("len-$i", $SEED);
    $paragraphs = 2 + ($rlen % 4);
    $description = "";

    for ($p = 1; $p <= $paragraphs; $p++) {
        $description .= "Product description paragraph $p for product $i. ";
        $description .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit. ";
        $description .= "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\n";
    }

    // Short description
    $short_description = "Short description for product $i. High quality and affordable.";

    // Price (deterministic)
    $rprice = seeded_rand("price-$i", $SEED);
    $price = 9.99 + ($rprice % 990);

    // Stock quantity
    $rstock = seeded_rand("stock-$i", $SEED);
    $stock = 10 + ($rstock % 90);

    // Check if product exists by unique slug
    $slug = "sample-product-$i";
    $existing = get_page_by_path($slug, OBJECT, 'product');
    if ($existing) {
        continue;
    }

    // Insert the product
    $product_id = wp_insert_post([
        'post_type'      => 'product',
        'post_title'     => $title,
        'post_name'      => $slug,
        'post_status'    => 'publish',
        'post_date'      => $post_date,
        'post_content'   => $description,
        'post_excerpt'   => $short_description,
    ]);

    if (is_wp_error($product_id)) {
        echo "Error creating product $i: " . $product_id->get_error_message() . "\n";
        continue;
    }

    // Set product category
    wp_set_object_terms($product_id, $cat_id, 'product_cat');

    // Set product meta (price, stock, etc.)
    update_post_meta($product_id, '_price', $price);
    update_post_meta($product_id, '_regular_price', $price);
    update_post_meta($product_id, '_stock', $stock);
    update_post_meta($product_id, '_stock_status', 'instock');
    update_post_meta($product_id, '_manage_stock', 'yes');
    update_post_meta($product_id, '_visibility', 'visible');
    update_post_meta($product_id, '_featured', 'no');
    
    // Set catalog visibility taxonomy
    wp_set_object_terms($product_id, 'visible', 'product_visibility');

    if ($i % 200 === 0) {
        echo "Created $i products...\n";
    }
}

echo "Done. Created up to $TOTAL_PRODUCTS products.\n";
