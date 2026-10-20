jQuery(function ($) {
    // Keep each color swatch and its hex text box in sync.
    $('.bbcore-color').each(function () {
        var $swatch = $(this).find('input[type="color"]');
        var $text = $(this).find('input[type="text"]');

        $swatch.on('input', function () {
            // Keep an 8-digit color's transparency when picking a new color.
            var alpha = /^#[0-9a-f]{8}$/i.test($text.val()) ? $text.val().slice(7) : '';
            $text.val($swatch.val() + alpha);
        });
        $text.on('input', function () {
            var value = $text.val();
            if (/^#[0-9a-f]{6}([0-9a-f]{2})?$/i.test(value)) {
                $swatch.val(value.slice(0, 7));
            }
        });
    });

    // "Choose image" opens the media library.
    $('.bbcore-choose-image').on('click', function (e) {
        e.preventDefault();
        var $wrap = $(this).closest('.bbcore-image');
        var frame = wp.media({ title: 'Choose image', button: { text: 'Use this image' }, multiple: false });

        frame.on('select', function () {
            var url = frame.state().get('selection').first().toJSON().url;
            $wrap.find('input[type="text"]').val(url);
            $wrap.find('.bbcore-image-preview').empty().append($('<img alt="">').attr('src', url));
        });
        frame.open();
    });

    $('form[data-confirm]').on('submit', function (e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });
});
