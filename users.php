<?php
$user_btn = 'selected';
include("header.php");  
include("config.php");

$action    = (isset($_REQUEST['action']) && $_REQUEST['action']!='') ? $_REQUEST['action'] : 'list';
$perPage   = ($_SESSION['perpageval']!='') ? $_SESSION['perpageval'] : 50;
$pageNum   = ($_REQUEST['page']!='') ? $_REQUEST['page'] : 1;

$userId     = (isset($_REQUEST['user_id']) && $_REQUEST['user_id']>0) ? $_REQUEST['user_id'] : 0;
$srchtxt    = trim(($_REQUEST['srchtxt']!='') ? $_REQUEST['srchtxt'] : '');
$search     = trim(($_REQUEST['search']!='') ? $_REQUEST['search'] : '');
$userTypes  = userTypes('', 0, 1);
$userTypes  = "'".implode("', '", $userTypes)."'";
$page_title = 'Clients';

$user_sql  = "SELECT * FROM gma_user_details,gma_logins,gma_company WHERE gma_user_details.userId=gma_logins.userId AND gma_company.companyId=gma_logins.companyId AND userType IN ($userTypes) AND gma_company.companyId='$ses_companyId' ";
switch ($action)
{
    case 'add':
        if(isset($_REQUEST['sbmt']))
        {
            unset($_POST['sbmt']);
            
            $discounts = $_POST['discount'];
            unset($_POST['discount']);
            
            $_POST['joinDate'] = convertToMysqlDate($_POST['joinDate']);
            
            $userName  = $_POST['userName'];
            $password  = $_POST['password'];
            $userType  = $_POST['userType'];
            $useremail = $_POST['email'];
            unset($_POST['userName']);
            unset($_POST['password']);
            unset($_POST['userType']);
            unset($_POST['email']);
            $sql = "INSERT INTO gma_logins SET companyId=".GetSQLValueString($ses_companyId, 'text').",userName=".GetSQLValueString($userName, 'text').",password=".GetSQLValueString($password, 'text').",email=".GetSQLValueString($useremail, 'text').",userType=".GetSQLValueString($userType, 'text');
            mysql_query($sql);
            $userId = mysql_insert_id();
                
            $values = "userId='$userId'"; //,joinDate=NOW()";
            foreach ($_POST AS $name=>$value)
            {
                if($values!='') $values .= ',';
                $values .= "$name=".GetSQLValueString($value, 'text');
            }
            if($values!='')
            {
                $sql = "INSERT INTO gma_user_details SET $values";
                mysql_query($sql);
                
                foreach ($discounts as $group_id=>$discount)
                {
                    if($discount>0)
                    {
                        $sql = "INSERT INTO gma_user_discount SET userId='$userId',group_id='$group_id',discount='$discount'";
                        mysql_query($sql);                
                    }
                }
            }
        
            $email_values = array(
                'firstname' => $_POST['firstName'],
                'lastname' => $_POST['lastName'],
                'clientname' => $_POST['businessName'],
                'username' => $userName,
                'password' => $password,
                'email' => $useremail,
                'to_email' => $useremail,
            );
            emailSend('new_client', $email_values);
                
            header("Location: users.php?msg=added");
            exit;
        }
        $user_row['joinDate'] = date('d/m/Y');
        break;
        
    case 'edit':
        if(isset($_REQUEST['userName']))
        {
            $values = '';
            unset($_POST['sbmt']);
            
            $discounts = $_POST['discount'];
            unset($_POST['discount']);
            
            $_POST['joinDate'] = convertToMysqlDate($_POST['joinDate']);
            
            $userName  = $_POST['userName'];
            $password  = $_POST['password'];
            $userType  = $_POST['userType'];
            $useremail = $_POST['email'];
            unset($_POST['userName']);
            unset($_POST['password']);
            unset($_POST['userType']);
            unset($_POST['email']);
            $sql = ($password!='') ? ",password=".GetSQLValueString($password,'text') : '';
            $sql = "UPDATE gma_logins SET userName=".GetSQLValueString($userName, 'text').",email=".GetSQLValueString($useremail, 'text').",userType=".GetSQLValueString($userType, 'text')."$sql WHERE userId='$userId'";
            mysql_query($sql);
            
            foreach ($_POST AS $name=>$value)
            {
                if($values!='') $values .= ',';
                $values .= "$name=".GetSQLValueString($value, 'text');
            }
            if($values!='')
            {
                $sql = "UPDATE gma_user_details SET $values WHERE userId='$userId'";
                mysql_query($sql);
                
                $sql = "DELETE FROM gma_user_discount WHERE userId='$userId'";
                mysql_query($sql);                
                foreach ($discounts as $group_id=>$discount)
                {
                    if($discount>0)
                    {
                        $sql = "INSERT INTO gma_user_discount SET userId='$userId',group_id='$group_id',discount='$discount'";
                        mysql_query($sql);                
                    }
                }
                
                header("Location: users.php?msg=updated");
                exit;
            }
        }
        $user_sql .= " AND gma_logins.userId='$userId'";
        $user_rs   = mysql_query($user_sql);
        if(mysql_num_rows($user_rs)!=1)
        {
            header("Location: users.php");
            exit;
        }
        $user_row = mysql_fetch_array($user_rs);	 
        $user_row['joinDate'] = dateFormat($user_row['joinDate']);
            
        $discount_sql = "SELECT * FROM gma_user_discount WHERE userId='$userId'";
        $discount_rs  = mysql_query($discount_sql);
        while ($discount_row = mysql_fetch_array($discount_rs))
        {
            $user_row['group_ids'][$discount_row['group_id']] = $discount_row['discount'];
        }     
        break;
        
    case 'view':
        $user_sql .= " AND gma_logins.userId='$userId'";
        $user_rs   = mysql_query($user_sql);
        if(mysql_num_rows($user_rs)!=1)
        {
            header("Location: users.php");
            exit;
        }
        $user_row     = mysql_fetch_array($user_rs);
        $discount_sql = "SELECT * FROM gma_user_discount WHERE userId='$userId'";
        $discount_rs  = mysql_query($discount_sql);
        while ($discount_row = mysql_fetch_array($discount_rs))
        {
            $user_row['group_ids'][$discount_row['group_id']] = $discount_row['discount'];
        }
        break;
        
    case 'delete':
        $user_sql .= " AND gma_logins.userId='$userId'";
        $user_rs   = mysql_query($user_sql);
        if(mysql_num_rows($user_rs)!=1)
        {
            header("Location: users.php");
            exit;
        }
                
//        $sql = "DELETE FROM gma_logins WHERE userId='$userId'";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_user_details WHERE userId='$userId'";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_user_discount WHERE userId='$userId'";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_order_details WHERE orderId IN (SELECT id FROM gma_order WHERE userId='$userId')";
//        mysql_query($sql);
//
//        $sql = "DELETE FROM gma_order WHERE userId='$userId'";
//        mysql_query($sql);
//
//        $sql = "DELETE FROM gma_payments WHERE userId='$userId'";
//        mysql_query($sql);
        
        header("Location: users.php?d");        
        break;
        
    case 'deleteall':
        $user_id   = implode(',', $_REQUEST['delete']);
        $user_sql .= " AND gma_logins.userId IN ($user_id)";
        $user_id   = 0;
        $user_rs   = mysql_query($user_sql);
        while($user_row = mysql_fetch_assoc($user_rs))
        {
            $user_id .= ','.$user_row['userId'];
        }
        if($user_id=='0')
        {
            header("Location: users.php?i");
            exit;
        }
                
//        $sql = "DELETE FROM gma_logins WHERE userId IN ($user_id)";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_user_details WHERE userId IN ($user_id)";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_user_discount WHERE userId IN ($user_id)";
//        mysql_query($sql);
//        
//        $sql = "DELETE FROM gma_order_details WHERE orderId IN (SELECT id FROM gma_order WHERE userId IN ($user_id))";
//        mysql_query($sql);
//
//        $sql = "DELETE FROM gma_order WHERE userId IN ($user_id)";
//        mysql_query($sql);
//
//        $sql = "DELETE FROM gma_payments WHERE userId IN ($user_id)";
//        mysql_query($sql);
        
        header("Location: users.php?d");        
        break;
        
    case 'login':
        $_SESSION['usr_userId'] = $_SESSION['ses_userId'];
        $_SESSION['ses_userId'] = $_REQUEST['userId'];
        
        header("Location: index.php");        
        break;
        
    default:
        $action  = 'list';
        $offset  = ($pageNum - 1) * $perPage;
        $orderBy = ($_REQUEST['orderby']!='') ? 'ORDER BY '.$_REQUEST['orderby'].' '.$_REQUEST['order'] : 'ORDER BY businessName ASC ';
        
        $user_sql  .= ($srchtxt!='') ? " AND (firstName LIKE '$srchtxt%' OR lastName LIKE '$srchtxt%' OR businessName LIKE '$srchtxt%' OR userName LIKE '$srchtxt%' OR gma_user_details.userId='$srchtxt')" : '';
        $user_sql  .= ($search!='') ? " AND (businessName LIKE '$search%')" : '';
                
        $user_sql  .= " $orderBy";
        $user_rs    = mysql_query($user_sql);
        $user_count = mysql_num_rows($user_rs);
        
        $pagination = '';
        if($user_count>$perPage)
        {
            $user_sql  .= " LIMIT $offset, $perPage";
            $user_rs    = mysql_query($user_sql);
            
            $maxPage     = ceil($user_count/$perPage);
            $pagination  = pagination($maxPage, $pageNum);
            $pagination  = paginations($user_count, $perPage, 5);
        }
        
        $links = '<a href="users.php?action=add" title="Add new">Add new</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0);" onclick="deleteAll();" title="Delete">Delete</a>';
        $chars = '<a href="users.php">All</a>';
        for($i=65;$i<91;$i++)
        {
            $char      = chr($i);
            $selected  = ($char==$search ? "class='selected'" : '');
            $chars    .= "&nbsp;<a href='users.php?search=$char' $selected>$char</a>";
        }
        break;
}
$group_sql = "companyId='$ses_companyId' AND status=1";
$group_sql = "SELECT * FROM gma_groups WHERE $group_sql ORDER BY name ASC";
$group_rs  = mysql_query($group_sql);
while ($group_row = mysql_fetch_assoc($group_rs)) {
	   $group_rows[$group_row['id']] = $group_row;
}

