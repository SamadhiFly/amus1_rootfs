<?php
//$os_info = exec("uname -r", $output, $return_code);
//if (($return_code == 0) && (!empty($os_info)))
//{
//  $os_version = $os_info;
//}
//else
//{
//  $os_version = "";
//}
//

//1.读取当前状态的配置信息
//$result = exec('mod_cli acu /tmp/command_output.txt wjc_hi_all /tmp/hi_config.json',$output,$return_code);
$result = exec('sudo net_config',$output,$return_code);

print_r($result);
echo "<br />";

print_r($output);
echo "<br />";

print_r($return_code);
echo "<br />";

die;


?>

<!--<fieldset>-->
<!--  <legend>Software Information</legend>-->
<!--  <b>OS Version:</b><br />--><?php //echo $os_version; ?><!--<br />-->
<!--</fieldset>-->


