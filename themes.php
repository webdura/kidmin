<?php
$user_flag = true;
$settings  = false;
include("header.php");
include("config.php");

if(isset($_POST['theme_id']) && $_POST['theme_id']>0)
{
    if(isset($_FILES['site_logo']) && $_FILES['site_logo']['size']>0)
    {
        $filename = $_FILES['site_logo']['name'];
        $tmp_name = $_FILES['site_logo']['tmp_name'];
        
        $exts = explode('.', $filename);
        $ext  = $exts[count($exts)-1];
        
        $filename = 'site_logo_'.$ses_companyId.'.'.$ext;
        $new_name = "images/company/$filename";
        $old_name = "images/company/$site_logo";
        
        copy($tmp_name, $new_name);
        upload_photo($new_name, $new_name, 500, 80);
        if($filename != $site_logo)
            @unlink($old_name);
        $site_logo = $filename;
    }
    if(isset($_FILES['invoice_logo']) && $_FILES['invoice_logo']['size']>0)
    {
        $filename = $_FILES['invoice_logo']['name'];
        $tmp_name = $_FILES['invoice_logo']['tmp_name'];
        
        $exts = explode('.', $filename);
        $ext  = $exts[count($exts)-1];
        
        $filename = 'invoice_logo_'.$ses_companyId.'.'.$ext;
        $new_name = "images/company/$filename";
        $old_name = "images/company/$invoice_logo";
        
        copy($tmp_name, $new_name);
        //upload_photo($new_name, $new_name, 20000, 100);
        if($filename != $invoice_logo)
            @unlink($old_name);
        $invoice_logo = $filename;
    }
    
    $site_logo      = GetSQLValueString($site_logo, 'text');
    $invoice_logo   = GetSQLValueString($invoice_logo, 'text');
    $invoice_status = isset($_POST['invoice_status']) ? 1 : 0;
    $theme_id       = GetSQLValueString($_POST['theme_id'], 'text');
    
    $head_bg    = strstr($_POST['head_bg'], '#') ? $_POST['head_bg'] : '#'.$_POST['head_bg'];
    $head_bg    = GetSQLValueString($head_bg, 'text');
    $head_color = strstr($_POST['head_color'], '#') ? $_POST['head_color'] : '#'.$_POST['head_color'];
    $head_color = GetSQLValueString($head_color, 'text');
//    $color1     = strstr($_POST['color1'], '#') ? $_POST['color1'] : '#'.$_POST['color1'];
//    $color1     = GetSQLValueString($color1, 'text');
//    $color2     = strstr($_POST['color2'], '#') ? $_POST['color2'] : '#'.$_POST['color2'];
//    $color2     = GetSQLValueString($color2, 'text');
//    $color3     = strstr($_POST['color3'], '#') ? $_POST['color3'] : '#'.$_POST['color3'];
//    $color3     = GetSQLValueString($color3, 'text');
//    $color4     = strstr($_POST['color4'], '#') ? $_POST['color4'] : '#'.$_POST['color4'];
//    $color4     = GetSQLValueString($color4, 'text');
    
    $company_theme_sql = "companyId='$ses_companyId',	theme_id=$theme_id,	site_logo=$site_logo,	invoice_logo=$invoice_logo,	invoice_status=$invoice_status,	head_bg=$head_bg,	head_color=$head_color"; //,	color1=$color1,	color2=$color2,	color3=$color3,	color4=$color4";
    if(mysql_num_rows(mysql_query("SELECT * FROM gma_company_theme WHERE companyId='$ses_companyId'"))==0)
        $company_theme_sql = "INSERT INTO gma_company_theme SET $company_theme_sql";
    else
        $company_theme_sql = "UPDATE gma_company_theme SET $company_theme_sql WHERE companyId='$ses_companyId'";
    mysql_query($company_theme_sql);
    
    return header("Location: themes.php?msg=updated");
}

$theme_rows = array();
$theme_sql = "SELECT * FROM gma_theme WHERE 1 ORDER BY name ASC";
$theme_rs  = mysql_query($theme_sql);
while ($theme_row = mysql_fetch_assoc($theme_rs)) {
	   $theme_rows[$theme_row['id']] = $theme_row;
}
//echo '<pre>'; print_r($theme_rows); exit;

$page_title = 'Theme';
include('sub_header.php');
?>

