<?php
// $Header: /cvsroot/phpldapadmin/phpldapadmin/htdocs/entry_chooser.php,v 1.31.2.5 2008/12/12 12:20:22 wurley Exp $

/**
 * Display a selection (popup window) to pick a DN.
 *
 * @package phpLDAPadmin
 */

include './common.php';
include HTDOCDIR.'header.php';

$entry = array();
$entry['container'] = get_request('container','GET');
$entry['element'] = get_request('form_element','GET');
$entry['rdn'] = get_request('rdn','GET');

echo '<body>';
echo '<div class="popup">';
printf('<h3 class="subtitle">%s</h3>',_('Entry Chooser'));
?>

<script type="text/javascript" language="javascript">
	function returnDN(dn) {
		opener.document.<?php echo $entry['element']; ?>.value = dn;
		close();
	}
</script>

<?php
echo '<table class="forminput" width=100% border=0>';
if ($entry['container']) {
	printf('<tr><td class="heading" colspan=3>%s:</td><td>%s</td></tr>',_('Server'),htmlspecialchars($ldapserver->name));
	printf('<tr><td class="heading" colspan=3>%s:</td><td>%s</td></tr>',_('Looking in'),htmlspecialchars($entry['container']));
	echo '<tr><td class="blank" colspan=4>&nbsp;</td></tr>';
}

/* Has the use already begun to descend into a specific server tree? */
if (isset($ldapserver) && ! is_null($entry['container'])) {

	if (! $ldapserver->haveAuthInfo())
		error(_('Not enough information to login to server. Please check your configuration.'),'error','index.php');

	$entry['children'] = $ldapserver->getContainerContents($entry['container'],0,'(objectClass=*)',$_SESSION[APPCONFIG]->GetValue('deref','tree'));
	sort($entry['children']);

	foreach ($ldapserver->getBaseDN() as $base_dn) {
		if (DEBUG_ENABLED)
			debug_log('Comparing BaseDN [%s] with container [%s]',64,__FILE__,__LINE__,__METHOD__,$base_dn,$entry['container']);

		if (! pla_compare_dns($entry['container'],$base_dn)) {
			$parent_container = false;
			$href['up'] = htmlspecialchars(sprintf('entry_chooser.php?form_element=%s&rdn=%s',$entry['element'],$entry['rdn']));
			break;

		} else {
			$parent_container = get_container($entry['container']);
			$href['up'] = htmlspecialchars(sprintf('entry_chooser.php?form_element=%s&rdn=%s&server_id=%s&container=%s',
				$entry['element'],$entry['rdn'],$ldapserver->server_id,rawurlencode($parent_container)));
		}
	}

	echo '<tr>';
	echo '<td class="blank">&nbsp;</td>';
	printf('<td class="icon"><a href="%s"><img src="%s/up.png" alt="Up" /></a></td>',$href['up'],IMGDIR);
	printf('<td colspan=2><a href="%s">%s</a></td>',$href['up'],_('Back Up...'));
	echo '</tr>';

	if (! count($entry['children']))
		printf('<td class="blank" colspan=2>&nbsp;</td><td colspan=2">(%s)</td>',_('no entries'));

	else
		foreach ($entry['children'] as $dn) {
			$href['return'] = sprintf("javascript:returnDN('%s%s')",($entry['rdn'] ? sprintf('%s,',$entry['rdn']) : ''),rawurlencode($dn));
			$href['expand'] = htmlspecialchars(sprintf('entry_chooser.php?server_id=%s&form_element=%s&rdn=%s&container=%s',
				$ldapserver->server_id,$entry['element'],$entry['rdn'],rawurlencode($dn)));

			echo '<tr>';
			echo '<td class="blank">&nbsp;</td>';
			printf('<td class="icon"><a href="%s"><img src="%s/plus.png" alt="Plus" /></a></td>',$href['expand'],IMGDIR);

			printf('<td colspan=2><a href="%s">%s</a></td>',$href['return'],htmlspecialchars($dn));
			echo '</tr>';
			echo "\n\n";
		}

/* draw the root of the selection tree (ie, list all the servers) */
} else {
	foreach ($_SESSION[APPCONFIG]->ldapservers->GetServerList() as $id) {

		$ldapserver = $_SESSION[APPCONFIG]->ldapservers->Instance($id);

		if ($ldapserver->isVisible()) {

			if (! $ldapserver->haveAuthInfo())
				continue;

			else {
				printf('<tr><td class="heading" colspan=3>%s:</td><td class="heading">%s</td></tr>',_('Server'),htmlspecialchars($ldapserver->name));
				foreach ($ldapserver->getBaseDN() as $dn) {
					if (! $dn) {
						printf('<tr><td class="blank">&nbsp;</td><td colspan=3>(%s)</td></tr>',_('Could not determine base DN'));

					} else {
						$href['return'] = sprintf("javascript:returnDN('%s%s')",($entry['rdn'] ? sprintf('%s,',$entry['rdn']) : ''),rawurlencode($dn));
						$href['expand'] = htmlspecialchars(sprintf('entry_chooser.php?server_id=%s&form_element=%s&rdn=%s&container=%s',
							$ldapserver->server_id,$entry['element'],$entry['rdn'],rawurlencode($dn)));

						echo '<tr>';
						echo '<td class="blank">&nbsp;</td>';
						printf('<td colspan=2 class="icon"><a href="%s"><img src="%s/plus.png" alt="Plus" /></a></td>',$href['expand'],IMGDIR);
						printf('<td colspan=2><a href="%s">%s</a></td>',$href['return'],htmlspecialchars($dn));
					}
				}

				echo '<tr><td class="blank" colspan=4>&nbsp;</td></tr>';
			}
		}
	}
}

echo '</table>';
echo '</div>';
echo '</body>';
?>
