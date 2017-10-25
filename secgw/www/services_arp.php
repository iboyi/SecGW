<?php
/* $Id$ */
/*
	diag_defaults.php
	Copyright (C) 2004-2009 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

/*
	pfSense_MODULE:	config
*/

##|+PRIV
##|*IDENT=page-diagnostics-factorydefaults
##|*NAME=Diagnostics: Factory defaults page
##|*DESCR=Allow access to the 'Diagnostics: Factory defaults' page.
##|*MATCH=services_arp.php*
##|-PRIV


$pgtitle = array("系统服务", "ARP绑定");
require("guiconfig.inc");
global $config;

$arpcfg = &$config['security']['arp'];

$all_ifinfo = get_all_if_status();
$interfaces = $config['interfaces'];
$arptypes = array('normal' => '正常应答', 'staticarp' => '静态应答', '-arp' => '取消应答');
              
$arplist = trim(str_replace(",","\n",trim($arpcfg['list']))); 
$pconfig['enable'] = isset($config['security']['arp']['enable']);


		 exec("/usr/sbin/arp -an",$rawdata);
		 $data = array();
		 
		 foreach ($rawdata as $line) {
			$elements = explode(' ',$line);
	
			if ($elements[3] != "(incomplete)") {
				$arpent = array();
				$arpent['ip'] = trim(str_replace(array('(',')'),'',$elements[1]));
				$arpent['mac'] = trim($elements[3]);
				$arpent['interface'] = trim($elements[5]);
				$data[] = $arpent;
			}
		 }
		 asort($data);
		 
		 
		 
		 function get_all_if_status() {
	global $config;
	
	$iflist = get_interface_list();
	$if_status = array();
	foreach($config['interfaces'] as $if_name=>$if_info) {
		if(!is_ipaddr($if_info['ipaddr']) ) continue;
		$if_info['mac']     = $iflist[$if_info['if']]['mac'];
		$if_info['status']  = $iflist[$if_info['if']]['up']?'up':'down';
		$if_info['mask']	  = gen_subnet_mask_long( $if_info['subnet'] ) ;
		$if_info['net']		  = $if_info['mask'] & ip2long($if_info['ipaddr']) ;
		$if_status[$if_name]	= $if_info;
	}
	return $if_status;
}

function arp_bind() {
	global $config;
	$interfaces = $config['interfaces'];
	
	if (isset($config['security']['arp']['enable'])) {
		$arplist = str_replace(",","\n",$config['security']['arp']['list']); 
		#$fill = isset($config['security']['arp']['fill']);
		
		if ($arplist !== "") {
		$fd	= fopen('/tmp/arp.txt','w');
		fwrite($fd,$arplist);
		fclose($fd);
		mwexec('/usr/sbin/arp -d -a && /usr/sbin/arp -f /tmp/arp.txt');
		unlink('/tmp/arp.txt');
		}

		foreach($interfaces as $interfacename => $interface) {
			if ($interfacename == "wan" || $interfacename == "lan" || isset($interface['enable'])) {
					mwexec("/sbin/ifconfig {$interface['if']} arp -staticarp");
				if ($interface['arp'] !== "normal")
					mwexec("/sbin/ifconfig {$interface['if']} {$interface['arp']}");
			}
		}
		
	} else {
		foreach($interfaces as $interfacename => $interface) {
					mwexec("/sbin/ifconfig {$interface['if']} arp -staticarp");
		}
		mwexec("/usr/sbin/arp -d -a");
	}
}
		 
		foreach ($data as $entry) {
			$curlist .= $entry['ip'] . " " . $entry['mac'] . "\n";
		}
		$curlist = ipsort(trim($curlist),"\n");
		//lan arp type
		$pconfig['lanarp'] = $config['interfaces']['lan']['arp'];
		//wan arp type
		$pconfig['wanarp'] = $config['interfaces']['lan']['arp'];
		// each interface arp type
		for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
			if (isset($config['interfaces']['opt' . $i]['enable']));
			$pconfig['opt' . $i . 'arp'] == $config['interfaces']['opt' . $i]['arp'];
			}
		
