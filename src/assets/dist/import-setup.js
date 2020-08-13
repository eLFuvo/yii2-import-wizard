(function ($) {
    var $importProgresContainer = $('.import-progress-container');
    $('.map-attribute').on('change', function () {
        var castTo = $(this).find('option:selected').data('type'),
            id = $(this).closest('td').data('id'),
            $selectCastTo = $(this).closest('.table').find('.type[data-id="' + id + '"] .cast-to');
        if (castTo && $selectCastTo.length) {
            $selectCastTo.val(castTo).trigger('change');
        }
    });

    $(window).on('import.stat.reload', function () {
        $importProgresContainer.load($importProgresContainer.data('url'), function () {
            if ($importProgresContainer.find('.import-done').length === 0) {
                setTimeout(function () {
                    $(window).trigger('import.stat.reload');
                }, 2000);
            } else {
                window.location.reload();
            }
        });
    });
})(jQuery);
