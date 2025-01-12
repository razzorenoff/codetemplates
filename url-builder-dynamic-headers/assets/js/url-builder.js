jQuery(document).ready(function ($) {
    if (window.location.pathname.indexOf('/bildungsangebot/') !== 0) {
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

        // Формируем URL в формате /bildungsangebot/ebenen/themengebiet
        const urlPath = [
            '/bildungsangebot',
            ...filterEbenen,
            ...filterThemengebiet,
        ].join('/');

        console.log('New URL:', urlPath);

        // Обновляем URL
        window.history.pushState({}, '', urlPath);

        // Формируем новый заголовок
        if (filterEbenen.length || filterThemengebiet.length) {
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

        // Удаляем параметры из URL, возвращаем только /bildungsangebot/
        window.history.replaceState({}, '', '/bildungsangebot/');

        // Восстанавливаем исходный заголовок
        titleContainer.html(initialTitle);

        // Удаляем класс из контейнера заголовка
        headerWrapper.removeClass('filters-applied');

        console.log('Filters and URL reset successfully.');
    }

    function initFiltersFromURL() {
        const pathParts = window.location.pathname.split('/').filter(Boolean); // Разделяем путь
        const pageIndex = pathParts.indexOf('bildungsangebot');

        if (pageIndex === -1) {
            return;
        }

        const filters = pathParts.slice(pageIndex + 1); // Берем всё, что идет после /bildungsangebot/

        console.log('Filters from URL:', filters);

        checkboxes.each(function () {
            const value = $(this).val();
            if (filters.includes(value)) {
                $(this).prop('checked', true);
            }
        });

        // Только обновляем заголовок, если есть параметры
        if (filters.length) {
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