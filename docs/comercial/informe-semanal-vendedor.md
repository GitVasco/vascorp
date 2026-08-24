# Informe semanal por vendedor

Vista imprimible (A4) para gerencia: un vendedor por hoja.

- Ruta: `index.php?ruta=informe-semanal-vendedor`
- Permiso: `gestion_comercial` → `informe_semanal_vendedor` → `ver`
- En pantalla: elegir semana y vendedor (solo activos), luego **Imprimir / PDF**

## Criterios (alineados a dashboards)

- **Ventas:** `ventajf` tipos S02/S03/S70/E05/S05, no anuladas, `neto`
- **Pedidos:** documentos distintos de esas ventas
- **Clientes con compra:** clientes distintos con venta en la semana
- **Nuevos:** primera venta (esos tipos) en la semana
- **Cartera de clientes:** `clientesjf` asignados al vendedor (activos)
- **Cobranza:** misma fuente que dashboard gerencial (efectivo, sin IGV). En este informe se suman cobranzas de códigos anteriores: 00→31, 24 y 32→33, 26 y 26A→30. Las ventas no se remapean.
- **Cartera por tramos:** misma lógica que dashboard CxC, corte = domingo de la semana
- **Semana:** lunes a domingo (ISO). Gráficos diarios de ventas y cobranza (esta semana vs. anterior).
- Lectura, plan y observaciones se arman con reglas (no se editan aún)
