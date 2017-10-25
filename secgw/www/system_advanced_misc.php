<?php
/*
 * system_advanced_misc.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2016 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2008 Shrew Soft Inc
 * All rights reserved.
 *
 * originally based on m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

##|+PRIV
##|*IDENT=page-system-advanced-misc
##|*NAME=System: Advanced: Miscellaneous
##|*DESCR=Allow access to the 'System: Advanced: Miscellaneous' page.
##|*MATCH=system_advanced_misc.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("vpn.inc");
require_once("vslb.inc");

$pconfig['proxyurl'] = $config['system']['proxyurl'];
$pconfig['proxyport'] = $config['system']['proxyport'];
$pconfig['proxyuser'] = $config['system']['proxyuser'];
$pconfig['proxypass'] = $config['system']['proxypass'];
$pconfig['harddiskstandby'] = $config['system']['harddiskstandby'];
$pconfig['lb_use_sticky'] = isset($config['system']['lb_use_sticky']);
$pconfig['srctrack'] = $config['system']['srctrack'];
$pconfig['gw_switch_default'] = isset($config['system']['gw_switch_default']);
$pconfig['powerd_enable'] = isset($config['system']['powerd_enable']);
$pconfig['crypto_hardware'] = $config['system']['crypto_hardware'];
$pconfig['thermal_hardware'] = $config['system']['thermal_hardware'];
$pconfig['schedule_states'] = isset($config['system']['schedule_states']);
$pconfig['gw_down_kill_states'] = isset($config['system']['gw_down_kill_states']);
$pconfig['skip_rules_gw_down'] = isset($config['system']['skip_rules_gw_down']);
$pconfig['use_mfs_tmpvar'] = isset($config['system']['use_mfs_tmpvar']);
$pconfig['use_mfs_tmp_size'] = $config['system']['use_mfs_tmp_size'];
$pconfig['use_mfs_var_size'] = $config['system']['use_mfs_var_size'];
$pconfig['do_not_send_uniqueid'] = isset($config['system']['do_not_send_uniqueid']);

$pconfig['powerd_ac_mode'] = "hadp";
if (!empty($config['system']['powerd_ac_mode'])) {
	$pconfig['powerd_ac_mode'] = $config['system']['powerd_ac_mode'];
}

$pconfig['powerd_battery_mode'] = "hadp";
if (!empty($config['system']['powerd_battery_mode'])) {
	$pconfig['powerd_battery_mode'] = $config['system']['powerd_battery_mode'];
}

$pconfig['powerd_normal_mode'] = "hadp";
if (!empty($config['system']['powerd_normal_mode'])) {
	$pconfig['powerd_normal_mode'] = $config['system']['powerd_normal_mode'];
}

$crypto_modules = array(
	'aesni' => gettext("AES-NI CPU-based Acceleration"),
	'cryptodev' => gettext("BSD Crypto Device (cryptodev)")
);

$thermal_hardware_modules = array(
	'coretemp' => gettext("Intel Core* CPU on-die thermal sensor"),
	'amdtemp' => gettext("AMD K8, K10 and K11 CPU on-die thermal sensor"));

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	ob_flush();
	flush();

	if (!empty($_POST['crypto_hardware']) && !array_key_exists($_POST['crypto_hardware'], $crypto_modules)) {
		$input_errors[] = gettext("Please select a valid Cryptographic Accelerator.");
	}

	if (!empty($_POST['thermal_hardware']) && !array_key_exists($_POST['thermal_hardware'], $thermal_hardware_modules)) {
		$input_errors[] = gettext("Please select a valid Thermal Hardware Sensor.");
	}

	if (!empty($_POST['use_mfs_tmp_size']) && (!is_numeric($_POST['use_mfs_tmp_size']) || ($_POST['use_mfs_tmp_size'] < 40))) {
		$input_errors[] = gettext("/tmp Size must be numeric and should not be less than 40MiB.");
	}

	if (!empty($_POST['use_mfs_var_size']) && (!is_numeric($_POST['use_mfs_var_size']) || ($_POST['use_mfs_var_size'] < 60))) {
		$input_errors[] = gettext("/var Size must be numeric and should not be less than 60MiB.");
	}

	if (!empty($_POST['proxyport']) && !is_port($_POST['proxyport'])) {
		$input_errors[] = gettext("Proxy port must be a valid port number, 1-65535.");
	}

	if (!empty($_POST['proxyurl']) && !is_fqdn($_POST['proxyurl']) && !is_ipaddr($_POST['proxyurl'])) {
		$input_errors[] = gettext("Proxy URL must be a valid IP address or FQDN.");
	}

	if (!empty($_POST['proxyuser']) && preg_match("/[^a-zA-Z0-9\.\-_@]/", $_POST['proxyuser'])) {
		$input_errors[] = gettext("The proxy username contains invalid characters.");
	}

	if ($_POST['proxypass'] != $_POST['proxypass_confirm']) {
		$input_errors[] = gettext("Proxy password and confirmation must match.");
	}

	if (!$input_errors) {

		if ($_POST['harddiskstandby'] <> "") {
			$config['system']['harddiskstandby'] = $_POST['harddiskstandby'];
			system_set_harddisk_standby();
		} else {
			unset($config['system']['harddiskstandby']);
		}

		if ($_POST['proxyurl'] <> "") {
			$config['system']['proxyurl'] = $_POST['proxyurl'];
		} else {
			unset($config['system']['proxyurl']);
		}

		if ($_POST['proxyport'] <> "") {
			$config['system']['proxyport'] = $_POST['proxyport'];
		} else {
			unset($config['system']['proxyport']);
		}

		if ($_POST['proxyuser'] <> "") {
			$config['system']['proxyuser'] = $_POST['proxyuser'];
		} else {
			unset($config['system']['proxyuser']);
		}

		if ($_POST['proxypass'] <> "") {
			if ($_POST['proxypass'] != DMYPWD) {
				$config['system']['proxypass'] = $_POST['proxypass'];
			}
		} else {
			unset($config['system']['proxypass']);
		}

		$need_relayd_restart = false;
		if ($_POST['lb_use_sticky'] == "yes") {
			if (!isset($config['system']['lb_use_sticky'])) {
				$config['system']['lb_use_sticky'] = true;
				$need_relayd_restart = true;
			}
			if ($config['system']['srctrack'] != $_POST['srctrack']) {
				$config['system']['srctrack'] = $_POST['srctrack'];
				$need_relayd_restart = true;
			}
		} else {
			if (isset($config['system']['lb_use_sticky'])) {
				unset($config['system']['lb_use_sticky']);
				$need_relayd_restart = true;
			}
		}

		if ($_POST['gw_switch_default'] == "yes") {
			$config['system']['gw_switch_default'] = true;
		} else {
			unset($config['system']['gw_switch_default']);
		}

		if ($_POST['pkg_nochecksig'] == "yes") {
			$config['system']['pkg_nochecksig'] = true;
		} elseif (isset($config['system']['pkg_nochecksig'])) {
			unset($config['system']['pkg_nochecksig']);
		}

		if ($_POST['do_not_send_uniqueid'] == "yes") {
			$config['system']['do_not_send_uniqueid'] = true;
		} else {
			unset($config['system']['do_not_send_uniqueid']);
		}

		if ($_POST['powerd_enable'] == "yes") {
			$config['system']['powerd_enable'] = true;
		} else {
			unset($config['system']['powerd_enable']);
		}

		$config['system']['powerd_ac_mode'] = $_POST['powerd_ac_mode'];
		$config['system']['powerd_battery_mode'] = $_POST['powerd_battery_mode'];
		$config['system']['powerd_normal_mode'] = $_POST['powerd_normal_mode'];

		if ($_POST['crypto_hardware']) {
			$config['system']['crypto_hardware'] = $_POST['crypto_hardware'];
		} else {
			unset($config['system']['crypto_hardware']);
		}

		if ($_POST['thermal_hardware']) {
			$config['system']['thermal_hardware'] = $_POST['thermal_hardware'];
		} else {
			unset($config['system']['thermal_hardware']);
		}

		if ($_POST['schedule_states'] == "yes") {
			$config['system']['schedule_states'] = true;
		} else {
			unset($config['system']['schedule_states']);
		}

		if ($_POST['gw_down_kill_states'] == "yes") {
			$config['system']['gw_down_kill_states'] = true;
		} else {
			unset($config['system']['gw_down_kill_states']);
		}

		if ($_POST['skip_rules_gw_down'] == "yes") {
			$config['system']['skip_rules_gw_down'] = true;
		} else {
			unset($config['system']['skip_rules_gw_down']);
		}

		if ($_POST['use_mfs_tmpvar'] == "yes") {
			$config['system']['use_mfs_tmpvar'] = true;
		} else {
			unset($config['system']['use_mfs_tmpvar']);
		}

		$config['system']['use_mfs_tmp_size'] = $_POST['use_mfs_tmp_size'];
		$config['system']['use_mfs_var_size'] = $_POST['use_mfs_var_size'];

		if (isset($_POST['rrdbackup'])) {
			if (($_POST['rrdbackup'] > 0) && ($_POST['rrdbackup'] <= 24)) {
				$config['system']['rrdbackup'] = intval($_POST['rrdbackup']);
			} else {
				unset($config['system']['rrdbackup']);
			}
		}
		if (isset($_POST['dhcpbackup'])) {
			if (($_POST['dhcpbackup'] > 0) && ($_POST['dhcpbackup'] <= 24)) {
				$config['system']['dhcpbackup'] = intval($_POST['dhcpbackup']);
			} else {
				unset($config['system']['dhcpbackup']);
			}
		}
		if (isset($_POST['logsbackup'])) {
			if (($_POST['logsbackup'] > 0) && ($_POST['logsbackup'] <= 24)) {
				$config['system']['logsbackup'] = intval($_POST['logsbackup']);
			} else {
				unset($config['system']['logsbackup']);
			}
		}

		// Add/Remove RAM disk periodic backup cron jobs according to settings and installation type.
		// Remove the cron jobs on full install if not using RAM disk.
		// Add the cron jobs on all others if the periodic backup option is set.  Otherwise the cron job is removed.
		if (!isset($config['system']['use_mfs_tmpvar'])) {
			/* See #7146 for detail on why the extra parameters are needed for the time being. */
			install_cron_job("/etc/rc.backup_rrd.sh", false, null, null, null, null, null, null, false);
			install_cron_job("/etc/rc.backup_dhcpleases.sh", false, null, null, null, null, null, null, false);
			install_cron_job("/etc/rc.backup_logs.sh", false, null, null, null, null, null, null, false);
		} else {
			/* See #7146 for detail on why the extra parameters are needed for the time being. */
			install_cron_job("/etc/rc.backup_rrd.sh", ($config['system']['rrdbackup'] > 0), $minute="0", "*/{$config['system']['rrdbackup']}", '*', '*', '*', 'root', false);
			install_cron_job("/etc/rc.backup_dhcpleases.sh", ($config['system']['dhcpbackup'] > 0), $minute="0", "*/{$config['system']['dhcpbackup']}", '*', '*', '*', 'root', false);
			install_cron_job("/etc/rc.backup_logs.sh", ($config['system']['logsbackup'] > 0), $minute="0", "*/{$config['system']['logsbackup']}", '*', '*', '*', 'root', false);
		}

		write_config();

		$changes_applied = true;
		$retval = 0;
		system_resolvconf_generate(true);
		$retval |= filter_configure();

		activate_powerd();
		load_crypto();
		load_thermal_hardware();
		if ($need_relayd_restart) {
			relayd_configure();
		}
	}
}

