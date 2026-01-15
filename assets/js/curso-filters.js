jQuery(function($) {
    'use strict';

    const cursoSearch = {
        init: function() {
            $('#categoria-filtro').on('change', this.filterByCategory.bind(this));
            $('#curso-search').on('keyup', this.searchCourses.bind(this));
        },

        filterByCategory: function() {
            const category = $('#categoria-filtro').val();
            if (category) {
                const baseUrl = window.location.origin + '/categoria-curso/' + category + '/';
                window.location.href = baseUrl;
            } else {
                window.location.href = window.location.origin + '/curso/';
            }
        },

        searchCourses: function() {
            const searchTerm = $('#curso-search').val().toLowerCase();
            const cards = $('.course-card');
            let visibleCount = 0;

            cards.each(function() {
                const title = $(this).find('.course-title').text().toLowerCase();
                const shortname = $(this).find('.course-shortname').text().toLowerCase();
                
                if (title.includes(searchTerm) || shortname.includes(searchTerm)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            // Show/hide no results message
            if (visibleCount === 0 && searchTerm.length > 0) {
                if ($('.no-courses-found').length === 0) {
                    $('.courses-grid').html(
                        '<div class="no-courses-found"><p>' +
                        'Nenhum curso encontrado para sua busca.' +
                        '</p></div>'
                    );
                }
            } else if (searchTerm.length === 0) {
                location.reload();
            }
        }
    };

    cursoSearch.init();
});
