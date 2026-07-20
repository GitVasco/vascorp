<?php

const DASHBOARD_CXC_VENDEDORES_INCOBRABLES = array(
    '00A',
    '01',
    '03',
    '05A',
    '07A',
    '14',
    '15',
);

/**
 * @return string[]
 */
function dashboardCxcVendedoresIncobrables()
{
    return DASHBOARD_CXC_VENDEDORES_INCOBRABLES;
}

function dashboardCxcSqlInIncobrables($alias = 'cc', $prefix = 'cxc_inc')
{
    $partes = array();

    foreach (DASHBOARD_CXC_VENDEDORES_INCOBRABLES as $i => $codigo) {
        $partes[] = ':' . $prefix . '_' . $i;
    }

    return $alias . '.vendedor IN (' . implode(', ', $partes) . ')';
}

/**
 * Lista fija de incobrables: seguro para SQL literal (no es input de usuario).
 */
function dashboardCxcSqlInIncobrablesLiterals($alias = 'cc')
{
    $codigos = array();

    foreach (DASHBOARD_CXC_VENDEDORES_INCOBRABLES as $codigo) {
        $codigos[] = "'" . str_replace("'", "''", $codigo) . "'";
    }

    return $alias . '.vendedor IN (' . implode(', ', $codigos) . ')';
}

function dashboardCxcBindIncobrables(PDOStatement $stmt, $prefix = 'cxc_inc')
{
    foreach (DASHBOARD_CXC_VENDEDORES_INCOBRABLES as $i => $codigo) {
        $stmt->bindValue(':' . $prefix . '_' . $i, $codigo, PDO::PARAM_STR);
    }
}

function dashboardCxcAnnosPermitidos()
{
    return array(2025, 2026);
}
