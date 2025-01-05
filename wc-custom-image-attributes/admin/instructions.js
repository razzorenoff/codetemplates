jQuery(document).ready(function ($) {
    // Открытие попапа
    $('#wc-custom-instructions-btn').on('click', function () {
        $('#wc-custom-instructions-popup').fadeIn();
    });

    $('#wc-custom-popup-close').on('click', function () {
        $('#wc-custom-instructions-popup').fadeOut();
    });

    // Закрытие попапа при клике вне окна
    $(document).on('click', function (e) {
        if ($(e.target).is('#wc-custom-instructions-popup')) {
            $('#wc-custom-instructions-popup').fadeOut();
        }
    });

    // Массовое обновление товаров
    $('#wc-custom-bulk-update').on('click', function () {
        if (!confirm('Вы уверены, что хотите обновить все товары? Это может занять некоторое время.')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wc_custom_bulk_update'
            },
            success: function (response) {
                alert('Товары успешно обновлены.');
            },
            error: function () {
                alert('Ошибка при обновлении товаров.');
            }
        });
    });
});