$pgtitle = array(gettext("System"), gettext("Advanced"), gettext("Miscellaneous"));
$pglinks = array("", "system_advanced_admin.php", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
	unset($pconfig['doreboot']);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

$tab_array = array();
$tab_array[] = array(gettext("Admin Access"), false, "system_advanced_admin.php");
$tab_array[] = array(htmlspecialchars(gettext("Firewall & NAT")), false, "system_advanced_firewall.php");
$tab_array[] = array(gettext("Networking"), false, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), true, "system_advanced_misc.php");
$tab_array[] = array(gettext("System Tunables"), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), false, "system_advanced_notifications.php");
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('Proxy Support');

$section->addInput(new Form_Input(
	'proxyurl',
	'Proxy URL',
	'text',
	$pconfig['proxyurl']
))->setHelp('Hostname or IP address of proxy server this system will '.
	'use for its outbound Internet access.');

$section->addInput(new Form_Input(
	'proxyport',
	'Proxy Port',
	'text',
	$pconfig['proxyport']
))->setHelp('Port where proxy server is listening.');

$section->addInput(new Form_Input(
	'proxyuser',
	'Proxy Username',
	'text',
	$pconfig['proxyuser']
))->setHelp('Username for authentication to proxy server. Optional, '.
	'leave blank to not use authentication.');

