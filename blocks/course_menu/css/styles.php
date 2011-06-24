<?php

?>
<style type="text/css">
td#chaptersTableContainer table tr td {
    padding: 2px 6px;
    border: 1px solid #000;
}
a.enableDisable {
    text-decoration: none !important;
}
a.showHide {
    display: block;
    padding-left: 20px;
}
a.showHide.minus {
    background: url("<?php echo $OUTPUT->pix_url('t/expanded') ?>") right center no-repeat;
    padding-left: 0;
    padding-right: 20px;
}

a.showHide.plus {
    background: url("<?php echo $OUTPUT->pix_url('t/collapsed') ?>") left center no-repeat;
}

.showHideCont {
    float: right;
    margin-right: 50px;
}
</style>
