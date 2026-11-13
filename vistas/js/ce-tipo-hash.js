/*
 * Comprobantes especiales → Nuevo comprobante: recuerda el tipo elegido en la URL.
 * Gratuitas: #tipo-<VALOR>. Exportación: #exportacion o #exportacion-<pedido> (lo maneja exportacion-crear.js).
 */
$(function () {
    "use strict";

    var $tipo = $("#ceTipo");
    if (!$tipo.length) return;

    var EXPORTACION = "EXPORTACION";
    var m = String(window.location.hash || "").match(/^#tipo-(.+)$/);
    var pendiente = m ? decodeURIComponent(m[1]) : "";

    function ponerHash(h) {
        if (window.location.hash === h) return;
        if (window.history && history.replaceState) {
            history.replaceState(null, "", window.location.pathname + window.location.search + h);
        } else {
            window.location.hash = h;
        }
    }

    $tipo.on("change", function () {
        var v = String($tipo.val() || "");
        if (!v) return;
        if (v === EXPORTACION) {
            if (!/^#exportacion(-\d+)?$/.test(window.location.hash)) ponerHash("#exportacion");
        } else {
            ponerHash("#tipo-" + encodeURIComponent(v));
        }
    });

    // El selector se llena por ajax (catálogos); aplicar el tipo de la URL cuando ya tenga opciones
    $(document).ajaxComplete(function (e, xhr, s) {
        if (!pendiente || !s || !/comprobantes-especiales\.ajax\.php/.test(s.url || "") || !/accion=catalogos/.test(s.url || "")) return;
        setTimeout(function () {
            if (!pendiente || !$tipo.find("option").filter(function () { return this.value === pendiente; }).length) return;
            var v = pendiente;
            pendiente = "";
            if ($tipo.val() !== v) {
                $tipo.val(v).selectpicker("refresh").trigger("change");
            }
        }, 0);
    });
});