<script type="text/javascript" src="js/jscolor.js"></script>
<div class="newinvoice" style="width:80%">
<form name="userForm" id="userForm" method="post" action="" enctype="multipart/form-data"><br>
<table width="100%" class="send_credits" cellpadding="3" cellspacing="3">
    <tr><td colspan="13" class="sc_head">Theme Details</td></tr>
    
    <tr valign="top">
        <th class="row1" align="left" width="10%">Select Theme&nbsp;:&nbsp;</th>
        <td class="row1">
            <select class="inputbox_green" style="width:300px;" name="theme_id" id="theme_id" onchange="changeTheme(this.value);">
                <? foreach ($theme_rows as $theme_id=>$theme_row) { ?>
                    <option value="<?=$theme_id?>" <?=($theme_theme_id==$theme_id ? 'selected' : '')?>><?=$theme_row['name']?></option>
                <? } ?>
            </select>
        </td>  
    </tr>  
    <? if($site_logo!='') { ?>
    <tr height="30">
        <th class="row2" align="left">Current site logo</td>
        <td class="row2" style="background-color:<?=$theme_head_bg?>" id="site_logo_div"><img src="images/company/<?=$site_logo?>"></td>
    </tr>
    <? } ?>
    <tr height="30">
        <th class="row2" align="left"><? if($site_logo!='') { ?>New <? } ?>site logo</td>
        <td class="row2"><input type="file" name="site_logo" id="site_logo" class="fleft file_size"></td>
    </tr>
    <? if($invoice_logo!='') { ?>
    <tr height="30">
        <th class="row1" align="left">Current invoice logo</td>
        <td class="row1"><img src="images/company/<?=$invoice_logo?>"></td>
    </tr>
    <? } ?>
    <tr height="30">
        <th class="row1" align="left"><? if($invoice_logo!='') { ?>New <? } ?>invoice logo</td>
        <td class="row1">
            <input type="file" name="invoice_logo" id="invoice_logo" class="fleft file_size">
            &nbsp;&nbsp;
            <div class="fleft">Use site logo&nbsp;<input type="checkbox" name="invoice_status" id="invoice_status" value="1" <?=($invoice_status==1 ? 'checked' : '')?>></div>        
        </td>
    </tr>
    <tr height="30">
        <th class="row2" align="left">Header background color</td>
        <td class="row2"><input type="text" name="head_bg" id="head_bg" class="required textbox color" value="<?=$theme_head_bg?>" readonly onchange="$('#site_logo_div').css('background-color', '#'+this.value);"></td>
    </tr>
    <tr height="30">
        <th class="row1" align="left">Header link color</td>
        <td class="row1"><input type="text" name="head_color" id="head_color" class="required textbox color" value="<?=$theme_head_color?>" readonly></td>
    </tr>
    <!--<tr height="30">
        <th class="row1" align="left">color1</td>
        <td class="row1"><input type="text" name="color1" id="color1" class="required textbox color" value="<?=$theme_color1?>" readonly></td>
    </tr>
    <tr height="30">
        <th class="row2" align="left">color2</td>
        <td class="row2"><input type="text" name="color2" id="color2" class="required textbox color" value="<?=$theme_color2?>" readonly></td>
    </tr>
    <tr height="30">
        <th class="row1" align="left">color3</td>
        <td class="row1"><input type="text" name="color3" id="color3" class="required textbox color" value="<?=$theme_color3?>" readonly></td>
    </tr>
    <tr height="30">
        <th class="row2" align="left">color4</td>
        <td class="row2"><input type="text" name="color4" id="color4" class="required textbox color" value="<?=$theme_color4?>" readonly></td>
    </tr>-->
    <tr><td bgcolor="#FFFFFF">&nbsp;</td></tr>
    <tr><td bgcolor="#FFFFFF"><input type="submit" name="sbmt" id="sbmt" value="Submit" class="search_bt" /></td></tr>
</table>

<script>
$(document).ready(function() {
    $("#userForm").validate({
        rules: {
            site_logo: {
                accept: "jpg|jpeg|gif|png"
            },
            invoice_logo: {
                accept: "jpg|jpeg|gif|png"
            }
        },
        messages: {
            site_logo: {
                accept: jQuery.format("Only JPG, GIF or PNG file types allowed")
            },
            invoice_logo: {
                accept: jQuery.format("Only JPG, GIF or PNG file types allowed")
            }
        }
    });
});
</script>
    
</form>
</div>

<?php include("footer.php");  ?>