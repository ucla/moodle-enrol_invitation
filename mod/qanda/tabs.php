<?php
    if (!isset($sortorder)) {
        $sortorder = '';
    }
    if (!isset($sortkey)) {
        $sortkey = '';
    }

    //make sure variables are properly cleaned
    $sortkey   = clean_param($sortkey, PARAM_ALPHA);// Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
    $sortorder = clean_param($sortorder, PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)

    $toolsrow = array();
    $browserow = array();
    $inactive = array();
    $activated = array();

    if (!has_capability('mod/qanda:answer', $context) && $tab == QANDA_APPROVAL_VIEW) {
    /// Non-teachers going to approval view go to defaulttab
        $tab = $defaulttab;
    }


    $browserow[] = new tabobject(QANDA_STANDARD_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=letter',
                                 get_string('standardview', 'qanda'));



    $browserow[] = new tabobject(QANDA_DATE_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=date',
                                 get_string('dateview', 'qanda'));
/*
        $browserow[] = new tabobject(qanda_CATEGORY_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=cat',
                                 get_string('categoryview', 'qanda'));
 $browserow[] = new tabobject(qanda_AUTHOR_VIEW,
                                 $CFG->wwwroot.'/mod/qanda/view.php?id='.$id.'&amp;mode=author',
                                 get_string('authorview', 'qanda'));
 *  */


    if ($tab < QANDA_STANDARD_VIEW ) {//|| $tab > qanda_AUTHOR_VIEW   // We are on second row
        $inactive = array('edit');
        $activated = array('edit');

        $browserow[] = new tabobject('edit', '#', get_string('edit'));
    }

/// Put all this info together

    $tabrows = array();
    $tabrows[] = $browserow;     // Always put these at the top
    if ($toolsrow) {
        $tabrows[] = $toolsrow;
    }


?>
  <div class="qanda-display">


<?php if ($showcommonelements) { print_tabs($tabrows, $tab, $inactive, $activated); } ?>

  <div class="entrybox">

<?php

    if (!isset($category)) {
        $category = "";
    }


    switch ($tab) {
        case QANDA_APPROVAL_VIEW:
            qanda_print_approval_menu($cm, $qanda, $mode, $hook, $sortkey, $sortorder);
        break;
        case QANDA_IMPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_import_menu($cm, $qanda, 'import', $hook, $sortkey, $sortorder);
        break;
        case QANDA_EXPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_export_menu($cm, $qanda, 'export', $hook, $sortkey, $sortorder);
        break;
        case QANDA_DATE_VIEW:
            if (!$sortkey) {
                $sortkey = 'UPDATE';
            }
            if (!$sortorder) {
                $sortorder = 'desc';
            }
            qanda_print_alphabet_menu($cm, $qanda, "date", $hook, $sortkey, $sortorder);
        break;
        case QANDA_STANDARD_VIEW:
        default:
            qanda_print_alphabet_menu($cm, $qanda, "letter", $hook, $sortkey, $sortorder);
            if ($mode == 'search' and $hook) {
                echo "<h3>$strsearch: $hook</h3>";
            }
        break;
    }
    /*
        case qanda_CATEGORY_VIEW:
            qanda_print_categories_menu($cm, $qanda, $hook, $category);
        break;
        case QANDA_APPROVAL_VIEW:
            qanda_print_approval_menu($cm, $qanda, $mode, $hook, $sortkey, $sortorder);
        break;
        case qanda_AUTHOR_VIEW:
            $search = "";
            qanda_print_author_menu($cm, $qanda, "author", $hook, $sortkey, $sortorder, 'print');
        break;
        case QANDA_IMPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_import_menu($cm, $qanda, 'import', $hook, $sortkey, $sortorder);
        break;
        case QANDA_EXPORT_VIEW:
            $search = "";
            $l = "";
            qanda_print_export_menu($cm, $qanda, 'export', $hook, $sortkey, $sortorder);
        break;
        case QANDA_DATE_VIEW:
            if (!$sortkey) {
                $sortkey = 'UPDATE';
            }
            if (!$sortorder) {
                $sortorder = 'desc';
            }
            qanda_print_alphabet_menu($cm, $qanda, "date", $hook, $sortkey, $sortorder);
        break;
        case QANDA_STANDARD_VIEW:
        default:
            qanda_print_alphabet_menu($cm, $qanda, "letter", $hook, $sortkey, $sortorder);
            if ($mode == 'search' and $hook) {
                echo "<h3>$strsearch: $hook</h3>";
            }
        break;
     */
    
    echo '<hr />';
?>
