<div class="row dc-graficos-cobranzas">
    <?php
    include __DIR__ . "/grafico-cobranza-dia.php";
    include __DIR__ . "/grafico-cobranza-semana.php";
    include __DIR__ . "/grafico-cobranza-dia-semana.php";
    ?>
</div>

<style>
    @media (min-width: 1200px) {
        .dc-graficos-cobranzas {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
        }

        .dc-graficos-cobranzas > [class*="col-"] {
            display: flex;
            flex-direction: column;
        }

        .dc-graficos-cobranzas .box {
            flex: 1;
            width: 100%;
            margin-bottom: 15px;
            min-height: 0;
        }

        .dc-graficos-cobranzas .box-body {
            overflow: visible;
        }
    }
</style>
