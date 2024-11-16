jQuery(document).ready(function($) {
    var isUpdating = false; // Флаг, чтобы предотвратить зацикливание

    function updateLiftOption(recalculate = true) {
        if (isUpdating) return; // Если обновление уже идет, выходим из функции
        isUpdating = true;

        var liftOption = $('input[name="cf_lift_option"]:checked').val();
        var floorCount = parseInt($('input[name="cf_floor_count"]').val(), 10) || 0;

        if (!liftOption) {
            console.error('No lift option selected');
            isUpdating = false;
            return;
        }

        $.ajax({
            type: 'POST',
            url: cf_ajax_object.ajax_url,
            data: {
                action: 'cf_update_lift_option',
                lift_option: liftOption,
                floor_count: floorCount,
            },
            success: function(response) {
                if (response.success) {
                    $('.cf-cost-amount').text(response.data.lift_cost);
                    $('input[name="cf_lift_cost"]').val(response.data.lift_cost);

                    // Обновление текста в предварительных итогах
                    if (liftOption === 'lift') {
                        $('.cf-lift-option-label').text('Требуется подъем на этаж, грузового лифта нет');
                        $('.cf-floor-count-label').text('Количество этажей: ' + floorCount);
                    } else {
                        $('.cf-lift-option-label').text('Без подъема на этаж или есть грузовой лифт');
                        $('.cf-floor-count-label').text(''); // Очищаем текст, если выбран "no_lift"
                    }

                    if (recalculate) {
                        $('body').trigger('update_checkout'); // Пересчет итогов вызывается только один раз
                    }
                }
                isUpdating = false; // Сбрасываем флаг после завершения обновления
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                isUpdating = false;
            }
        });
    }

    function toggleFloorDetails() {
        var liftOption = $('input[name="cf_lift_option"]:checked').val();

        if (liftOption === 'lift') {
            $('#cf_floor_details').removeClass('hidden'); // Показываем поле выбора количества этажей
            console.log('Showing floor count input');
        } else {
            $('#cf_floor_details').addClass('hidden'); // Скрываем поле выбора этажей
            $('.cf-cost-amount').text('0');
            $('input[name="cf_lift_cost"]').val('0');
            console.log('Hiding floor count input');
        }
    }

    $('input[name="cf_lift_option"]').change(function() {
        toggleFloorDetails(); // Переключаем видимость блока с выбором этажей
        updateLiftOption();   // Обновляем стоимость и предварительные итоги
    });

    $('input[name="cf_floor_count"]').change(function() {
        updateLiftOption();   // Обновляем стоимость при изменении количества этажей
    });

    // Инициализация при загрузке страницы
    toggleFloorDetails();
    updateLiftOption(false); // Обновляем без пересчета

    // Обнуляем сумму при загрузке страницы
    $('.cf-cost-amount').text('0');
    $('input[name="cf_lift_cost"]').val('0');

    // Обновляем значения только один раз после первого пересчета
    $('body').on('updated_checkout', function() {
        if (!isUpdating) {
            updateLiftOption(false); // Обновляем без повторного пересчета
        }
    });
});
