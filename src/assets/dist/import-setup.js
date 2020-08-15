/*
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

(function ($) {
    var $importProgresContainer = $('.import-progress-container'),
        $form = $('.import-form');
    $('.map-attribute').on('change', function () {
        var castTo = $(this).find('option:selected').data('type'),
            id = $(this).closest('td').data('id'),
            $selectCastTo = $(this).closest('.table').find('.type[data-id="' + id + '"] .cast-to');
        if (castTo && $selectCastTo.length) {
            $selectCastTo.val(castTo).trigger('change');
        }
    });

    $(window).on('import.stat.reload', function () {
        $importProgresContainer.load($importProgresContainer.data('url'), function (content) {
            if (/Not Found/.test(content)) {
                return;
            }
            if ($importProgresContainer.find('.import-done').length === 0) {
                $form.hide();
                setTimeout(function () {
                    $(window).trigger('import.stat.reload');
                }, 2000);
            }
        });
    });
    $(window).trigger('import.stat.reload');
})(jQuery);
