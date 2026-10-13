document.addEventListener('DOMContentLoaded', function () {
    var mainImg = document.getElementById('FeaturedImg');
    var thumbs = document.querySelectorAll('.ld-featured-product .pict');

    if (!mainImg || !thumbs.length) {
        return;
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var large = thumb.getAttribute('data-large');
            if (large) {
                mainImg.src = large;
            }
        });
    });
});
