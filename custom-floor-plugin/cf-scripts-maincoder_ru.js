jQuery(document).ready(function($) {
    // Функция для обновления выбранного варианта подъема на этаж
    function updateLiftOption() {
        var liftOption = $('input[name="cf_lift_option"]:checked').val();
        var floorCount = $('input[name="cf_floor_count"]').val();

        if (!liftOption) {
            console.error('No lift option selected');
            return;
        }

        console.log('Lift option selected:', liftOption);

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
                    $('body').trigger('update_checkout');
                    $('.cf-cost-amount').text(response.data.lift_cost);
                    $('input[name="cf_lift_cost"]').val(response.data.lift_cost);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    }

    // Обработчик изменения значения радиокнопок
    $('input[name="cf_lift_option"]').change(function() {
        var liftOption = $(this).val(); // Получаем новое выбранное значение
        console.log('Radio button changed:', liftOption); // Логируем выбранное значение

        if (liftOption === 'lift') {
            $('#cf_floor_details').removeClass('hidden'); // Показываем поле выбора количества этажей
            console.log('Showing floor count input');
        } else {
            $('#cf_floor_details').addClass('hidden'); // Скрываем поле выбора этажей
            $('.cf-cost-amount').text('0');
            $('input[name="cf_lift_cost"]').val('0');
            console.log('Hiding floor count input');
        }

        updateLiftOption(); // Вызываем функцию для обновления стоимости
    });

    // Обработчик изменения значения поля количества этажей
    $('input[name="cf_floor_count"]').change(function() {
        updateLiftOption(); // Вызываем функцию для обновления стоимости при изменении количества этажей
    });

    // Инициализация при загрузке страницы
    var initialLiftOption = $('input[name="cf_lift_option"]:checked').val();
    console.log('Initial lift option on page load:', initialLiftOption); // Логируем значение радиокнопки при загрузке

    if (initialLiftOption === 'lift') {
        $('#cf_floor_details').removeClass('hidden');
        console.log('Floor count input should be visible on page load');
    } else {
        $('#cf_floor_details').addClass('hidden');
        console.log('Floor count input should be hidden on page load');
    }
});