$section->addPassword(new Form_Input(
	'proxypass',
	'Proxy Password',
	'password',
	$pconfig['proxypass']
))->setHelp('Password for authentication to proxy server.');

$form->add($section);
$section = new Form_Section('Load Balancing');

$group = new Form_Group('Load Balancing');

$group->add(new Form_Checkbox(
	'lb_use_sticky',
	'Use sticky connections',
	'Use sticky connections',
	$pconfig['lb_use_sticky']
))->setHelp('Successive connections will be redirected to the servers in a '.
	'round-robin manner with connections from the same source being sent to the '.
	'same web server. This "sticky connection" will exist as long as there are '.
	'states that refer to this connection. Once the states expire, so will the '.
	'sticky connection. Further connections from that host will be redirected '.
	'to the next web server in the round robin. Changing this option will '.
	'restart the Load Balancing service.');

$group->add(new Form_Input(
	'srctrack',
	'Source tracking timeout',
	'number',
	$pconfig['srctrack'],
	["placeholder" => "0"]
))->setHelp('Set the source tracking timeout for sticky connections. By default '.
	'this is 0, so source tracking is removed as soon as the state expires. '.
	'Setting this timeout higher will cause the source/destination relationship '.
	'to persist for longer periods of time.');

$section->add($group);

$section->addInput(new Form_Checkbox(
	'gw_switch_default',
	'Default gateway switching',
	'Enable default gateway switching',
	$pconfig['gw_switch_default']
))->setHelp('If the default gateway goes down, switch the default gateway to '.
	'another available one. This is not enabled by default, as it\'s unnecessary in '.
	'most all scenarios, which instead use gateway groups.');