if ($_POST) {
	

	$pconfig = $_POST;
	foreach($interfaces as $interface) {
		unset($interface['arp']);
	}

	if ($_POST['copy']) 
		$arplist = $curlist;

	if ($_POST['save']) {
		unset($lista,$listb,$listc,$errors);
		$lista = trim(str_replace("\n",",",trim($_POST['arplist'])));
		$listb = explode(",", $lista);
		foreach($listb as $listc) {
			$listd = explode(" ", trim($listc));
				if (!is_ipaddr($listd[0]) && $_POST['arplist'] !== "") {
					$input_errors['0'] = "列表包含错误的IP地址:{$listd[0]}";
					input_error_js($input_errors);
				}
				if (!is_macaddr($listd[1]) && $_POST['arplist'] !== "") {
					$input_errors['0'] = "列表包含错误的MAC地址:{$listd[1]}";
					input_error_js($input_errors);
				}
		}
		if ($_POST['arplist'] == "" && isset($_POST['enable'])) {
			$input_errors['0'] = "ARP绑定列表为空，不能启用ARP绑定";
			$input_errors['1'] = "services_arp.php";
			input_error_js($input_errors);
		}
			
		if (!$input_errors) {
			$arpcfg['enable'] = $pconfig['enable'] ? true : false;
			$arpcfg['list'] = ipsort($lista,",");

			foreach($interfaces as $interfacename => $interface) {
				if ($interfacename == "wan" || $interfacename == "lan" || isset($interface['enable'])) {
					$config['interfaces'][$interfacename]['arp'] = $pconfig[$interfacename . 'arp'];
				}
			}
			write_config();
			if (isset($arpcfg['enable'])) {
				arp_bind();
				$errors['0'] = "ARP信息已保存并绑定.";
				$errors['1'] = "services_arp.php";
				input_error_js($errors);
				exit;
			} else {
				$errors['0'] = "ARP信息已保存，但未启用绑定。";
				$errors['1'] = "services_arp.php";
				input_error_js($errors);
				exit;
			}
		}
	}
}

$if	= _get_if();
if( isset($_GET['bindMac']) ) { bindMac(); exit(1);}
$msg	= false;
$_MOD	= array(
		'Arp' 			=>	array('List','Save','Run','Now','Bat','AllSave'), 
);
$bAry	= array(
		'filter'	=> '防火墙规则',
		'shaper'	=> '流量整形',
		'arp'		=> 'MAC绑定表',
		'staticroutes'	=> '静态路由',
		'captiveportal'	=> '入网门户',
	);
$mod	= $_MOD[$_GET['mod']] ? $_GET['mod'] : 'Arp';
$act	= in_array($_GET['act'], $_MOD[$mod])? $_GET['act'] :'List';
if( $act!=='List' ) eval("{$mod}$act();");


function & _get_if(){
	global $config;
	$_if	= get_interface_list();
	$a = array();
	foreach($config['interfaces'] as $k=>$v) {
		if( $k=='wan' || !is_ipaddr($v['ipaddr']) ) continue;
		$v['mac']     = $_if[$v['if']]['mac'];
		$v['status']  = $_if[$v['if']]['up']?'up':'down';
		$v['mask']	  = gen_subnet_mask_long( $v['subnet'] ) ;
		$v['net']		  = $v['mask'] & ip2long($v['ipaddr']) ;
		$a[$k]	= $v;
	}
	return $a;
}
function ArpBat(){
	global $config, $msg, $if;	
	$s	= "@echo off\r\n@color 0A\r\n@echo 		Firewall client arp bind script\r\n@echo #####################################################\r\narp -d\r\n";
	$if	= is_array( $if[$_GET['if']] ) ? $if[$_GET['if']]  : $if['lan'];
	$s	.= "arp -s {$if[ipaddr]} ". preg_replace("/\:/","-", $if['mac']). "\r\n" ;
	$list	= @explode(',', $config['security']['arp']['list']);
	if(is_array($list)) foreach($list as $l){
		if( preg_match("/^([\d\.]+)\s+([\w\:]+)/", $l, $a)){
			if( ($if['mask'] & ip2long($a[1]) ) === $if['net']){	
				$mac	= preg_replace("/\:/", "-", $a[2]);
				$s	.= "arp -s $a[1] $mac\r\n";
			}
		}
	}
	$s	.= "arp -a\r\n@echo #####################################################\r\npause";
	session_cache_limiter('public');
	header("Content-Type: application/octet-stream");
	header("Content-Length: ".strlen($s) );
	header("Content-Disposition: attachment; filename=\"".htmlentities('arp_'.$if['if'].'.cmd')."\"\n");
	die($s);
}





