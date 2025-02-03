document.addEventListener('DOMContentLoaded', function () {
    const mainLogo = document.querySelector('.logo .main-logo:not(.sticky-logo)');
    const stickyLogo = document.querySelector('.logo .main-logo.sticky-logo');
    const headerElement = document.querySelector('header.styler-header-default');
    const elementorDefaultLogo = stylerSettings.elementor_default_logo;
    const elementorStickyLogo = stylerSettings.elementor_sticky_logo;
    const stickyLogoSrc = stylerSettings.sticky_logo;
    const defaultLogoSrc = stylerSettings.default_logo;

    if (!mainLogo || !stickyLogo || !headerElement) {
        console.warn('Logos or header element not found!');
        return;
    }

    // Устанавливаем начальный логотип для страницы /shop/ и товаров
    if (stylerSettings.is_shop_page || stylerSettings.is_product_page) {
        mainLogo.src = stickyLogoSrc; // Цветной логотип
        stickyLogo.src = stickyLogoSrc; // Цветной логотип для sticky-режима
        mainLogo.style.display = 'block';
        stickyLogo.style.display = 'none';
        console.log('Sticky logo applied for shop and product pages');
        return; // Завершаем, чтобы логика не переписывала логотип
    }

    // Устанавливаем начальный логотип для страниц блога без Elementor
    if (stylerSettings.is_blog_post && !(elementorDefaultLogo || elementorStickyLogo)) {
        mainLogo.src = stickyLogoSrc; // Цветной логотип
        stickyLogo.src = stickyLogoSrc; // Цветной логотип для sticky-режима
        mainLogo.style.display = 'block';
        stickyLogo.style.display = 'none';
        console.log('Sticky logo applied for blog post without Elementor');
        return; // Завершаем, чтобы логика не переписывала логотип
    }

    // Устанавливаем начальный логотип для страниц с Elementor
    if (elementorDefaultLogo || elementorStickyLogo) {
        mainLogo.src = elementorDefaultLogo || defaultLogoSrc; // Основной логотип из Elementor или fallback
        stickyLogo.src = elementorStickyLogo || stickyLogoSrc; // Sticky-логотип из Elementor или fallback
        mainLogo.style.display = 'block';
        stickyLogo.style.display = 'none';
        console.log('Elementor logos applied with fallback to plugin settings');
    } else {
        mainLogo.src = defaultLogoSrc; // Белый логотип по умолчанию
        stickyLogo.src = stickyLogoSrc;
    }

    // Обработчик для sticky-режима
    const handleStickyState = () => {
        if (headerElement.classList.contains('sticky-start')) {
            mainLogo.style.display = 'none';
            stickyLogo.style.display = 'block';
        } else {
            mainLogo.style.display = 'block';
            stickyLogo.style.display = 'none';
        }
    };

    // Следим за изменением sticky-класса
    const observer = new MutationObserver(() => handleStickyState());
    observer.observe(headerElement, { attributes: true });

    handleStickyState(); // Инициализация при загрузке

    // Логика наведения на меню
    const menuItems = document.querySelectorAll('.styler-header-top-menu-area .menu-item');
    menuItems.forEach(menuItem => {
        menuItem.addEventListener('mouseenter', function () {
            mainLogo.style.display = 'none';
            stickyLogo.style.display = 'block';
        });

        menuItem.addEventListener('mouseleave', function () {
            handleStickyState(); // Возвращаем состояние после наведения
        });
    });
});