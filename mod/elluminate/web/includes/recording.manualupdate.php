<?php
$manualupdate = optional_param('manualupdate', 0, PARAM_INT);

if ($manualupdate){
   $detailViewHelper->doManualUpdate();
}