$userType = userTypes($user_row['userType']);

include('sub_header.php');
if($action=='list') { ?>

<form method="POST">
<div class="pagination" align="right">
    <table border="0" width="100%">
    <tr>
        <td align="left" width="24%" >
            <b>Search&nbsp;:&nbsp;</b>
            <input type="text" class="inputbox_green" name="srchtxt" id="srchtxt" size="23" value="<?=$srchtxt?>" />&nbsp;
            <input type="submit"  value="Search"  class="search_bt" name="sbmt" id="sbmt" />
        </td>
        <td align="center" width="30%"><?=$chars?></td>
        <td align="right" width="35%"><?=$pagination?></td>
    </tr>
    </table>
</div>
</form>

<form method="POST" id="listForm" name='listForm'>
<input type="hidden" name="action" value="deleteall">
<div class="client_display">
    <table width="100%" class="client_display_table" cellpadding="3" cellspacing="3">
        <tr height="30">
            <th class="thead"width="2%"><input type="checkbox" name="selectall" id="selectall" onclick="checkUncheck(this);"></th>
            <? $width=15; if($companyId==0 && 1==2) { $width=10; ?>
            <th width="<?=$width?>%" class="thead" valign="middle"><span>Company Name</span>&nbsp;<a href="?<?=$queryString ?>&orderby=companyName&order=ASC"><img src="images/arrowAsc.png"  border="0"/></a>&nbsp;<a href="?<?=$queryString ?>&orderby=companyName&order=DESC"><img src="images/arrowDec.png"  border="0"/></a></th>
            <? } ?>
            <th width="<?=$width?>%" class="thead" valign="middle"><span>Client Name</span>&nbsp;<a href="?<?=$queryString ?>&orderby=businessName&order=ASC"><img src="images/arrowAsc.png"  border="0"/></a>&nbsp;<a href="?<?=$queryString ?>&orderby=businessName&order=DESC"><img src="images/arrowDec.png"  border="0"/></a></th>
            <th width="<?=$width?>%" class="thead"><span>Contact Name</span>&nbsp;<a href="?<?=$queryString ?>&orderby=firstName&order=ASC"><img src="images/arrowAsc.png"  border="0"/></a>&nbsp;<a href="?<?=$queryString ?>&orderby=firstName&order=DESC"><img src="images/arrowDec.png"  border="0"/></a></th>
            <th width="10%" class="thead"><span>Username</span>&nbsp;<a href="?<?=$queryString ?>&orderby=userName&order=ASC"><img src="images/arrowAsc.png"  border="0"/></a>&nbsp;<a href="?<?=$queryString ?>&orderby=userName&order=DESC"><img src="images/arrowDec.png"  border="0"/></a></th>
            <th width="15%" class="thead"><span>Email</span>&nbsp;<a href="?<?=$queryString ?>&orderby=email&order=ASC"><img src="images/arrowAsc.png"  border="0"/></a>&nbsp;<a href="?<?=$queryString ?>&orderby=email&order=DESC"><img src="images/arrowDec.png"  border="0"/></a></th>
            <th width="10%" class="thead">Tel No.</th>
            <th width="20%" class="thead">Action</th>
        </tr>
        <?php
        $j=0;
        while($user_row = mysql_fetch_assoc($user_rs))
        {
            $class   = ((($j++)%2)==1) ? 'row2' : 'row1';
            $auto_id = $user_row['userId'];
            ?>
            <tr class="<?=$class?>">
                <td><input type="checkbox" id="delete" name="delete[]" value="<?=$auto_id?>"></td>
                <? if($companyId==0 && 1==2) { ?>
                    <td><?=$user_row['companyName']?></td>
                <? } ?>
                <td><?=$user_row['businessName']?></td>
                <td><?=$user_row['firstName'].' '.$user_row['lastName']?></td>
                <td><?=$user_row['userName']?></td>
                <td><?=$user_row['email']?></td>
                <td><?=$user_row['phone']?></td>
                <td><a href="users.php?action=view&user_id=<?=$auto_id?>" alt="View Details" title="View Details">View</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="users.php?action=edit&user_id=<?=$auto_id?>" alt="Edit Details" title="Edit Details">Edit</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="users.php?action=delete&user_id=<?=$auto_id?>" onclick="return window.confirm('Are you sure to delete this ?');">Delete</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="invoices.php?action=add&userId=<?=$auto_id?>">Create Invoice</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="users.php?action=login&userId=<?=$auto_id?>">Login</a></td>
            </tr>
            <?php
        }
        if($user_count==0) { ?>
           <tr><td class="message" colspan="10">No Records Found</td></tr>
        <? } ?>
    </table>
</div>
</form>

<div class="pagination" align="right">
    <table border="0" width="100%">
    <tr>
        <td align="left" width="24%" ></td>
        <td align="center" width="30%"><?=$chars?></td>
        <td align="right" width="35%"><?=$pagination?></td>
    </tr>
    </table>
</div>

<? } else if($action=='edit' || $action=='add') { ?>

<script type="text/javascript" src="js/date.js"></script>
<script type="text/javascript" src="js/jquery.datePicker.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="css/datePicker.css">

<div class="newinvoice">
<form name="userForm" id="userForm" method="post" action="">
<br>
<table width="100%" class="send_credits" cellpadding="3" cellspacing="3">
    <tr><td colspan="13" class="sc_head"><?=($action=='add' ? 'ADD' : 'EDIT')?> CLIENT DETAILS&nbsp;<span class="back"><a href="users.php">Back</a></span></td></tr>
</table>
<table width="100%" class="send_credits" cellpadding="3" cellspacing="3">
<tr>
    <th class="row2" align="left">Account Grading</th>
    <td class="row2"><input type="text" name="grade" id="grade" class="textbox number" value="<?=$user_row['grade']?>" /></td>  
</tr>  
<tr valign="top">
    <th class="row1" align="left">Discount Percentage</th>
    <td class="row1">
    
    <? foreach ($group_rows as $group_id=>$group_row) { ?>
        <b><?=$group_row['name']?>&nbsp;:&nbsp;</b><input type="text" name="discount[<?=$group_id?>]" id="discount_<?=$group_id?>" class="textbox number" style="width:200px;" value="<?=@$user_row['group_ids'][$group_id]?>" /><br>
    <? } ?>
    
    </td>  
</tr>  
<tr>
    <th class="row2" align="left">Username</th>
    <td class="row2"><input type="text" name="userName" id="userName" class="textbox required" value="<?=$user_row['userName']?>" /></td>  
</tr>
<tr>
    <th class="row1" align="left">Password</th>
    <td class="row1"><input type="text" name="password" id="password" class="textbox required" value="<?=$user_row['password']?>" /></td>  
</tr>
<tr>
    <th class="row2" align="left" width="25%">Client Name</th>
    <td class="row2"><input type="text" name="businessName" id="businessName" class="textbox required" value="<?=$user_row['businessName']?>" /></td>  
</tr>  
<tr>
    <th class="row1" align="left">First Name</th>
    <td class="row1"><input type="text" name="firstName" id="firstName" class="textbox required" value="<?=$user_row['firstName']?>" /></td>  
</tr>  
<tr>
    <th class="row2" align="left">Last Name</th>
    <td class="row2"><input type="text" name="lastName" id="lastName" class="textbox required" value="<?=$user_row['lastName']?>" /></td>  
</tr>
<tr>
    <th class="row1" align="left">Email</th>
    <td class="row1"><input type="text" name="email" id="email" class="textbox required email" value="<?=$user_row['email']?>"/></td>  
</tr>  
<tr>
    <th class="row2" align="left">Tel No.</th>
    <td class="row2"><input type="text" name="phone" id="phone" class="textbox required" value="<?=$user_row['phone']?>" /></td>  
</tr> 
<tr>
    <th class="row1" align="left">Owner/Manager First Name</th>
    <td class="row1"><input type="text" name="ownerFirstName" id="ownerFirstName" class="textbox" value="<?=$user_row['ownerFirstName']?>" /></td>  
</tr>  
<tr>
    <th class="row2" align="left">Owner/Manager Last Name</th>
    <td class="row2"><input type="text" name="ownerLastName" id="ownerLastName" class="textbox" value="<?=$user_row['ownerLastName']?>" /></td>  
</tr>  
<tr>
    <th class="row1" align="left">Owner/Manager Email</th>
    <td class="row1"><input type="text" name="ownerEmail" id="ownerEmail" class="textbox email" value="<?=$user_row['ownerEmail']?>"/></td>  
</tr>  
<tr>
    <th class="row2" align="left">Owner/Manager Tel No.</th>
    <td class="row2"><input type="text" name="ownerPhone" id="ownerPhone" class="textbox" value="<?=$user_row['ownerPhone']?>" /></td>  
</tr> 

<tr>
    <th class="row1" align="left">Address</th>
    <td class="row1"><input type="text" name="address" id="address" class="textbox" value="<?=$user_row['address']?>" /></td>  
</tr>  
<tr>
    <th class="row2" align="left">Payment Method</th>
    <td class="row2"><input type="text" name="paymentMethod" id="paymentMethod" class="textbox" value="<?=$user_row['paymentMethod']?>" /></td>  
</tr>  
<tr>
    <th class="row1" align="left">Payment Details Method</th>
    <td class="row1"><input type="text" name="paymentDetailsMethod" id="paymentDetailsMethod" class="textbox" value="<?=$user_row['paymentDetailsMethod']?>" /></td>  
</tr>  

<tr>
    <th class="row2" align="left">Bank Name</th>
    <td class="row2"><input type="text" name="bankName" id="bankName" class="textbox" value="<?=$user_row['bankName']?>" /></td>  
</tr>  
<tr>
    <th class="row1" align="left">Account Name</th>
    <td class="row1"><input type="text" name="accountName" id="accountName" class="textbox" value="<?=$user_row['accountName']?>" /></td>  
</tr>    
<tr>
    <th class="row2" align="left">Account Type</th>
    <td class="row2"><input type="text" name="accountType" id="accountType" class="textbox" value="<?=$user_row['accountType']?>" /></td>  
</tr>
<tr>
    <th class="row1" align="left">Account No</th>
    <td class="row1"><input type="text" name="accountNo" id="accountNo" class="textbox" value="<?=$user_row['accountNo']?>" /></td>  
</tr>  
<tr>
    <th class="row2" align="left">Branch Code</th>
    <td class="row2"><input type="text" name="branchCode" id="branchCode" class="textbox" value="<?=$user_row['branchCode']?>" /></td>  
</tr>

<tr>
    <th class="row1" align="left">Vat No</th>
    <td class="row1"><input type="text" name="vatNo" id="vatNo" class="textbox required" value="<?=$user_row['vatNo']?>" /></td>  
</tr>    
<tr>
    <th class="row2" align="left">User Status</th>
    <td class="row2"><input type="text" name="userStatus" id="userStatus" class="textbox" value="<?=$user_row['userStatus']?>" /></td>  
</tr>    
<tr>
    <th class="row1" align="left">Join Date</th>
    <td class="row1"><input type="text" name="joinDate" id="joinDate" class="textbox required" value="<?=$user_row['joinDate']?>" readonly /></td>  
</tr>
<tr>
    <th class="row2" align="left">User Type</th>
    <td class="row2"><?=$userType?></td>  
</tr> 
<tr>
    <th class="row1" align="left">Lead</th>
    <td class="row1"><input type="text" name="lead" id="lead" class="textbox" value="<?=$user_row['lead']?>" /></td>  
</tr>  
<tr>
    <th class="row2">&nbsp;</th>
    <td class="row2"><input type="submit" name="sbmt" id="sbmt" value="Submit" class="search_bt" /></td>  
</tr>      
</table>
</div>
</form>

<script>
$(document).ready(function() {
    $('#joinDate').datePicker({startDate: start_date, dateFormat: date_format});
   
    jQuery("#userForm").validate({
        rules: {
            userName: {
                required: true,
                remote: {
                    url: "ajax_check.php",
                    type: "post",
                    data: {
                        task: 'checkUserName',
                        user_id: '<?=$userId?>'
                    }
                }
            },
            email: {
                required: true,
//                remote: {
//                    url: "ajax_check.php",
//                    type: "post",
//                    data: {
//                        task: 'checkEmail',
//                        user_id: '<?=$userId?>'
//                    }
//                }
            }
        },
        messages: {
            userName: {
                remote: jQuery.format("Username is already in use.")
            },
            email: {
                remote: jQuery.format("Email is already in use.")
            }
        }
    });
});
</script>

<? } else if($action=='view') { ?>

<div class="newinvoice">
<br>
<table width="100%" class="send_credits" cellpadding="3" cellspacing="3">
    <tr><td colspan="13" class="sc_head">VIEW CLIENT DETAILS&nbsp;<span class="back"><a href="users.php">Back</a></span></td></tr>
</table>
<table width="100%" class="send_credits" cellpadding="3" cellspacing="3">
<tr>
    <th class="row1" align="left">Account Grading</th>
    <td class="row1"><?=$user_row['grade']?></td>
</tr>
<tr valign="top">
    <th class="row2" align="left">Discount Percentage</th>
    <td class="row2">
    
    <? foreach ($group_rows as $group_id=>$group_row) { ?>
        <?=$group_row['name']?>&nbsp;:&nbsp;<?=@$user_row['group_ids'][$group_id]?><br>
    <? } ?>
    
    </td>
</tr>
<tr>
    <th class="row1" align="left">Username</th>
    <td class="row1"><?=$user_row['userName']?></td>
</tr>
<tr>
    <th class="row2" align="left" width="25%">Client Name</th>
    <td class="row2"><?=$user_row['businessName']?></td>
</tr>
<tr>
    <th class="row1" align="left">First Name</th>
    <td class="row1"><?=$user_row['firstName']?></td>
</tr>
<tr>
    <th class="row2" align="left">Last Name</th>
    <td class="row2"><?=$user_row['lastName']?></td>
</tr>
<tr>
    <th class="row1" align="left">Email</th>
    <td class="row1"><?=$user_row['email']?></td>
</tr>
<tr>
    <th class="row2" align="left">Tel No.</th>
    <td class="row2"><?=$user_row['phone']?></td>
</tr> 

<tr>
    <th class="row1" align="left">Owner/Manager First Name</th>
    <td class="row1"><?=$user_row['ownerFirstName']?></td>
</tr>
<tr>
    <th class="row2" align="left">Owner/Manager Last Name</th>
    <td class="row2"><?=$user_row['ownerLastName']?></td>
</tr>
<tr>
    <th class="row1" align="left">Owner/Manager Email</th>
    <td class="row1"><?=$user_row['ownerEmail']?></td>
</tr>
<tr>
    <th class="row2" align="left">Owner/Manager Tel No.</th>
    <td class="row2"><?=$user_row['ownerPhone']?></td>
</tr> 

<tr>
    <th class="row1" align="left">Address</th>
    <td class="row1"><?=$user_row['address']?></td>
</tr>
<tr>
    <th class="row2" align="left">Payment Method</th>
    <td class="row2"><?=$user_row['paymentMethod']?></td>
</tr>
<tr>
    <th class="row1" align="left">Payment Details Method</th>
    <td class="row1"><?=$user_row['paymentDetailsMethod']?></td>
</tr>

<tr>
    <th class="row2" align="left">Bank Name</th>
    <td class="row2"><?=$user_row['bankName']?></td>
</tr>
<tr>
    <th class="row1" align="left">Account Name</th>
    <td class="row1"><?=$user_row['accountName']?></td>
</tr>  
<tr>
    <th class="row2" align="left">Account Type</th>
    <td class="row2"><?=$user_row['accountType']?></td>
</tr>
<tr>
    <th class="row1" align="left">Account No</th>
    <td class="row1"><?=$user_row['accountNo']?></td>
</tr>
<tr>
    <th class="row2" align="left">Branch Code</th>
    <td class="row2"><?=$user_row['branchCode']?></td>
</tr>

<tr>
    <th class="row1" align="left">Vat No</th>
    <td class="row1"><?=$user_row['vatNo']?></td>
</tr>
<tr>
    <th class="row2" align="left">User Status</th>
    <td class="row2"><?=$user_row['userStatus']?></td>
</tr>
<tr>
    <th class="row1" align="left">Join Date</th>
    <td class="row1"><?=$user_row['joinDate']?></td>
</tr>
<tr>
    <th class="row2" align="left">User Type</th>
    <td class="row2"><?=$user_row['userType']?></td>
</tr>
<tr>
    <th class="row1" align="left">Lead</th>
    <td class="row1"><?=$user_row['lead']?></td>
</tr>
</table>
</div>

<? }

include('footer.php');
?>