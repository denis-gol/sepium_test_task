(function ($) {
    'use strict';

    // Выбор категории в товаре и обновление блока характеристик.
    $('body').on('change', '.js-category', function () {
        var category = [];
        var $properties = $('.property_all');

        // создаем объект для хранения данных
        var savedStates = {};

        $properties.find('.property-field').each(function() {
            var $field = $(this);
            var propId = $field.data('property-id');

            // обрабатываем чекбоксы
            var $checkboxGrid = $field.find('.checkbox_property');
            if ($checkboxGrid.length > 0) {
                var checkedVals = [];

                // перебираем каждый чекбокс в сетке
                $checkboxGrid.find('label.line_chek').each(function() {
                    var $label = $(this);
                    if ($label.find('input[type="checkbox"]').is(':checked')) {
                        var val = $label.find('.ckeck_param').data('val');
                        if (val !== undefined) {
                            checkedVals.push(val);
                        }
                    }
                });

                if (checkedVals.length > 0) {
                    savedStates[propId] = { type: 'checkbox', value: checkedVals };
                }
                return;
            }

            // обрабатываем обычные поля (input, select)
            var $input = $field.find('input.ag_pole_good, select.ag_pole_good');
            if ($input.length > 0) {
                var val = $input.val();
                if (val !== undefined && val !== null && val !== '') {
                    savedStates[propId] = { type: 'text_or_select', value: val };
                }
            }
        });

        $(this).closest('.add_good_name_category')
            .toggleClass('category_checked is-selected', this.checked);

        $('.category_checked').each(function () {
            category[category.length] = $(this).attr('data-category-id');
        });

        $properties.addClass('is-loading').attr('aria-busy', 'true');

        $.ajax({
            type: 'POST',
            url: './admin/ajax/property/Refresh_Property_Good.php',
            dataType: 'html',
            data: { category: category },
            success: function (data) {
                if (data != 'no') {
                    $properties.html(data);

                    // восстанавливаем сохраненные значения в форме
                    $.each(savedStates, function(propId, savedInfo) {

                        var $field = $properties.find('.property-field[data-property-id="' + propId + '"]');
                        if ($field.length === 0) return; // если свойство исчезло в новом HTML, то пропускаем

                        if (savedInfo.type === 'checkbox') {
                            // Восстанавливаем чекбоксы
                            var valuesToCheck = savedInfo.value; // Массив сохраненных ID

                            $field.find('.checkbox_property label.line_chek').each(function() {
                                var $label = $(this);
                                var currentVal = $label.find('.ckeck_param').data('val');

                                // Если этот data-val был сохранен, отмечаем чекбокс галочкой
                                if (valuesToCheck.indexOf(currentVal) !== -1) {
                                    $label.find('input[type="checkbox"]').prop('checked', true);
                                }
                            });
                        } else if (savedInfo.type === 'text_or_select') {
                            // Восстанавливаем input/select
                            $field.find('input.ag_pole_good, select.ag_pole_good').val(savedInfo.value);
                        }

                    });
                }
            },
            error: function () {
                $properties.html('<div class="error-state">Не удалось обновить характеристики.</div>');
            },
            complete: function () {
                $properties.removeClass('is-loading').attr('aria-busy', 'false');
            }
        });
    });
}(jQuery));
