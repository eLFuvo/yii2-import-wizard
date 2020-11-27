/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

(function ($) {
    var $importProgressContainer = $('.import-progress-container'),
        $form = $('.import-form'),
        $setupForm = $('.setup-import-form'),
        model = $setupForm.data('model'),
        mapAttribute = window.localStorage.getItem('import-' + model),
        startRowIndex = window.localStorage.getItem('startRow-' + model),
        identity = window.localStorage.getItem('identity-' + model);
    if ($setupForm.length && mapAttribute) {
        mapAttribute = JSON.parse(mapAttribute);
        Object.keys(mapAttribute).forEach(function (id) {
            const item = mapAttribute[id],
                $attr = $setupForm.find('.attribute[data-id="' + item.id + '"] select'),
                $cast = $setupForm.find('.type[data-id="' + item.id + '"] select');
            if ($attr.length) {
                $attr.val(item.attribute).trigger('change');
                setTimeout(function (elem) {
                    $cast.val(elem.castTo).trigger('change')
                }, 50, item);
            }
        });
        if (startRowIndex > 0) {
            $setupForm.find('.start-row-index').val(startRowIndex);
        }
        if (identity) {
            identity = JSON.parse(identity);
            $setupForm.find('.identity input').prop('checked', false)
            identity.forEach(function (id) {
                $setupForm.find('.identity input[data-id="' + id + '"]')
                    .prop('checked', true)
                    .trigger('change');
            });
        }
    } else {
        mapAttribute = {};
    }
    $setupForm.find('.map-attribute').on('change', function () {
        const attribute = $(this).val(),
            id = $(this).closest('td').data('id'),
            $selectCastTo = $setupForm.find('.type[data-id="' + id + '"] select');
        let castTo = $(this).find('option:selected').data('type');
        if (castTo <= '') {
            castTo = 'string';
        }
        if ($selectCastTo.length) {
            $selectCastTo.val(castTo).trigger('change');
        }
        mapAttribute[id] = {
            id: id,
            attribute: attribute,
            castTo: castTo
        };
        window.localStorage.setItem('import-' + model, JSON.stringify(mapAttribute));
    });
    $setupForm.find('.start-row-index').on('input change', function () {
        window.localStorage.setItem('startRow-' + model, $(this).val());
    });
    $setupForm.find('.identity input').on('change', function () {
        let columns = [];
        $setupForm.find('.identity input:checked').each(function (index, input) {
            columns.push($(input).data('id'));
        });
        window.localStorage.setItem('identity-' + model, JSON.stringify(columns));
    });
    $setupForm.find('button[type="reset"]').on('click', function () {
        $setupForm.find('.map-attribute').val('').trigger('change');
        // $setupForm.find('select.type').val('string').trigger('change');
        $setupForm.find('input.identity').prop('checked', false).trigger('change');
        $setupForm.find('input.start-row-index').val(2);

        window.localStorage.removeItem('import-' + model);
        window.localStorage.removeItem('startRow-' + model);
        window.localStorage.removeItem('identity-' + model);
    });
    if ($importProgressContainer.length) {
        $(window).on('import.stat.reload', function () {
            $importProgressContainer.load($importProgressContainer.data('url'), function (content) {
                if (/Not Found/.test(content)) {
                    return;
                }
                if ($importProgressContainer.find('.import-stat').length
                    && $importProgressContainer.find('.import-done').length === 0) {
                    $form.hide();
                    setTimeout(function () {
                        $(window).trigger('import.stat.reload');
                    }, 2000);
                }
            });
        });
        $(window).trigger('import.stat.reload');
    }
})(jQuery);