$form->add($section);
$section = new Form_Section('Power Savings');

$section->addInput(new Form_Checkbox(
	'powerd_enable',
	'PowerD',
	'Enable PowerD',
	$pconfig['powerd_enable']
))->setHelp('The powerd utility monitors '.
	'the system state and sets various power control options accordingly.  It offers '.
	'four modes (maximum, minimum, adaptive and hiadaptive) that can be individually '.
	'selected while on AC power or batteries. The modes maximum, minimum, adaptive '.
	'and hiadaptive may be abbreviated max, min, adp, hadp.	 Maximum mode chooses the '.
	'highest performance values.  Minimum mode selects the lowest performance values '.
	'to get the most power savings. Adaptive mode attempts to strike a balance by '.
	'degrading performance when the system appears idle and increasing it when the '.
	'system is busy.  It offers a good balance between a small performance loss for '.
	'greatly increased power savings.  Hiadaptive mode is alike adaptive mode, but '.
	'tuned for systems where performance and interactivity are more important than '.
	'power consumption.	 It raises frequency faster, drops slower and keeps twice '.
	'lower CPU load.');

$modes = array(
	'hadp' => gettext('Hiadaptive'),
	'adp' => gettext('Adaptive'),
	'min' => gettext('Minimum'),
	'max' => gettext('Maximum'),
);

