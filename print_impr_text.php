<?php


/**
 * \file
 * \brief Print/Edit an improved annotated text
 * 
 * Call: print_impr_text.php?text=[textid]&...
 *      ... edit=1 ... edit own annotation 
 *      ... del=1  ... delete own annotation
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/print__impr__text_8php.html
 * @since   1.5.0
 */

require_once 'inc/session_utility.php';

$textid = (int)getreq('text');
$editmode = getreq('edit');
$editmode = ($editmode == '' ? 0 : (int)$editmode);
$delmode = getreq('del');
$delmode = ($delmode == '' ? 0 : (int)$delmode);
$ann = get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
$ann_exists = (strlen($ann) > 0);
if ($ann_exists) {
    $ann = recreate_save_ann($textid, $ann);
    $ann_exists = (strlen($ann) > 0);
}

if ($textid==0) {
    header("Location: edit_texts.php");
    exit();
}

if ($delmode) {  // Delete
    if ($ann_exists) { 
        runsql(
            'update ' . $tbpref . 'texts set ' .
            'TxAnnotatedText = ' . convert_string_to_sqlsyntax("") . 
            ' where TxID = ' . $textid, 
            ""
        );
    }
    $ann_exists = (int)get_first_value(
        "SELECT length(TxAnnotatedText) AS value 
        FROM " . $tbpref . "texts where TxID = " . $textid
    ) > 0;
    if (!$ann_exists) {
        header("Location: print_text.php?text=" . $textid);
        exit();
    }
}

$sql = 'select TxLgID, TxTitle, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$title = $record['TxTitle'];
$sourceURI = $record['TxSourceURI'];
$langid = $record['TxLgID'];
$audio = $record['TxAudioURI'];
if (!isset($audio)) { 
    $audio = ''; 
}
$audio = trim($audio);
mysqli_free_result($res);

$sql = 'select LgTextSize, LgRemoveSpaces, LgRightToLeft, LgGoogleTranslateURI 
from ' . $tbpref . 'languages where LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'];
$rtlScript = $record['LgRightToLeft'];
if (!empty($record['LgGoogleTranslateURI'])) {
    $ttsLg=preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $record['LgGoogleTranslateURI']);
    if ($record['LgGoogleTranslateURI']==$ttsLg) {
        $ttsClass='';
    } else { 
        $ttsClass = 'tts_'.$ttsLg.' '; 
    }
} else { 
    $ttsClass=''; 
}
mysqli_free_result($res);

saveSetting('currenttext', $textid);

pagestart_nobody('Annotated Text', 'input[type="radio"]{display:inline;}');

?>
<div class="noprint"> 
<div class="flex-header">
<div>
<?php echo_lwt_logo(); ?>
</div>
<div>
<?php echo getPreviousAndNextTextLinks($textid, 'print_impr_text.php?text=', true, ''); ?>
</div>
<div><a href="do_text.php?start=<?php echo $textid; ?>" target="_top">
<img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a>
<a href="do_test.php?text=<?php echo $textid; ?>" target="_top">
<img src="icn/question-balloon.png" title="Test" alt="Test" />
</a>
<a href="print_text.php?text=<?php echo $textid; ?>" target="_top">
<img src="icn/printer.png" title="Print" alt="Print" />
<a target="_top" href="edit_texts.php?chg=<?php echo $textid; ?>">
<img src="icn/document--pencil.png" title="Edit Text" alt="Edit Text" /></a>
</div>
<div>
<?php quickMenu(); ?>
</div></div>
<h1>ANN.TEXT ▶ <?php echo tohtml($title) . 
(isset($sourceURI) && substr(trim($sourceURI), 0, 1)!='#' ? 
' <a href="<?php echo $sourceURI; ?>" target="_blank"><img src="'.get_file_path('icn/chain.png') .
'" title="Text Source" alt="Text Source" /></a>' 
: '') ?></h1>
<div id="printoptions">
<h2>Improved Annotated Text<?php

if ($editmode) {
    ?> (Edit Mode) 
    <img src="icn/question-frame.png" title="Help" alt="Help" class="click" onclick="window.open('docs/info.html#il');" />
    </h2>
    <input type="button" value="Display/Print Mode" onclick="location.href='print_impr_text.php?text=<?php echo $textid; ?>';" />
    <?php
} else {
    ?> (Display/Print Mode)</h2>
    <div class="flex-spaced">
    <input type="button" value="Edit" onclick="location.href='print_impr_text.php?edit=1&amp;text=<?php echo $textid; ?>';" /> 
    <input type="button" value="Delete" onclick="if (confirm ('Are you sure?')) location.href='print_impr_text.php?del=1&amp;text=<?php echo $textid; ?>';" /> 
    <input type="button" value="Print" onclick="window.print();" />
    <input type="button" value="Display <?php echo (($audio != '') ? ' with Audio Player' : ''); ?> in new Window" 
    onclick="window.open('display_impr_text.php?text=<?php echo $textid; ?>');" />
    </div>
    <?php
}
?>
</div>
</div> 
<!-- noprint -->
<?php

// --------------------------------------------------------

if ($editmode) {  // Edit Mode

    if (!$ann_exists) {  // No Ann., Create...
        $ann = create_save_ann($textid);
        $ann_exists = (strlen($ann) > 0);
    }
    
    if (!$ann_exists) {  // No Ann., not possible
        echo '<p>No annotated text found, and creation seems not possible.</p>';
    } else { // Ann. exists, set up for editing.
        echo "\n";
        echo '<div data_id="' . $textid . '" id="editimprtextdata"></div>';
        echo "\n";
        ?>
    <script type="text/javascript">
        //<![CDATA[
        $(document).ready(function() {
            do_ajax_edit_impr_text(0,'');
        } ); 
        //]]>
    </script>
        <?php
    }
    echo '<div class="noprint">
    <input type="button" value="Display/Print Mode" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
    </div>';

} else {  // Print Mode

    echo "<div id=\"print\"" . ($rtlScript ? ' dir="rtl"' : '') . ">";
    
    echo '<p style="font-size:' . $textsize . '%;line-height: 1.35; margin-bottom: 10px; ">' . tohtml($title) . '<br /><br />';
    
    $items = preg_split('/[\n]/u', $ann);
    
    foreach ($items as $item) {
        $vals = preg_split('/[\t]/u', $item);
        if ($vals[0] > -1) {
            $trans = '';
            if (count($vals) > 3) { 
                $trans = $vals[3]; 
            }
            if ($trans == '*') { 
                $trans = $vals[1] . " "; // <- U+200A HAIR SPACE
            }      
            echo ' <ruby><rb><span class="'.$ttsClass.'anntermruby">' . tohtml($vals[1]) . '</span></rb><rt><span class="anntransruby2">' . tohtml($trans) . '</span></rt></ruby> ';
        } else {
            if (count($vals) >= 2) { 
                echo str_replace(
                    "¶",
                    '</p><p style="font-size:' . $textsize . '%;line-height: 1.3; margin-bottom: 10px;">',
                    " " . tohtml($vals[1]) . " "
                ); 
            }
        }
    }
    
    echo "</p></div>";

}

pageend();

?>
