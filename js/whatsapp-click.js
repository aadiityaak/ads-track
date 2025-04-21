jQuery(document).ready(function ($) {
  // Deteksi klik pada tombol WhatsApp
  $(document).on(
    "click",
    'a[href*="wa.me"], a[href*="api.whatsapp.com"]',
    function (e) {
      e.preventDefault(); // Cegah tindakan default sementara

      var $link = $(e.target).closest("a");
      var whatsappLink = $link.attr("href");

      if (!whatsappLink) {
        console.error("URL WhatsApp tidak ditemukan.");
        return;
      }

      // Kirim permintaan AJAX ke server
      $.post(
        velocityAdsTrackAjax.ajaxurl,
        {
          action: "track_whatsapp_click",
        },
        function (response) {
          if (response && response.success) {
            // Tunggu sejenak lalu redirect jika respon sukses
            setTimeout(function () {
              window.location.href = whatsappLink;
            }, 300);
          } else {
            // Logging error jika gagal
            console.error("Gagal merekam klik WhatsApp:", response.message);
          }
        }
      ).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX gagal:", textStatus, errorThrown);
      });
    }
  );
});
