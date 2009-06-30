<?php
// $Header: /cvsroot/phpldapadmin/phpldapadmin/htdocs/create.php,v 1.48.2.4 2008/12/12 12:20:22 wurley Exp $

/**
 * Creates a new object.
 *
 * Variables that come in as POST vars:
 *  - new_dn
 *  - required_attrs (an array with indices being the attributes,
 *		      and the values being their respective values)
 *  - object_classes (rawurlencoded, and serialized array of objectClasses)
 *
 * @package phpLDAPadmin
 */
/**
 * @todo: posixgroup with empty memberlist generates an error.
 */

require './common.php';

if ($ldapserver->isReadOnly())
	error(_('You cannot perform updates while server is in read-only mode'),'error','index.php');

if (! $_SESSION[APPCONFIG]->isCommandAvailable('entry_create'))
	error(sprintf('%s%s %s',_('This operation is not permitted by the configuration'),_(':'),_('create entry')),'error','index.php');

$rdn_attr = get_request('rdn_attribute');

$entryfactoryclass = $_SESSION[APPCONFIG]->GetValue('appearance','entry_factory');
eval('$entry_factory = new '.$entryfactoryclass.'();');
$entry = $entry_factory->newCreatingEntry('');

eval('$reader = new '.$_SESSION[APPCONFIG]->GetValue('appearance', 'entry_reader').'($ldapserver);');
$entry->accept($reader);

$container = $entry->getContainer();

if (!$container || !$ldapserver->dnExists($container))
	error(sprintf(_('The container you specified (%s) does not exist. Please try again.'),htmlspecialchars($container)),'error','index.php');

$tree = get_cached_item($ldapserver->server_id,'tree');
if ($tree) {
	$container_entry = $tree->getEntry($container);
	if (!$container_entry)
		$tree->addEntry($container);

	$container_entry = $tree->getEntry($container);
	if ($container_entry->isLeaf())
		error(sprintf(_('The container (%s) is a leaf.'), htmlspecialchars($container)),'error','index.php');
}

$entry->setRdnAttributeName($rdn_attr);
if (!$entry->getRdnAttribute())
	error(sprintf(_('The Rdn attribute (%s) does not exist.'), htmlspecialchars($rdn_attr)),'error','index.php');

$new_dn = $entry->getDn();
if (! $new_dn)
	error(_('You left the RDN field blank.'),'error','index.php');

$redirect = get_request('redirect','POST',false,false);

$new_entry = array();
$attrs = $entry->getAttributes();
foreach ($attrs as $attr) {
	$vals = $attr->getValues();
	$new_vals = array();
	foreach ($vals as $val) {
		if (strlen($val) > 0)
			$new_vals[] = $val;
	}

	if ($attr->isRequired() && !$new_vals && !$ldapserver->isIgnoredAttr($attr->getName())) 
			error(sprintf(_('You left the value blank for required attribute (%s).'),htmlspecialchars($attr->getName())),'error','index.php');
	

	if ($new_vals)
		$new_entry[$attr->getName()] = $new_vals;
}

if (! in_array('top', $new_entry['objectClass']))
	$new_entry['objectClass'][] = 'top';

foreach ($new_entry as $attr => $vals) {
	# Check to see if this is a unique Attribute
	if ($badattr = $ldapserver->checkUniqueAttr($new_dn,$attr,$vals)) {
		$search_href = sprintf('?cmd=search&amp;search=true&amp;form=advanced&amp;server_id=%s&amp;filter=%s=%s', $ldapserver->server_id,$attr,$badattr);
		error(sprintf(_('Your attempt to add <b>%s</b> (<i>%s</i>) to <br><b>%s</b><br> is NOT allowed. That attribute/value belongs to another entry.<p>You might like to <a href=\'%s\'>search</a> for that entry.'),$attr,$badattr,$new_dn,$search_href),'error','index.php');
	}
}

# Check the user-defined custom call back first
if (run_hook('pre_entry_create',array('server_id'=>$ldapserver->server_id,'dn'=>$new_dn,'attrs'=>$new_entry)))
	$add_result = $ldapserver->add($new_dn,$new_entry);

if ($add_result) {
	run_hook('post_entry_create',array('server_id'=>$ldapserver->server_id,'dn'=>$new_dn,'attrs'=>$new_entry));

	$action_number = $_SESSION[APPCONFIG]->GetValue('appearance', 'action_after_creation');

	$container = get_container($new_dn,false);
	//$container_container = get_container($container);

	if ($redirect) {
		$redirect_url = $redirect;
	} else if ($action_number == 2) {
		$redirect_url = sprintf('cmd.php?cmd=template_engine&server_id=%s&container=%s', $ldapserver->server_id, rawurlencode($container));
	} else {
		$redirect_url = sprintf('cmd.php?cmd=template_engine&server_id=%s&dn=%s', $ldapserver->server_id, rawurlencode($new_dn));
	}

	if ($action_number == 1 || $action_number == 2)
		printf('<meta http-equiv="refresh" content="0; url=%s" />',$redirect_url);

	if ($action_number == 1 || $action_number == 2) {
		$create_message = sprintf('%s DN%s <b>%s</b> %s',_('Creation successful!'),_(':'),htmlspecialchars($new_dn),_('has been created.'));

		system_message(array(
			'title'=>_('Create Entry'),
			'body'=>$create_message,
			'type'=>'info'),
			$redirect_url);
	} else {
		printf('<h3 class="title">%s</h3>',_('Entry created'));
		echo '<br />';
		echo '<center>';
		printf('<a href="cmd.php?cmd=template_engine&server_id=%s&dn=%s">%s</a>.',$ldapserver->server_id,rawurlencode($new_dn),_('Display the new created entry'));
		echo '<br />';
		printf('<a href="cmd.php?cmd=template_engine&server_id=%s&container=%s">%s</a>.',$ldapserver->server_id,rawurlencode($container),_('Create another entry'));
		echo '</center>';
	}

} else {
	system_message(array(
		'title'=>_('Could not add the object to the LDAP server.'),
		'body'=>ldap_error_msg($ldapserver->error(),$ldapserver->errno()),
		'type'=>'error'));
}
?>
