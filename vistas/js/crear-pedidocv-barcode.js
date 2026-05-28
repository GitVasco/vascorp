/**
 * Lectura código de barras — vista dedicada `escaneo-barcode-pedidocv`.
 * Normalización:
 * - Empieza con letra (ej. RL271013): lectura completa del escáner.
 * - Empieza con 0 (ej. 0057032): "1" + todos los dígitos → 10057032.
 * - Otros numéricos: primeros 7 dígitos.
 * Incrementa cantidad (+1 por lectura) en detalle_temporal usando articulojf.articulo como SKU.
 * Opcional: también refresca bloques #updDiv del formulario Crear pedido si están en la misma página.
 * No depende del flujo modelo + modal en pedidoscv.js.
 */
(function ($) {
  "use strict";

  function refrescarAreasPedidoCv() {
    if ($("#updDiv").length) {
      $("#updDiv").load(" #updDiv");
    }
    if ($("#updDivC").length) {
      $("#updDivC").load(" #updDivC");
    }
    if ($("#updDivB").length) {
      $("#updDivB").load(" #updDivB");
    }
  }

  var enviando = false;

  /**
   * Referencia local (la normalización oficial la hace el servidor).
   */
  function normalizarCodigoBarcodePedCv(scan) {
    var limpio = $.trim(scan || "").replace(/[\u000a\u000d]/g, "");
    if (!limpio) {
      return "";
    }
    if (/^[a-zA-Z]/.test(limpio)) {
      return limpio;
    }
    var soloDigitos = (limpio.match(/\d/g) || []).join("");
    if (soloDigitos.length === 0) {
      return limpio;
    }
    if (soloDigitos.charAt(0) === "0") {
      return "1" + soloDigitos;
    }
    return soloDigitos;
  }

  function enviarBarcode() {
    var $inp = $("#inputBarcodePedCv");
    var raw = $.trim(($inp.val() || "").replace(/[\u000a\u000d]/g, ""));
    if (!raw || enviando) {
      return;
    }

    var pedido = (
      $("#escaneoPedidoCodigoActivo").val() ||
      $("#formEscaneoNuevoCodigo").val() ||
      $("#nuevoCodigo").val() ||
      ""
    )
      .toString()
      .trim();
    if (!pedido) {
      Command: toastr["error"]("No hay código de pedido.");
      $inp.val("");
      return;
    }

    enviando = true;

    var datos = new FormData();
    datos.append("barcodePedidoCvAccion", "1");
    datos.append("articuloBarcodeCv", raw);
    datos.append("pedidoBarcodeCv", pedido);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        if (res && res.ok === true) {
          var codigoUsado = res.codigo || raw;
          if (
            typeof res.advertencia === "string" &&
            res.advertencia.length > 0
          ) {
            Command: toastr["warning"](res.advertencia);
          }
          Command: toastr["success"](
            "Cantidad aumentada (+1). Código: " + codigoUsado
          );
          refrescarAreasPedidoCv();
          if (typeof window.escaneoCvRefrescarTotales === "function") {
            window.escaneoCvRefrescarTotales();
          }
          if (
            typeof window.escaneoCvRefrescarListadoArticulos === "function"
          ) {
            window.escaneoCvRefrescarListadoArticulos();
          }
        } else {
          Command: toastr["error"](
            res && res.mensaje ? res.mensaje : "No se pudo registrar la lectura."
          );
        }
      },
      error: function () {
        Command: toastr["error"]("Error de conexión al registrar el código.");
      },
      complete: function () {
        enviando = false;
        $inp.val("");
        window.setTimeout(function () {
          $inp.focus().select();
        }, 120);
      },
    });
  }

  $(function () {
    var $inp = $("#inputBarcodePedCv");
    if (!$inp.length) {
      return;
    }

    /* Los lectores suelen cerrar lectura con Enter */
    $inp.on("keydown", function (event) {
      if (event.keyCode === 13 || event.which === 13) {
        event.preventDefault();
        enviarBarcode();
      }
    });
  });
})(jQuery);
