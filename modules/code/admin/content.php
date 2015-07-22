<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2015 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Tue, 14 Jul 2015 04:17:56 GMT
 */

if ( ! defined( 'NV_IS_FILE_ADMIN' ) ) die( 'Stop!!!' );

if ( $nv_Request->isset_request( 'get_alias_title', 'post' ) )
{
	$alias = $nv_Request->get_title( 'get_alias_title', 'post', '' );
	$alias = change_alias( $alias );
	die( $alias );
}

$row = array();
$error = array();
$row['id'] = $nv_Request->get_int( 'id', 'post,get', 0 );
if ( $nv_Request->isset_request( 'submit', 'post' ) )
{
	$row['title'] = $nv_Request->get_title( 'title', 'post', '' );
	$row['alias'] = $nv_Request->get_title( 'alias', 'post', '' );
	$row['alias'] = ( empty($row['alias'] ))? change_alias( $row['title'] ) : change_alias( $row['alias'] );
	$row['description'] = $nv_Request->get_textarea( 'description', '', 'br' );
	$row['code_php'] = $nv_Request->get_textarea( 'code_php', 'post', NV_ALLOWED_HTML_TAGS );
	$row['code_php_template'] = $nv_Request->get_textarea( 'code_php_template', 'post', NV_ALLOWED_HTML_TAGS );
	$row['code_html'] = $nv_Request->get_textarea( 'code_html', '' );
	$row['code_css'] = $nv_Request->get_textarea( 'code_css', '' );
	$row['code_js'] = $nv_Request->get_textarea( 'code_js', 'post', NV_ALLOWED_HTML_TAGS );

	if( empty( $row['title'] ) )
	{
		die( 'NO_' . $lang_module['error_required_title'] );
	}

	try
	{
		if( empty( $row['id'] ) )
		{
			$stmt = $db->prepare( 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . ' (title, alias, description, code_php, code_php_template, code_html, code_css, code_js, adduser, addtime, status) VALUES (:title, :alias, :description, :code_php, :code_php_template, :code_html, :code_css, :code_js, ' . $admin_info['userid'] . ', ' . NV_CURRENTTIME . ', 1)' );
		}
		else
		{
			$stmt = $db->prepare( 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . ' SET title = :title, alias = :alias, description = :description, code_php = :code_php, code_php_template = :code_php_template, code_html = :code_html, code_css = :code_css, code_js = :code_js WHERE id=' . $row['id'] );
		}
		$stmt->bindParam( ':title', $row['title'], PDO::PARAM_STR );
		$stmt->bindParam( ':alias', $row['alias'], PDO::PARAM_STR );
		$stmt->bindParam( ':description', $row['description'], PDO::PARAM_STR, strlen($row['description']) );
		$stmt->bindParam( ':code_php', $row['code_php'], PDO::PARAM_STR, strlen($row['code_php']) );
		$stmt->bindParam( ':code_php_template', $row['code_php_template'], PDO::PARAM_STR, strlen($row['code_php_template']) );
		$stmt->bindParam( ':code_html', $row['code_html'], PDO::PARAM_STR, strlen($row['code_html']) );
		$stmt->bindParam( ':code_css', $row['code_css'], PDO::PARAM_STR, strlen($row['code_css']) );
		$stmt->bindParam( ':code_js', $row['code_js'], PDO::PARAM_STR, strlen($row['code_js']) );

		$exc = $stmt->execute();
		if( $exc )
		{
			$row['code_html'] = nv_unhtmlspecialchars( $row['code_html'] );
			file_put_contents( NV_ROOTDIR . '/' . NV_FILES_DIR . '/' . $module_upload . '/' . $row['alias'] . '-' . $row['id'] . '.tpl', $row['code_html'], LOCK_EX );
			nv_del_moduleCache( $module_name );
			die( 'OK' );
		}
		else
		{
			die( 'NO_' . $lang_module['error_database'] );
		}
	}
	catch( PDOException $e )
	{
		//trigger_error( $e->getMessage() );
		die( 'NO_' . $e->getMessage() ); //Remove this line after checks finished
	}
}
elseif( $row['id'] > 0 )
{
	$row = $db->query( 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . ' WHERE id=' . $row['id'] )->fetch();
	if( empty( $row ) )
	{
		Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name );
		die();
	}
}
else
{
	$row['id'] = 0;
	$row['title'] = '';
	$row['alias'] = '';
	$row['description'] = '';
	$row['code_php'] = '';
	$row['code_php_template'] = '';
	$row['code_html'] = '';
	$row['code_css'] = '';
	$row['code_js'] = '';
}

$row['code_html'] = !empty( $row['code_html'] ) ? nv_unhtmlspecialchars( $row['code_html'] ) : '';

$xtpl = new XTemplate( $op . '.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file );
$xtpl->assign( 'LANG', $lang_module );
$xtpl->assign( 'NV_LANG_VARIABLE', NV_LANG_VARIABLE );
$xtpl->assign( 'NV_LANG_DATA', NV_LANG_DATA );
$xtpl->assign( 'NV_BASE_ADMINURL', NV_BASE_ADMINURL );
$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
$xtpl->assign( 'MODULE_NAME', $module_name );
$xtpl->assign( 'MODULE_UPLOAD', $module_upload );
$xtpl->assign( 'NV_BASE_SITEURL', NV_BASE_SITEURL );
$xtpl->assign( 'MODULE_FILE', $module_file );
$xtpl->assign( 'OP', $op );
$xtpl->assign( 'ROW', $row );

if( ! empty( $error ) )
{
	$xtpl->assign( 'ERROR', implode( '<br />', $error ) );
	$xtpl->parse( 'main.error' );
}
if( empty( $row['id'] ) )
{
	$xtpl->parse( 'main.auto_get_alias' );
}

$xtpl->parse( 'main' );
$contents = $xtpl->text( 'main' );

$page_title = $lang_module['content'];

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme( $contents );
include NV_ROOTDIR . '/includes/footer.php';