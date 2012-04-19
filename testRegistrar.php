<?php

$db_conn = odbc_connect('RIGEL-TEST', 'ccle_dev', 'KK3456Qwd') or die( "ERROR: C
onnection to Registrar failed.");

$result = odbc_exec ($db_conn, "EXECUTE ucla_getterms '12S'");
while (odbc_fetch_into($result, $row))
{
    print_r($row);
}

?>