$section->addInput(new Form_Select(
	'powerd_ac_mode',
	'AC Power',
	$pconfig['powerd_ac_mode'],
	$modes
));

$section->addInput(new Form_Select(
	'powerd_battery_mode',
	'Battery Power',
	$pconfig['powerd_battery_mode'],
	$modes
));

$section->addInput(new Form_Select(
	'powerd_normal_mode',
	'Unknown Power',
	$pconfig['powerd_normal_mode'],
	$modes
));

$form->add($section);
$section = new Form_Section('Cryptographic & Thermal Hardware');

$section->addInput(new Form_Select(
	'crypto_hardware',
	'Cryptographic Hardware',
	$pconfig['crypto_hardware'],
	['' => gettext('None')] + $crypto_modules
))->setHelp('A cryptographic accelerator module will use hardware support to speed up some cryptographic '.
	'functions on systems which have the chip. '.
	'Loading the BSD Crypto Device module will allow access to acceleration devices using drivers '.
	'built into the kernel, such as Hifn or ubsec chipsets. '.
	'If the firewall does not contain a crypto chip, this option will have no effect. '.
	'To unload the selected module, set this option to "none" and then reboot.');

$section->addInput(new Form_Select(
	'thermal_hardware',
	'Thermal Sensors',
	$pconfig['thermal_hardware'],
	array('' => 'None/ACPI') + $thermal_hardware_modules
))->setHelp('With a '.
	'supported CPU, selecting a thermal sensor will load the appropriate driver to '.
	'read its temperature. Setting this to "None" will attempt to read the '.
	'temperature from an ACPI-compliant motherboard sensor instead, if one is '.
	'present. If there is not a supported thermal sensor chip in the system, this '.
	'option will have no effect. To unload the selected module, set this option to '.
	'"none" and then reboot.');

$form->add($section);
$section = new Form_Section('Schedules');

$section->addInput(new Form_Checkbox(
	'schedule_states',
	'Schedule States',
	'Do not kill connections when schedule expires',
	$pconfig['schedule_states']
))->setHelp('By default, when a schedule expires, connections permitted by that '.
	'schedule are killed. This option overrides that behavior by not clearing states '.
	'for existing connections.');

$form->add($section);
$section = new Form_Section('Gateway Monitoring');

$section->addInput(new Form_Checkbox(
	'gw_down_kill_states',
	'State Killing on Gateway Failure',
	'Flush all states when a gateway goes down',
	$pconfig['gw_down_kill_states']
))->setHelp('The monitoring process will flush all states when a gateway goes down '.
	'if this box is checked.');

$section->addInput(new Form_Checkbox(
	'skip_rules_gw_down',
	'Skip rules when gateway is down',
	'Do not create rules when gateway is down',
	$pconfig['skip_rules_gw_down']
))->setHelp('By default, when a rule has a gateway specified and this gateway is '.
	'down, the rule is created omitting the gateway. This option overrides that '.
	'behavior by omitting the entire rule instead.');

$form->add($section);
$section = new Form_Section('RAM Disk Settings (Reboot to Apply Changes)');

$section->addInput(new Form_Checkbox(
	'use_mfs_tmpvar',
	'Use RAM Disks',
	'Use memory file system for /tmp and /var',
	$pconfig['use_mfs_tmpvar']
))->setHelp('Set this to use /tmp and /var as RAM disks (memory file '.
	'system disks) on a full install rather than use the hard disk. Setting this will '.
	'cause the data in /tmp and /var to be lost. RRD, '.
	'DHCP leases and log directory will be retained. Changing this setting will cause the firewall to reboot after clicking "Save".');

