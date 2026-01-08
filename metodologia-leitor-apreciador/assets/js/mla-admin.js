/**
 * JavaScript Admin - Metodologia Leitor-Apreciador
 *
 * @package MetodologiaLeitorApreciador
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Confirmação de exclusão
        $('.button-link-delete').on('click', function () {
            return confirm(mlaAdmin.i18n.confirmDelete);
        });

        // Auto-dismiss notices
        setTimeout(function () {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
    });

})(jQuery);