function ipsort($ip2sort,$char){
	unset($lista,$listb,$listc);
	$lista = explode($char, $ip2sort);
	foreach($lista as $listb) {
		$listc = explode(" ", trim($listb));
		$tolong[]=array("ip" => ip2long(trim($listc[0])),
                  "mac" => trim($listc[1])
    );              
	}
	sort($tolong);
	foreach($tolong as $valn){
		$toip .= long2ip($valn['ip']) . " " . $valn['mac'] . $char;
	}
	if ($toip !== ""){
		$toip = substr($toip,0,-1);
	}
	return $toip;
}
?>
<?php include("head.inc"); include("fbegin.inc"); ?>
<style type="text/css">
body {
	background-color: #FFF;
}
.listhdrr div strong {
	color: #FFF;
}
.a55 {
	color: #FFF;
}
</style>
<body>

	
<form action="" method="post">
            <p>&nbsp;</p>
  <table width="75%" height="91" border="1" align="center" cellpadding="0" cellspacing="0" summary="content pane">
    <tr>
      <td height="37" colspan="7" bgcolor="#333333" class="listhdrr"><div align="left"><strong>网络接口信息</strong></div>        
      <div align="center"></div>        <div align="center"></div>        <div align="center"></div>        <div align="center"></div></td>
    </tr>
    <tr valign="top" bgcolor="#FFFFCC">
      <td height="24" bgcolor="#FFF" class="listlr"><div align="center">网络接口</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">网卡</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">IP地址</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">mac地址</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">状态</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">ARP应答方式</div></td>
      <td bgcolor="#FFF" class="listr"><div align="center">客户端脚本</div></td>
    </tr>
		<? foreach($all_ifinfo as $name=>$info) {?>
		 <tr valign="top" bgcolor="#FFFFCC">
		   <td width="10%" height="24" bgcolor="#99CC99" class="listlr"><div align="center">
		     <?=$name?>
	       </div></td>
		   <td width="10%" bgcolor="#99CC99" class="listr"><div align="center">
		     <?=$info['if']?>
	       </div></td>
		   <td width="19%" bgcolor="#99CC99" class="listr"><?=$info['ipaddr'].'/'.$info['subnet']?></td>
		   <td width="17%" bgcolor="#99CC99" class="listr"><?=$info['mac']?></td>
		   <td width="7%" bgcolor="#99CC99" class="listr"><div align="center">
		     <?=$info['status']?>
	       </div></td>
		   <td width="16%" bgcolor="#99CC99" class="listr"><div align="center">
		     <select name="<?=$name?>arp">
		       <?php
                    	foreach ($arptypes as $arptype => $arp) {
                    		echo "<option value=\"$arptype\"";
                    		if ($arptype == $config['interfaces'][$name]['arp'])
                    			echo " selected";
                    		echo ">$arp</option>\n";
                    	}
                    ?>
	         </select>
	       </div></td>
		   <td width="21%" bgcolor="#99CC99" class="listr"><div align="center"><a href='?mod=Arp&act=Bat&if=<?=$name?>'>下载</a></div></td>
    </tr>
		<?}?>
  </table>
  <p>
    <label for="textarea"></label>
  </p>
  <table width="75%" border="1" align="center" cellpadding="0" cellspacing="0" summary="content pane">
    <tr>
      <td height="37" colspan="2" bgcolor="#333333" class="a55"><strong>ARP绑定信息</strong></td>
    </tr>
    <tr bgcolor="#FFF">
          <td width="536" height="506"><div align="center">
            <p>ARP绑定表<br/>
              <textarea name='arplist' style='width: 300px; height: 450px; display: block; margin: 0px 4px; padding: 4px;'><?=$arplist?>
                      </textarea>
            </p>
      </div></td>
          <td width="540"><div align="center">
            <p>当前ARP状态表<br/>
               <textarea name='curlist' cols="" rows="" style='width: 300px; height: 450px; display: block; margin: 0px 4px; padding: 4px;'><?=$curlist?>
              </textarea>
            </p>
      </div></td>
        </tr>
        <tr>
          <td height="35" colspan="2" bgcolor="#FFF"><div align="center">
            <input type="submit" name="copy" id="copy" value="从右侧复制ARP表" />
          </div></td>
        </tr>
        <tr>
          <td height="35" colspan="2" bgcolor="#FFF"><strong>启用ARP绑定</strong>
            <input name="enable" type='checkbox' value='enable'<?php if ($pconfig['enable']) echo "checked"; ?>/>
          <input type='submit' name="save" value='保存'/></td>
          </tr>
        <tr>
        <td colspan="2" bgcolor="#FFF">
        			<div align="center">
        			  <p>&nbsp;</p>
        			  <p align="left"><strong class="red">说明:</strong>
	                  <p align="left">→ 输入格式请参照右侧当前ARP状态表，你也可以点击中间的 &lt;&lt;&lt; 按钮来复制当前ARP状态表到ARP绑定列表。<br />
一旦按下保存，ARP绑定将立刻执行，你可以通过对比ARP绑定列表和当前ARP状态表来判断设置是否生效。<br />
必须勾选启用ARP绑定方可生效。<br />
<strong class="red">ARP应答模式:</strong><br />
1、正常模式，即响应所有的ARP应答，为默认设置。<br />
2、静态应答，只响应已经在绑定表项目的ARP应答，未绑定的客户端将无法与网关通讯(推荐选择)。<br />
3、取消应答，不响应任何ARP应答，不在绑定表的客户端或者客户端没有绑定网关将无法通讯(俗称双绑)。<br />
如果ARP环境恶劣，需要选择模式2或者模式3，请注意绑定好所有ip-mac后，方可启用ARP绑定，否则，将可能导致未绑定客户掉线。                      
          <p align="left">
          <p align="left" >版权声明：ARP绑定插件由天神降临在pfsense2.0中第一次发布，后续版本适配由鐵血男兒完成。pfSense中国社区总群 90565640，欢迎加入。 </p>
          <p align="left"><p align="left">
          <p align="left">                                             
          <p align="left">
            </center>
          <div align="center"></div></td>
          </tr>
        </table>
  <p><br/>
  </p>
  <p>&nbsp;</p>
</form>

<tr>
    <td bgcolor="#e0e0e0" class="vexpl" style='padding:10px'>
<tr></td>
</tr>
</form>

<?php include("foot.inc"); ?>