$group = new Form_Group('RAM Disk Size');

$group->add(new Form_Input(
	'use_mfs_tmp_size',
	'/tmp RAM Disk Size',
	'number',
	$pconfig['use_mfs_tmp_size'],
	['placeholder' => 40]
))->setHelp('/tmp RAM Disk<br />Do not set lower than 40.');

$group->add(new Form_Input(
	'use_mfs_var_size',
	'/var RAM Disk Size',
	'number',
	$pconfig['use_mfs_var_size'],
	['placeholder' => 60]
))->setHelp('/var RAM Disk<br />Do not set lower than 60.');

$group->setHelp('Sets the size, in MiB, for the RAM disks.');

$section->add($group);

$group = new Form_Group('Periodic RAM Disk Data Backups');

$group->add(new Form_Input(
	'rrdbackup',
	'Periodic RRD Backup',
	'number',
	$config['system']['rrdbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('RRD Data');

$group->add(new Form_Input(
	'dhcpbackup',
	'Periodic DHCP Leases Backup',
	'number',
	$config['system']['dhcpbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('DHCP Leases');

$group->add(new Form_Input(
	'logsbackup',
	'Periodic Logs Backup',
	'number',
	$config['system']['logsbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('Log Directory');

$group->setHelp('Sets the interval, in hours, to periodically backup these portions of RAM disk data so '.
	'they can be restored automatically on the next boot. Keep in mind that the more '.
	'frequent the backup, the more writes will happen to the media.');

$section->add($group);

$form->add($section);

$section = new Form_Section('Hardware Settings');

$opts = array(0.5,  1, 2,  3,  4,  5,  7.5,  10,  15,  20,  30,  60);
$vals = array(  6, 12, 24, 36, 48, 60,  90, 120, 180, 240, 241, 242);

$section->addInput(new Form_Select(
	'harddiskstandby',
	'Hard disk standby time',
	$pconfig['harddiskstandby'],
	['' => gettext("Always on")] + array_combine($opts, $vals)
))->setHelp('Puts the hard disk into standby mode when the selected number of minutes has elapsed since the last access.%1$s' .
			'%2$sDo not set this for CF cards.%3$s', '<br />', '<strong>', '</strong>');

$form->add($section);

$section = new Form_Section('Installation Feedback');

$section->addInput(new Form_Checkbox(
	'do_not_send_uniqueid',
	'Netgate Device ID',
	'Do NOT send Netgate Device ID with user agent',
	$pconfig['do_not_send_uniqueid']
))->setHelp('Enable this option to not send Netgate Device ID to pfSense as part of User-Agent header.');

$form->add($section);

print $form;

$ramdisk_msg = gettext('The \"Use Ramdisk\" setting has been changed. This will cause the firewall\nto reboot immediately after the new setting is saved.\n\nPlease confirm.');?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// Record the state of the Use Ramdisk checkbox on page load
	use_ramdisk = $('#use_mfs_tmpvar').prop('checked');

	$('form').submit(function(event) {
		// Has the Use ramdisk checkbox changed state?
		if ($('#use_mfs_tmpvar').prop('checked') != use_ramdisk) {
			if (confirm("<?=$ramdisk_msg?>")) {
				$('form').append('<input type="hidden" name="doreboot" id="doreboot" value="yes"/>');
			} else {
				event.preventDefault();
			}
		}
	});

	drb = "<?=$pconfig['doreboot']?>";

	if (drb == "yes") {
		$('form').append("<input type=\"hidden\" name=\"override\" value=\"yes\" />");
		$('form').get(0).setAttribute('action', 'diag_reboot.php');
		$(form).submit();
	}

	// source track timeout field is disabled if sticky connections not enabled
	$('#lb_use_sticky').click(function () {
		disableInput('srctrack', !$(this).prop("checked"));
	});

	disableInput('srctrack', !$('#lb_use_sticky').prop("checked"));

});
//]]>
</script>

<?php
include("foot.inc");