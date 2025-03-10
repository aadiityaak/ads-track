jQuery(document).ready(function ($) {
    // Deteksi klik pada tombol WhatsApp
    $(document).on('click', 'a[href*="wa.me"], a[href*="api.whatsapp.com"]', function (e) {
        e.preventDefault(); // Cegah tindakan default sementara

        // Pastikan elemen <a> yang sesuai
        var $link = $(e.target).closest('a');
        var whatsappLink = $link.attr('href');

        if (!whatsappLink) {
            console.error('URL WhatsApp tidak ditemukan.');
            return;
        }

        // Kirim permintaan AJAX ke server
        $.post(velocityAdsTrackAjax.ajaxurl, {
            action: 'track_whatsapp_click'
        }, function (response) {
            // Redirect ke WhatsApp setelah data disimpan
            window.location.href = whatsappLink;
        });
    });
});