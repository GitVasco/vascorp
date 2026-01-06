$(".tablaCuentas, .tablaCuentasPaginado").on("click", ".btnCancelacionDirecta", function () {
    const $button = $(this); // Guardar referencia al botón
    const idCta = $(this).attr("idCta");
    const tipo_doc = $(this).attr("tipo_doc");
    const num_cta = $(this).attr("num_cta");
    const cliente = $(this).attr("cliente");
    const vendedor = $(this).attr("vendedor");
    const monto = $(this).attr("monto");
    let saldo = parseFloat($button.attr("saldo")); // Convertir saldo a número
    const fecha = $(this).attr("fecha");
    const fecha_ven = $(this).attr("fecha_ven");
    const doc_origen = $(this).attr("doc_origen");
    const fechaActual = new Date().toISOString().split("T")[0];

    // Primera alerta de confirmación
    swal({
        title: "¿Deseas confirmar la cancelación?",
        text: "Esta acción es irreversible.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, confirmar",
        cancelButtonText: "No, cancelar",
    }).then(function (result) {
        if (result.value) {
            // Segunda alerta para ingresar los datos
            swal({
                title: "Ingresa los detalles de la cancelación",
                html: $(
                    `<div>
                        <label for="codigoCancelacion">Código de Cancelación:</label>
                        <select id="codigoCancelacion" class="swal2-input" required>
                            <option value="">Selecciona un código</option>
                            <option value="00">00 - LETRAS BCP</option>
                            <option value="02">02 - RECIBOS POR HONORARIOS</option>
                            <option value="04">04 - CONTROL INTERNO-SALIDA ALMACEN</option>
                            <option value="05">05 - DEP. CTACTE</option>
                            <option value="06">06 - POS-BCP</option>
                            <option value="10">10 - DESCUENTO ADICIONAL PROFORMAS</option>
                            <option value="11">11 - PROMOCION VENTA DE BRASIERES</option>
                            <option value="12">12 - TICKET O CINTA EMITIDA POR MA</option>
                            <option value="13">13 - DEVOL. PROFORMAS</option>
                            <option value="14">14 - DEP. CULQUI-BCP</option>
                            <option value="15">15 - YAPE-BCP</option>
                            <option value="16">16 - VENDEMAS - BCP</option>
                            <option value="17">17 - POS-SCOTIABANK</option>
                            <option value="18">18 - VENDE MAS -SCOTIABANK</option>
                            <option value="70">70 - ENTREGA DE EFECTIVO</option>
                            <option value="71">71 - PAGOS Y GASTOS</option>
                            <option value="72">72 - CAJA CHICA DIA ANTERIOR</option>
                            <option value="80">80 - EFECTIVO</option>
                            <option value="81">81 - TARJETA DE CREDITO</option>
                            <option value="82">82 - ABONO EN CTA. S/.</option>
                            <option value="83">83 - ABONO EN CTA. US$</option>
                            <option value="84">84 - CHEQUE</option>
                            <option value="85">85 - LETRAS</option>
                            <option value="95">95 - MUESTRAS DE VITRINAS/PANELES</option>
                            <option value="96">96 - NOTA DE CREDITO DEVOLUCION</option>
                            <option value="97">97 - NOTA DE CREDITO PRONTO PAGO</option>
                            <option value="98">98 - AJUSTE DE CUENTAS NO EFECTIVO</option>
                            <option value="99">99 - PERDIDA POR ROBO</option>
                            <option value="RF">RF - REFINANCIACION</option>
                            <option value="TR">TR - TELECREDITO</option>
                        </select>
                        <label for="fecha">Fecha:</label>
                        <input type="date" id="fecha" class="swal2-input" placeholder="YYYY-MM-DD" value='${fechaActual}'>
                        <label for"nota">Nota:</label>
                        <input type="text" id="nota" class="swal2-input" placeholder="Nota">
                        <label for="monto">Monto:</label>
                        <input type="number" step="any" id="monto" class="swal2-input" placeholder="Monto" max='${saldo}' min='0' value='${saldo}'>
                    </div>`
                ),

                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: "Confirmar",
                cancelButtonText: "Cancelar",
                preConfirm: () => {
                    const codigoCancelacion = $("#codigoCancelacion").val();
                    const fechaCancelacion = $("#fecha").val();
                    const montoCancelacion = $("#monto").val();
                    const notaCancelacion = $("#nota").val();
                    if (
                        !codigoCancelacion ||
                        !fechaCancelacion ||
                        !montoCancelacion ||
                        !notaCancelacion
                    ) {
                        toastr["error"]("Debes completar todos los campos.");
                        return false;
                    }
                    return {
                        codigoCancelacion: codigoCancelacion,
                        fechaCancelacion: fechaCancelacion,
                        montoCancelacion: montoCancelacion,
                        notaCancelacion: notaCancelacion,
                    };
                },
            }).then((result) => {
                if (result.value) {
                    console.log("Datos de Cancelación:", result.value);
                    const {
                        codigoCancelacion,
                        fechaCancelacion,
                        montoCancelacion,
                        notaCancelacion,
                    } = result.value;
                    console.log("Código de Cancelación:", codigoCancelacion);
                    console.log("Fecha de Cancelación:", fechaCancelacion);
                    console.log("Monto de Cancelación:", montoCancelacion);
                    console.log("Nota de Cancelación:", notaCancelacion);

                    const formdata = new FormData();
                    formdata.append("idCta", idCta);
                    formdata.append("tipo_doc", tipo_doc);
                    formdata.append("num_cta", num_cta);
                    formdata.append("cliente", cliente);
                    formdata.append("vendedor", vendedor);
                    formdata.append("monto", monto);
                    formdata.append("saldo", saldo);
                    formdata.append("fecha", fecha);
                    formdata.append("fecha_ven", fecha_ven);
                    formdata.append("doc_origen", doc_origen);
                    formdata.append("codigoCancelacion", codigoCancelacion);
                    formdata.append("fechaCancelacion", fechaCancelacion);
                    formdata.append("montoCancelacion", montoCancelacion);
                    formdata.append("notaCancelacion", notaCancelacion);

                    // Aquí puedes enviar los datos usando AJAX
                    $.ajax({
                        url: "ajax/cuentas.ajax.php",
                        method: "POST",
                        data: formdata,
                        cache: false,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                        success: function (respuesta) {
                            //console.log(respuesta);
                            console.log("🚀 ~ respuesta:", respuesta);

                            if (respuesta === "ok") {
                                const saldoActual =
                                    parseFloat(saldo) -
                                    parseFloat(montoCancelacion);
                                console.log("🚀 ~ saldoActual:", saldoActual);

                                toastr["success"](
                                    "La cancelación se realizó con éxito."
                                );

                                if (saldoActual == 0) {
                                    console.log("🚀 ~ saldo:", saldo);
                                    $button.removeClass(
                                        "btnCancelacionDirecta btn-danger"
                                    );
                                    $button.addClass("btn-success");
                                    $button.html("CANCELADO");
                                }

                                // Actualiza el saldo en la fila correspondiente
                                const row = $button.closest("tr");
                                const saldoCell = row.find("td").eq(7); // Suponiendo que la columna de saldo es la 8ª columna (índice 7)
                                saldoCell.text(saldoActual.toFixed(2));

                                // Actualiza el atributo saldo del botón
                                $button.attr("saldo", saldoActual);
                            } else {
                                toastr["error"](
                                    "Ocurrió un error al intentar cancelar la cuenta."
                                );
                            }
                        },
                    });
                }
            });
        }
    });
});
