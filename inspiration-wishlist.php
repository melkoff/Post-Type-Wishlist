<?php

/**
 * Plugin Name: Custom Inspiration Wishlist
 * Description: Wishlist for post type "inspiration"
 * Version: 1.0
 * Author: melkoff
 * Author URI: https://github.com/melkoff
 *
 */

add_action('wp_enqueue_scripts', 'custom_inspiration_wishlist_scripts');
function custom_inspiration_wishlist_scripts()
{
    wp_enqueue_script('inspiration-wishlist-js', plugin_dir_url(__FILE__) . 'wishlist.js', ['jquery'], null, true);
    wp_localize_script('inspiration-wishlist-js', 'InspirationWishlist', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('inspiration_wishlist_nonce')
    ]);

    // Include custom CSS
    wp_enqueue_style('inspiration-wishlist-css', plugin_dir_url(__FILE__) . 'wishlist.css');
}

/**
 * Render wishlist toggle button
 */
add_shortcode('inspiration_favorite_btn', function () {
    $post_id = get_the_ID();
    if (! $post_id) return '';

    $user_id = get_current_user_id();
    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (! is_array($wishlist)) $wishlist = [];

    $in_wishlist = in_array($post_id, $wishlist);
    $state_class = $in_wishlist ? 'active' : '';
    $label = $in_wishlist ? 'Remove from favorites' : 'Add to favorites';

    ob_start();
?>
    <button class="inspiration-fav-btn <?php echo $state_class; ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <i class="fa<?php echo $in_wishlist ? 's' : 'r'; ?> fa-heart"></i> <?php echo $label; ?>
    </button>
    <?php
    return ob_get_clean();
});

/**
 * Handle AJAX
 */
add_action('wp_ajax_toggle_inspiration_wishlist', 'toggle_inspiration_wishlist');
function toggle_inspiration_wishlist()
{
    check_ajax_referer('inspiration_wishlist_nonce', 'nonce');

    $post_id = absint($_POST['post_id']);
    $user_id = get_current_user_id();
    if (! $user_id || get_post_type($post_id) !== 'inspiration') {
        wp_send_json_error();
    }

    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (! is_array($wishlist)) $wishlist = [];

    if (in_array($post_id, $wishlist)) {
        $wishlist = array_diff($wishlist, [$post_id]);
        $status = 'removed';
    } else {
        $wishlist[] = $post_id;
        $status = 'added';
    }

    update_user_meta($user_id, 'inspiration_wishlist', $wishlist);
    wp_send_json_success(['status' => $status]);
}

/**
 * Render wishlist list
 */
add_shortcode('inspiration_wishlist', function () {
    $user_id = get_current_user_id();
    if (! $user_id) return '<p>Please log in to view your favorites.</p>';

    $wishlist = get_user_meta($user_id, 'inspiration_wishlist', true);
    if (empty($wishlist) || ! is_array($wishlist)) return '<p>No favorites yet.</p>';

    $args = [
        'post_type' => 'inspiration',
        'post__in'  => $wishlist,
        'orderby'   => 'post__in'
    ];

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="inspiration-wishlist-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $title = get_the_title();
            $link = get_permalink();
            $thumb = get_the_post_thumbnail_url($id, 'medium');
    ?>
            <div class="wishlist-card">
                <div class="wishlist-card__image">
                    <a href="<?php echo esc_url($link); ?>">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                    </a>
                    <button class="wishlist-remove-btn" data-post-id="<?php echo esc_attr($id); ?>">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <div class="wishlist-card__title">
                    <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                </div>
            </div>
<?php
        }
        echo '</div>';
    } else {
        echo '<p>No inspiration favorites found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});
