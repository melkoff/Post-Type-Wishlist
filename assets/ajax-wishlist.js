jQuery(document).ready(function($){
    // Button Add to favorites and Delete on page Inspiration
    $('.inspiration-fav-btn').on('click', function(){
        var btn = $(this);
        var postID = btn.data('post-id');

        $.post(InspirationWishlist.ajaxurl, {
            action: 'toggle_inspiration_wishlist',
            post_id: postID,
            nonce: InspirationWishlist.nonce
        }, function(response){
            if (response.success) {
                if (response.data.status === 'added') {
                    btn.addClass('active').html('<i class="fas fa-heart"></i>');
                } else {
                    btn.removeClass('active').html('<i class="far fa-heart"></i>');
                }
            }
        });
    });

    // Button Remove on wishlist page
    $('.wishlist-remove-btn').on('click', function(){
        var btn = $(this);
        var postID = btn.data('post-id');
        var card = btn.closest('.wishlist-card');

        $.post(InspirationWishlist.ajaxurl, {
            action: 'toggle_inspiration_wishlist',
            post_id: postID,
            nonce: InspirationWishlist.nonce
        }, function(response){
            if (response.success && response.data.status === 'removed') {
                card.slideUp(300, function() {
                    card.remove();
                    // If doesn't have any favorites show message
                    if ($('.wishlist-card').length === 0) {
                        $('.inspiration-wishlist-grid').html('<p>No inspiration favorites yet.</p>');
                    }
                });
            }
        });
    });
});
