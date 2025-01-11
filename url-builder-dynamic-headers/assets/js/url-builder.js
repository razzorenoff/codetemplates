jQuery(document).ready(function ($) {
    if (window.location.pathname !== '/bildungsangebot/') {
        console.log('Not on the Bildungsangebot page. Exiting...');
        return;
    }

    const checkboxes = $('input[name="filter_terms_ebenen[]"], input[name="filter_terms_themengebiet[]"]');
    const titleContainer = $('.all_title');
    const headerWrapper = $('.all_header_wrapper .all_header_inner');
    const resetButton = $(document).on('click', '.filter_update', function (e) {
        e.preventDefault();
        resetFilters();
    });

    // Сохраняем исходный заголовок
    const initialTitle = titleContainer.html();

    function updateURLAndTitle() {
        const params = new URLSearchParams();
        let filterEbenen = [];
        let filterThemengebiet = [];

        // Собираем выбранные фильтры
        $('input[name="filter_terms_ebenen[]"]:checked').each(function () {
            filterEbenen.push($(this).val());
        });

        $('input[name="filter_terms_themengebiet[]"]:checked').each(function () {
            filterThemengebiet.push($(this).val());
        });

        console.log('Selected Ebenen:', filterEbenen);
        console.log('Selected Themengebiet:', filterThemengebiet);

        // Обновляем параметры URL только если есть фильтры
        if (filterEbenen.length) {
            params.set('ebenen', filterEbenen.join(','));
        }
        if (filterThemengebiet.length) {
            params.set('themengebiet', filterThemengebiet.join(','));
        }

        const hasParams = params.toString();
        const newURL = hasParams ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
        console.log('New URL:', newURL);

        // Обновляем URL
        window.history.pushState({}, '', newURL);

        // Формируем новый заголовок
        if (hasParams) {
            const baseTitle = 'Bildungsangebot am ZbW:';
            const filtersText = [...filterEbenen, ...filterThemengebiet].join(', ');

            // Очищаем контейнер заголовка
            titleContainer.empty();

            // Добавляем <h1>
            const h1 = $('<h1>').text(baseTitle);
            titleContainer.append(h1);

            // Добавляем <h2> с выбранными фильтрами
            if (filtersText) {
                const h2 = $('<h2>').text(filtersText);
                titleContainer.append(h2);

                // Добавляем класс, если есть выбранные фильтры
                console.log('Adding filters-applied class to header wrapper.');
                headerWrapper.addClass('filters-applied');
            }
        } else {
            // Возвращаем исходный заголовок, если фильтры сброшены
            console.log('Restoring initial title.');
            titleContainer.html(initialTitle);
            headerWrapper.removeClass('filters-applied');
        }
    }

    function resetFilters() {
        console.log('Resetting filters...');

        // Сбрасываем все чекбоксы
        checkboxes.prop('checked', false);

        // Удаляем параметры из URL
        window.history.replaceState({}, '', window.location.pathname);

        // Восстанавливаем исходный заголовок
        titleContainer.html(initialTitle);

        // Удаляем класс из контейнера заголовка
        headerWrapper.removeClass('filters-applied');

        console.log('Filters and URL reset successfully.');
    }

    function initFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);
        const filterEbenen = params.get('ebenen') ? params.get('ebenen').split(',') : [];
        const filterThemengebiet = params.get('themengebiet') ? params.get('themengebiet').split(',') : [];

        console.log('Filters from URL:', { filterEbenen, filterThemengebiet });

        checkboxes.each(function () {
            const value = $(this).val();
            if (filterEbenen.includes(value) || filterThemengebiet.includes(value)) {
                $(this).prop('checked', true);
            }
        });

        // Только обновляем заголовок, если есть параметры
        if (filterEbenen.length || filterThemengebiet.length) {
            updateURLAndTitle();
        }
    }

    // Обработчик изменения состояния фильтров
    checkboxes.on('change', function () {
        updateURLAndTitle();
    });

    // Инициализация при загрузке страницы
    initFiltersFromURL();
});
