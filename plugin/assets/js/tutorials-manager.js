jQuery(function ($) {
    var $rows = $('.bbcore-tutorials-table tbody');
    var template = $('#bbcore-tutorial-row-template').html();
    // Each new row needs a unique index. PHP keeps rows in page order,
    // so indexes don't need to be sequential.
    var nextIndex = $rows.children().length;

    $('.bbcore-add-tutorial').on('click', function () {
        var $row = $(template.replace(/__INDEX__/g, 'new' + nextIndex++));
        $rows.append($row);
        $row.find('input').first().trigger('focus');
    });

    $rows.on('click', '.bbcore-remove-tutorial', function () {
        $(this).closest('tr').remove();
    });

    $rows.sortable({
        handle: '.bbcore-drag',
        axis: 'y',
        helper: function (e, $tr) {
            // Keep cell widths while dragging.
            $tr.children().each(function () {
                $(this).width($(this).width());
            });
            return $tr;
        }
    });

    // Start with one empty row so there's somewhere to type.
    if (!$rows.children().length) {
        $('.bbcore-add-tutorial').trigger('click');
    }
});
