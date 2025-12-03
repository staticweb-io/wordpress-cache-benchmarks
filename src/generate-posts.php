<?php
/**
 * Deterministic WordPress content generator
 *
 * Run with:
 *   wp eval-file generate-posts.php
 */

// -----------------------------------------------
// CONFIG
// -----------------------------------------------
$SEED           = 12345;
$TOTAL_POSTS    = 1000;
$CATEGORY_COUNT = 20;

// -------------------------------
// Deterministic RNG (stable)
// -------------------------------
function seeded_rand(string $key, int $seed): int {
    $data = $seed . '-' . $key;
    return hexdec(substr(hash('sha256', $data), 0, 8));
}

// -----------------------------------------------
// CREATE CATEGORIES (deterministically named)
// -----------------------------------------------
echo "Creating $CATEGORY_COUNT categories...\n";

$cat_ids = [];
for ($i = 1; $i <= $CATEGORY_COUNT; $i++) {
    $name = "Category $i";
    $slug = "category-$i";

    // Check if category exists
    $existing = get_term_by('slug', $slug, 'category');
    if ($existing) {
        $cat_ids[] = $existing->term_id;
        continue;
    }

    $result = wp_insert_term($name, 'category', ['slug' => $slug]);
    if (is_wp_error($result)) {
        echo "Error creating category $i: " . $result->get_error_message() . "\n";
        exit(1);
    }

    $cat_ids[] = $result['term_id'];
}

echo "Created/loaded " . count($cat_ids) . " categories.\n";


// -----------------------------------------------
// CREATE POSTS
// -----------------------------------------------
echo "Creating $TOTAL_POSTS posts...\n";

for ($i = 1; $i <= $TOTAL_POSTS; $i++) {

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
    $title = "Sample Post $i";

    // Content length
    $rlen = seeded_rand("len-$i", $SEED);
    $paragraphs = 2 + ($rlen % 6);
    $content = "";

    for ($p = 1; $p <= $paragraphs; $p++) {
        $content .= "Paragraph $p of post $i. ";
        $content .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit. ";
        $content .= "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\n";
    }

    // Check if post exists by unique slug
    $slug = "sample-post-$i";
    $existing = get_page_by_path($slug, OBJECT, 'post');
    if ($existing) {
        continue;
    }

    // Insert the post
    wp_insert_post([
        'post_type'      => 'post',
        'post_title'     => $title,
        'post_name'      => $slug,
        'post_status'    => 'publish',
        'post_date'      => $post_date,
        'post_content'   => $content,
        'post_category'  => [$cat_id],
    ]);

    if ($i % 200 === 0) {
        echo "Created $i posts...\n";
    }
}

echo "Done. Created up to $TOTAL_POSTS posts.\n";
