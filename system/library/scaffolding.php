<?php
// 
//  scaffolding.php
//  surf
//  
//  Created by Craig Hoover on 2010-07-26.
//  Copyright 2010 __MyCompanyName__. All rights reserved.
// 

session_start();

/*
* function dependency check
*/

setDependencies('getAppVar','setAppVar');

/**
 * undocumented function
 *
 * @return array
 * @author Craig Hoover
 */
function generateSchema()
{
	$schema = setAppVar('schema', array());	
	$conn = getAppVar('conn');
	$db = getAppVar('database');	
	
	$sql = 'SHOW TABLES FROM `'.$db['name'].'`';
	$res = mysql_query($sql, $conn);
	
	if($res && mysql_num_rows($res) > 0)
	{
		while($row = mysql_fetch_array($res))
		{
			$schema[$row[0]] = getTableInfo($row[0]);
		}
	}
	else
	{
		trigger_error('error getting tables or no tables exist.', E_USER_WARNING);
	}
	
	setAppVar('schema', $schema);
	
	return $schema;
}

/**
 * undocumented function
 *
 * @return array
 * @author Craig Hoover
 */
function getSchema()
{
	return appVarIsset('schema') ? getAppVar('schema') : generateSchema();
}

/**
 * undocumented function
 *
 * @param string $table 
 * @return array
 * @author Craig Hoover
 */
function getTableInfo($table)
{
	$conn = getAppVar('conn');
	$db = getAppVar('database');
	$schema = getSchema();

	if($table == '') return false;

	if(!isset($schema[$table]))
	{
		$fields = mysql_list_fields($db['name'], $table, $conn);
	
		if($fields)
		{
			$sql = 'SHOW COLUMNS FROM `' . $table. '`';
			$columns = mysql_query($sql);
		
			if($columns)
			{		
				$num = mysql_num_fields($fields);
			
				for($i=0; $i < $num; ++$i)
				{
					$column = mysql_fetch_array($columns);
					$field = mysql_fetch_field($fields, $i);
					$flags = mysql_field_flags($fields, $i);
					$len = mysql_field_len($fields, $i);
					$size = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM `$table`",$conn));
						
					$flags = $flags == '' ? array() : explode(' ',$flags);
					
					if (in_array('primary_key',$flags))
					{
						$result_fields['primary_key'] = $field->name;
					}
				
					if (ereg('enum.(.*).',$column['Type'],$match))
					{
						$field->type = 'enum';
						$field->values = explode(',',$match[1]);
					}
				
					if (ereg('set.(.*).',$column['Type'],$match))
					{
						$field->type = 'enum';
						$field->values = explode(',',$match[1]);
					}
				
					if (ereg('tinyint.(1).',$column['Type'],$match))
					{
						$field->type = 'boolean';
					}
				
					if(!property_exists($field, 'values')) $field->values = array();
				
					$field->default  = $field->def;
					unset($field->def);
					$field->length = $len;
					$result_fields['record_count'] = $size[0];
					$result_fields[$field->name] = $field;
				}
				mysql_free_result($columns);
			}
		
			mysql_free_result($fields);
			
			return $result_fields;
		}
	}
	else
	{
		return $schema[$table];
	}
}

/**
 * undocumented function
 *
 * @return void
 * @author Craig Hoover
 */
function copyRecord()
{
	$schema 	= getSchema();
	$config	= getAppVar('scaffolding');
	$conn 	= getAppVar('conn');
	$errors 	= array();
	$table 	= $_GET['table'];
	$key 		= $schema[$table]['primary_key']; // which field our primary key is	
	$id		= isset($_GET['id']) ? $_GET['id'] : '';
	
	$cols = array_keys(array_filter($schema[$table], create_function('$x','return is_object($x) && $x->type != "timestamp" && $x->name != "'.$key.'";')));
	
	if(!empty($id))
	{
		$sql = "INSERT INTO `$table` (".implode(',',$cols).") SELECT ".implode(',',$cols)." FROM `$table` WHERE $key = '".mysql_real_escape_string($id)."'";
		$result = @mysql_query($sql, $conn);
		
		return mysql_affected_rows($conn);
	}
	return false;
}


/**
 * undocumented function
 *
 * @return boolean
 * @author Craig Hoover
 */
function saveRecord()
{
	$schema 	= getSchema();
	$config	= getAppVar('scaffolding');
	$conn 	= getAppVar('conn');
	$errors 	= array();
	$table 	= $_POST['table'];
	$key 		= $schema[$table]['primary_key']; // which field our primary key is
	$mode 	= isset($_POST['id']) && !empty($_POST['id']) ? 'update' : 'create';
	$stmt 	= array(); // stores sql value info
	$cols 	= array(); // stores sql column info
	$nonulltest = array('datetime','timestamp','date','time'); // these field can't be null tested since they are a concatenation of several fields
	
	foreach($schema[$table] as $field)
	{
		$fieldnamevalid = property_exists($field,'name');
		$fieldiskey = property_exists($field, 'primary_key') && $field->primary_key;
		$fieldiseditable = $fieldnamevalid && (in_array($field->name, $config['columnsForEdit'][$table]) || array_key_exists($field->name, $config['columnsForEdit'][$table]));
		
		// ignore fields that match these conditions
		if(!$fieldnamevalid || $fieldiskey || !$fieldiseditable) continue;
		
		$name = $field->name;
		$value = in_array($field->type,$nonulltest) ? null : trim($_POST[$name]);
		$cols[] = $name;
		
		// perform null test on fields NOT in our 'nonulltest' array
		if($value === '' && $field->not_null)
		{
			$errors[] = readable($name).' is a required field.';
		}

		switch($field->type)
		{
			case 'boolean':
			case 'int':
				$stmt[] = $mode == 'create' ? sprintf("%d",$value) : sprintf("`$name` = %d",$value);
			break;
		
			case 'blob';
			case 'string':
				$stmt[] = $mode == 'create' ? sprintf("'%s'",$value) : sprintf("`$name` = '%s'", mysql_real_escape_string($value));
			break;
			
			case 'datetime':
			case 'timestamp':
				$value = date('Y-m-d H:i:s',mktime($_POST["{$name}_hour"],$_POST["{$name}_minute"],0,$_POST["{$name}_month"],$_POST["{$name}_day"],$_POST["{$name}_year"]));
				$stmt[] = $mode == 'create' ? sprintf("'%s'",$value) : sprintf("`$name` = '%s'", $value);
			break;
			
			case 'date':
				$value = date('Y-m-d',mktime(0,0,0,$_POST["{$name}_month"],$_POST["{$name}_day"],$_POST["{$name}_year"]));
				$stmt[] = $mode == 'create' ? sprintf("'%s'",$value) : sprintf("`$name` = '%s'", $value);
			break;
			
			case 'time':
				$value = date('H:i:s',mktime($_POST["{$name}_hour"],$_POST["{$name}_minute"],0,1,1,date('Y')));
				$stmt[] = $mode == 'create' ? sprintf("'%s'",$value) : sprintf("`$name` = '%s'", $value);
			break;		
		}
		
	}

	if(count($errors) == 0)
	{
		// check posted mode for SQL creation
		if($mode == 'create')
		{
			$sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $stmt) .")";
		}
		else
		{
			$sql = "UPDATE `$table` SET ".implode(',', $stmt). " WHERE `" . $key ."` = " . mysql_real_escape_string($_POST[$key]);
		}
	
		// run query
		$result = @mysql_query($sql, $conn);
	
		// check for errors
		if(!$result)
		{
			$errors[] = mysql_error($conn);
		}
	}
	
	// errors occurred, display them and return FALSE
	if(count($errors))
	{
		setMessage(implode('<br />', $errors));
		return false;
	}
	else // no errors, return TRUE
	{
		return true;
	}	
}

/**
 * undocumented function
 *
 * @return void
 * @author Craig Hoover
 */
function removeRecord($table, $id)
{	
	$conn = getAppVar('conn');
	$schema = getSchema();	
	$pkey = $schema[$table]['primary_key'];
	
	$sql = "DELETE FROM `" . mysql_real_escape_string($table) ."` WHERE $pkey ='" . mysql_real_escape_string($id). "' LIMIT 1";	
	$result = mysql_query($sql, $conn);
	
	return mysql_affected_rows($conn);	
}

/**
 * undocumented function
 *
 * @return array
 * @author Craig Hoover
 */
function getRecordFromPost()
{
	$record = Array();
	
	foreach($_POST as $key => $value)
	{
		$record[$key] = $value;
	}
	return $record;
}

/**
 * undocumented function
 *
 * @param string $table 
 * @param string $id 
 * @return void
 * @author Craig Hoover
 */
function getRecord($table = '',$id = '')
{
	$conn = getAppVar('conn');
	$db = getAppVar('database');
	$schema = getSchema();
	$sql = "SELECT * FROM `$table` WHERE " . $schema[$table]['primary_key'] . " = '".mysql_real_escape_string($id)."'";
	$res = mysql_query($sql, $conn);
	
	if($res && mysql_num_rows($res) > 0)
	{
		return mysql_fetch_assoc($res);
	}
	else
	{
		return false;
	}
}

/**
 * undocumented function
 *
 * @param string $table 
 * @return void
 * @author Craig Hoover
 */
function newRecord($table)
{
	$schema = getSchema();
	$record = array();
	
	foreach($schema[$table] as $field => $attrs)
	{	
		if(property_exists($attrs,'name'))
		{
			$record[$attrs->name] = '';
		}
	}
	return $record;	
}

/**
 * undocumented function
 *
 * @param string $table 
 * @param string $order 
 * @param string $limit 
 * @return void
 * @author Craig Hoover
 */
function getRecords($table, $order = '', $limit = '')
{
	$conn = getAppVar('conn');
	$db = getAppVar('database');
	$schema = getSchema();
	$sql = "SELECT * FROM `$table`".(empty($order) ? '' : " ORDER BY $order").(empty($limit) ? '' : " LIMIT $limit");
	$res = mysql_query($sql, $conn);
	$data = array();
	
	if($res && mysql_num_rows($res) > 0 )
	{
		while($row = mysql_fetch_assoc($res))
		{
			$data[] = $row;
		}
	}	
	mysql_free_result($res);
	return $data;
}

/**
 * undocumented function
 *
 * @param string $path 
 * @param string $query 
 * @param string $params 
 * @param string $perpage 
 * @return void
 * @author Craig Hoover
 */
function getPagedRecords($path, $query, $params,  $perpage = 10)
{
	$conn = getAppVar('conn');

	$h = "";
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	$start = floor($page -1) * $perpage;
	$res = mysql_query($query, $conn);
	$count = mysql_num_rows($res);
	$pages = $count < $perpage ? 1 : floor(($count + $perpage - 1) / $perpage);
	$range = range(1,$pages);	
	$records = array();
	
	if($res && mysql_num_rows($res) > 0)
	{
		while($row = mysql_fetch_assoc($res))
		{
			$records[] = $row;
		}
	}
	mysql_free_result($res);
	
	$records = array_slice($records, $start, $perpage);
	$params = empty($params) ? "?page=" : "?$params&amp;page=" ;
	
	$tonav = create_function('$k', 'return $k != "'.$page.'" ? " <a href=\"'.$path. $params. '$k\">".$k."</a>"  :" <strong>".$k."</strong>";');
	
	$h .= "<div class=\"scaffolded_pagination\">Goto Page: ";
	$h .= implode('',array_map($tonav, $range));
	$h .= "</div>";
	
	return array($records, $h);
}

/**
 * undocumented function
 *
 * @param string $path 
 * @param string $table 
 * @param string $record 
 * @return void
 * @author Craig Hoover
 */
function generateRecordForm($path, $table, $record)
{
	$schema = getSchema();
	$options = getAppVar('scaffolding');
	$conn = getAppVar('conn');
	$contents = '';	
	$contents .= hidden('action','save');

	if(!array_key_exists($table,$options['columnsForEdit']))
	{
		echo "Missing [columnsForEdit] custom configuration for table [".$table."].";
	}
	
	$fields = array();
	$fieldsorder = array();
	
	// create ordered array list of specified fields
	foreach($options['columnsForEdit'][$table] as $key => $value)
	{
		$fieldsorder[] = is_numeric($key) ? $value : $key;
	}

	foreach($schema[$table] as $name => $attr)
	{					
		if(!property_exists($attr, 'type')) continue;
		
		$contents .= @$attr->primary_key ? hidden($attr->name, $record[$attr->name]) : '';
	
		$columnavail = in_array($name, $options['columnsForEdit'][$table]);
		$columnhaslabel = array_key_exists($name, $options['columnsForEdit'][$table]);
		$columniskey	=	$attr->primary_key;
	
		if(($columnavail || $columnhaslabel) && !$columniskey)
		{
			$label = $columnhaslabel ? $options['columnsForEdit'][$table][$name] : '';
			
			if(columnHasLookup($table, $attr->name))
			{
				$fields[$attr->name] = select($attr->name, getLookupValues($table, $attr->name), $record[$attr->name], true, $label, true);
			}
			else
			{			
				switch($attr->type)
				{
					case 'string':
						$fields[$attr->name] = input($attr->name, $record[$attr->name], true, $label);
					break;

					case 'blob':
						$fields[$attr->name] = textarea($attr->name, $record[$attr->name], true, $label);
					break;
	
					case 'boolean':
						$fields[$attr->name] = checkbox($attr->name, 1, $record[$attr->name] == 1, true, $label);
					break;

					case 'date':
						$fields[$attr->name] = dates($attr->name, $record[$attr->name], true, $label);
					break;
	
					case 'time':
						$fields[$attr->name] = times($attr->name, $record[$attr->name], true, $label, true);
					break;
	
					case 'datetime':
						$fields[$attr->name] = datestamp($attr->name, $record[$attr->name], true, $label, true);
					break;
	
					case 'timestamp':
						$fields[$attr->name] = datestamp($attr->name, $record[$attr->name], true, $label, true);
					break;

					case 'int':
						$fields[$attr->name] = input($attr->name, $record[$attr->name], $label);
					break;
				}
			}
		}
	}

	// output our fields as specified in config
	foreach($fieldsorder as $fieldname)
	{
		$contents .= $fields[$fieldname];
	}
	
	$extra = "<div class=\"scaffolded_back\"><a href=\"".$_SERVER['HTTP_REFERER']."\">Back</a></div>";
	echo form($path, $table, $contents, 'post', $extra);	
}

/**
 * undocumented function
 *
 * @param string $path 
 * @param string $table 
 * @param string $columns 
 * @param string $actions 
 * @return void
 * @author Craig Hoover
 */
function generateRecordList($path, $table, $columns, $actions)
{
	$schema = getSchema();
	$scaffold = getAppVar('scaffolding');
	$sql = "SELECT * FROM `$table`";
	$params = "table=$table&amp;action=view";
	$column = '';
	$sort = '';
	$opp = '';

	if(isset($_GET['sort']) && isset($_GET['column']))
	{
		$column = $_GET['column'];
		$dir = $_GET['sort'];
		
		if(array_key_exists($column, $schema[$table]))
		{
			switch($dir)
			{
				case 'desc':
					$opp = 'asc';
				break;
				
				default;
				case 'asc':
					$opp = 'desc';
				break;
			}
			
			$sql .= " ORDER BY $column $dir";
			$params .= "&amp;sort=$dir&amp;column=$column";
		}
	}

	$data = getPagedRecords($path, $sql, $params, $scaffold['resultsPerPage']);
	
	$records = $data[0];
	$pagination = $data[1];
	$columns = count($columns) == 0 ? array_keys($schema[$table]) : $columns;
	$pkey = $schema[$table]['primary_key'];
	$headers = array_filter($columns, create_function('$h','return !preg_match("/primary_key|record_count|'.$pkey.'/",$h);'));
	$headrow = "";

	foreach($headers as $name => $value)
	{
		$h = is_numeric($name) ? $value : $name;
		$headrow .= "<th class=\"$h".($column == $h ? " sorted" : "")."\"><a href=\"$path?table=$table&amp;action=view&amp;sort=$opp&amp;column=$h\">".readable($value)."</a></th>";
	}

	$h = "\n<div class=\"scaffolded_records $table\">";
	$h .= "\n<table>";
	$h .= "\n<thead>";
	$h .= "\n<tr>" . $headrow . "<th class=\"action\" colspan=\"".count($actions[$table])."\">Actions</th></tr>";
	$h .= "\n</thead>";
	$h .= "\n<tbody>";

	$i = 0;
	$ignore = array('primary_key','record_count', $pkey);
	
	foreach($records as $row)
	{		
		$key = $row[$pkey];
		$h .= "\n<tr class=\"record".($i % 2 == 0 ? ' even' : ' odd')."\" id=\"{$table}_$key\" title=\"Double-click to edit\" ondblclick=\"window.location='$path?table=$table&amp;action=edit&amp;id=$key'\">";		
		
		foreach($columns as $column => $alttext)
		{		
			$column = is_numeric($column) ? $alttext : $column;
			$sc = $schema[$table][$column];
						
			if(in_array($column, $ignore)) continue; 
			
			$type = $schema[$table][$column]->type;
			$td = "\n<td class=\"$column\">%s</td>";
			$value = $row[$column];
			
			if(columnHasLookup($table, $column))
			{
				$h .= sprintf($td, getLookupValue($table, $column, $value));				
			}
			else
			{	
				switch($type)
				{
					case 'blob':
						$h .= sprintf($td, substr($value,0, 55));
					break;
		
					case 'boolean':
						$h .= sprintf($td, ($value == 1 ? 'True'  : 'False'));
					break;
		
					case 'datetime':
					case 'timestamp':
						$h .= sprintf($td, date('n/j/Y g:iA',strtotime($value)));
					break;
		
					case 'date':
						$h .= sprintf($td, date('n/j/Y',strtotime($value)));
					break;
		
					case 'time':
						$h .= sprintf($td, date('g:iA',strtotime($value)));
					break;
		
					case 'int':
						$h .= sprintf($td, $value);
					break;
			
					default:
					case 'string':
						$h .= sprintf($td, $value);
					break;
				}
			}
		}				
		
		$i+=1;
		
		$h .= in_array('view', $actions[$table]) ? "<td class=\"action\"><a class=\"view\" href=\"$path?table=$table&amp;action=view&amp;id=$key\">View</a></td>" : '';	
		$h .= in_array('edit', $actions[$table]) ? "<td class=\"action\"><a class=\"edit\" href=\"$path?table=$table&amp;action=edit&amp;id=$key\">Edit</a></td>" : '';	
		$h .= in_array('copy', $actions[$table]) ? "<td class=\"action\"><a class=\"copy\" href=\"$path?table=$table&amp;action=copy&amp;id=$key\" onclick=\"return confirm('Are you sure you want to copy this record?')\">Copy</a></td>" : '';
		$h .= in_array('remove', $actions[$table]) ? "<td class=\"action\"><a hclass=\"remove\" href=\"$path?table=$table&amp;action=remove&amp;id=$key\" onclick=\"return confirm('Are you sure you want to remove this record?')\">Remove</a></td>" : '';			
		$h .= "</tr>";
	}
	
	$h .= "\n</tbody>";
	$h .= "\n</table>";
	$h .=  "\n$pagination";
	$h .= "\n<div class=\"scaffolded_back\"><a href=\"$path\">Back</a></div>";
	$h .= "\n</div>";
	
	echo $h;
	
}

/**
 * undocumented function
 *
 * @param string $table 
 * @param string $column 
 * @return void
 * @author Craig Hoover
 */
function columnHasLookup($table, $column)
{
	$config = getAppVar('scaffolding');
	return isset($config['hasLookupColumn'][$table][$column]);
}

/**
 * undocumented function
 *
 * @param string $table 
 * @param string $column 
 * @return void
 * @author Craig Hoover
 */
function getLookupValues($table, $column)
{
	$config = getAppVar('scaffolding');
	$conn = getAppVar('conn');

	$res = mysql_query($config['hasLookupColumn'][$table][$column], $conn);
	$ops = array();
	
	if($res && mysql_num_rows($res) > 0)
	{
		$label = mysql_field_name($res, 1);
		while($row = mysql_fetch_array($res))
		{
			$ops[$row[0]] = $row[1];
		}
	}
	return $ops;
}

/**
 * undocumented function
 *
 * @param string $table 
 * @param string $column 
 * @param string $key 
 * @return void
 * @author Craig Hoover
 */
function getLookupValue($table, $column, $key)
{
	$config = getAppVar('scaffolding');
	$conn = getAppVar('conn');	

	$res = mysql_query($config['hasLookupColumn'][$table][$column], $conn);
	$ops = array();
	
	if($res && mysql_num_rows($res) > 0)
	{
		$label = mysql_field_name($res, 1);
		while($row = mysql_fetch_array($res))
		{
			if($key == $row[0]) return $row[1];
		}
	}
	return "(none)";
}

/**
 * undocumented function
 *
 * @param string $option 
 * @return void
 * @author Craig Hoover
 */
function getSchemaColumnDefaults()
{
	$schema = getSchema();
	$result = array();
	foreach($schema as $name => $cols)
	{
		if(is_array($cols) && count($cols) > 0)
		{
			$result[$name] = array_keys($cols);
		}
	}
	return $result;
}

/**
 * undocumented function
 *
 * @return void
 * @author Craig Hoover
 */
function getSchemaUsageDefaults()
{
	$schema = getSchema();
	$result = array();
	$cando = array('view','new','export','empty','edit','copy','remove');	
	
	foreach($schema as $name => $cols)
	{
		$result[$name] = $cando;
	}
	return $result;
}

/**
 * undocumented function
 *
 * @param string $msg 
 * @return void
 * @author Craig Hoover
 */
function setMessage($msg = '')
{
	$_SESSION['message'] = $msg;
}

/**
 * undocumented function
 *
 * @return void
 * @author Craig Hoover
 */
function getMessage($error = false)
{
	if(isset($_SESSION['message']) && !empty($_SESSION['message']))
	{
		$message = sprintf("<div class=\"scaffolded_message%s\">%s</div>", ($error ? ' with_error' : ''), $_SESSION['message']);
		$_SESSION['message'] = '';
		echo $message;
	}	
}

/**
 * undocumented function
 *
 * @param string $options 
 * @return void
 * @author Craig Hoover
 */
function generateScaffold($config = array())
{	
	$schema	= generateSchema();
	$coldef	= getSchemaColumnDefaults();
	$usage 	= getSchemaUsageDefaults();
	$table 	= isset($_REQUEST['table']) ? $_REQUEST['table'] : '';
	$action 	= isset($_REQUEST['action']) ? ($_SERVER['REQUEST_METHOD'] == 'POST' ? 'save' : $_REQUEST['action']) : '';
	$id 		= isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
	$tables	=	array_keys($schema);
	
	$contents 	= '';
	$defaults 	= array(
		'submitRoute' => $_SERVER['PATH_INFO'], 
		'resultsPerPage' => 10,
		'allowedTables' => $tables,
		'allowedActions' => $usage,
		'columnsForView' => $coldef,
		'columnsForEdit' => $coldef,
		'richtextEditors' => $tables,
		'currentTable' => $table,
		'currentAction' => $action,
		'hasLookupColumn' => $tables	
	);	
	
	$config = array_merge($defaults, $config);

	setAppVar('scaffolding', $config); // store our config
	
	getMessage();
	
	if(empty($config['currentTable']))
	{		
		// generate list of available tables and actions
		generateTableList($config['submitRoute'], $config['allowedTables'], $config['allowedActions']);
	}
	else
	{	
		// only use allowed tables
		if(in_array($config['currentTable'], $config['allowedTables']))
		{
			switch($config['currentAction'])
			{
				case 'save':
					if(saveRecord())
					{
						setMessage('Record successfully saved');
						header('Location:'. $config['submitRoute'].'?table='.$config['currentTable']);
						exit(0);
					}
					else
					{
						getMessage(true);
						generateRecordForm($config['submitRoute'], $config['currentTable'], getRecordFromPost());
					}
				break;
				
				case 'remove':
					if(removeRecord($table, $id))
					{
						setMessage('Record successfully removed');
						header('Location:'. $config['submitRoute'].'?table='.$config['currentTable']);
						exit(0);
					}
					else
					{
						setMessage('Selected record no longer exists');
						header('Location:'.$_SERVER['HTTP_REFERER']);
						exit(0);
					}
				break;
				
				case 'copy':
					if(copyRecord())
					{
						setMessage('Record successfully copied');
					}
					else
					{
						setMessage('Record could no be copied. You may be trying to copy a record that no longer exists.');
					}
					
					header('Location:'. $config['submitRoute'].'?table='.$config['currentTable']);
					exit(0);
				break;				
			
				case 'new':
				case 'edit':
					$record = !empty($id) ? getRecord($config['currentTable'], $id) : newRecord($config['currentTable']);
			
					if($record)
					{
						generateRecordForm($config['submitRoute'], $config['currentTable'], $record);
					}
					else
					{
						echo 'The record you are trying to access no longer exists.';
					}
				break;
				
				default;
				case 'view':				
					if(empty($id))
					{
						if(!array_key_exists($config['currentTable'],$config['columnsForView']))
						{
							echo "Missing [columnsForView] custom configuration for table [".$config['currentTable']."].";
							break;
						}
						
						$columns = empty($config['currentTable']) ? array() : $config['columnsForView'][$config['currentTable']];
						generateRecordList($config['submitRoute'], $config['currentTable'], $columns, $config['allowedActions']);
					}				
				break;
			}
		}
	}		
}

/**
 * undocumented function
 *
 * @param string $path 
 * @param string $tables 
 * @param string $actions 
 * @return void
 * @author Craig Hoover
 */
function generateTableList($path, $tables = array(), $actions = array())
{
	$schema = getSchema();
	$contents = "<div class=\"scaffolded_list\">\n";
	$contents .= "<ul>";
	
	foreach($schema as $table => $fields)
	{
		if(in_array($table, $tables))
		{
			if(!array_key_exists($table, $actions))
			{
				$contents .= "<li><span class=\"table_name\">".readable($table) . "</span> Missing [allowedActions] custom configuration.";
				continue;
			}
			
			$contents .= "<li><span class=\"table_name\" title=\"".$schema[$table]['record_count']." rows\">".readable($table) . "</span>";
			$contents .= "<ul>\n";
			$contents .= in_array('view',$actions[$table]) ?  "<li class=\"table_view\"><a href=\"$path?table=$table&amp;action=view\">View</a></li>\n" : '';
			$contents .= in_array('new', $actions[$table]) ? "<li class=\"table_new\"><a href=\"$path?table=$table&amp;action=new\">New</a></li>\n" : '';
			$contents .= in_array('export', $actions[$table]) ? "<li class=\"table_export\"><a href=\"$path?table=$table&amp;action=export\">Export</a></li>\n" : '';
			$contents .= in_array('empty',$actions[$table]) ? "<li class=\"table_empty\"><a href=\"$path?table=$table&amp;action=empty\">Empty</a></li>\n" : '';
			$contents .= "</ul>\n</li>\n";
		}
	}
	$contents .= "</ul>\n";
	$contents .= "</div>\n";
	
	echo $contents;
}

/**
 * undocumented function
 *
 * @param string $text 
 * @return void
 * @author Craig Hoover
 */
function readable($text)
{
	return ucwords(preg_replace("/\_|\W/"," ", strip_tags($text)));	
}

/**
 * undocumented function
 *
 * @param string $content 
 * @return void
 * @author Craig Hoover
 */
function field($content)
{
	return sprintf("<div class=\"scaffolded_field\">%s</div>", $content);
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $values 
 * @param string $choices 
 * @param string $usekeys 
 * @param string $label 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function select($id, $values = array(), $choices = '', $label = true, $text = '', $usekeys = false,  $extra = '')
{
	$m = is_array($choices);
	$h  = defaultValue($id, $choices);
	$h .= $label ? label($id, $text) : '';
	$h .= "\n<select id=\"$id\" name=\"$id\"".($m ? " multiple=\"multiple\" size=\"8\"" : "").">";
	$choices = is_array($choices) ? $choices : array("$choices");

	foreach($values as $key => $value)
	{	
		$key = $usekeys ? $key : $value;
		$h .= "\n<option value=\"$key\"" . (in_array($key, $choices) ? ' selected="selected"' : '');
		$h .= ">$value</option>";
	}
		
	$h .= "\n</select>";
	$h .= $extra;
	return $label ? field($h) : $h;
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $checked 
 * @param string $label 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function checkbox($id, $value = '', $checked = false, $label = true, $text = '', $extra = '')
{
	$h = "<input type=\"hidden\" name=\"{$id}\" id=\"{$id}_default\" value=\"0\"/>";
	$h .= "\n<input type=\"checkbox\" name=\"$id\" id=\"$id\" value=\"1\"" . ($checked ? ' checked=\"checked\"' : '') .'/>';
	$h .= $label ? label($id, $text, '<label for="%s" class="checkbox">%s</label>') : '';
	$h .= $extra;
	return field($h);
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $values 
 * @param string $choice 
 * @param string $usekey 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function radio($id, $values = array(), $choice = '', $text = '', $usekey = false, $extra = '')
{
	$h = "<fieldset id=\"$id_fieldset\">";
	$h .= defaultValue($id, $choice);
	$i = 0;
	foreach($values as $key => $value)
	{
		$key = $usekey ? $key : $value;
		$h .= "\n<input type=\"radio\" name=\"$id\" id=\"$id_$i\" value=\"$value\"" . ($key == $choice ? " checked=\"checked\"" : '') ."/>";
		$h .= label($key, '', '<label for="%s" class="radio">%s</label><br />');
		$i += 1;
	}
	$h .="\n</fieldset>";
	$h .= $extra;
	return field($h);
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $label 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function input($id, $value = '', $label = true, $text = '', $extra = '')
{
	$h = "<div class=\"field\">";
	$h .= $label ? label($id, $text, '<label for="%s" class="string">%s:</label>') : '';
	$h .= defaultValue($id, $value);
	$h .= "\n<input type=\"text\" name=\"$id\" id=\"$id\" size=\"35\" value=\"$value\"/>";
	$h .= $extra;
	$h .= richeditable($id);
	return field($h);	
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function hidden($id, $value = '', $extra = '')
{
	$h = "\n<input type=\"hidden\" name=\"$id\" id=\"$id\" value=\"$value\"/>";
	$h .= $extra;
	return $h;	
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $label 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function textarea($id, $value = '', $label = true, $text = '', $extra = '')
{
	$options = getAppVar('scaffolding');
	$editor = count($options['richtextEditors']) > 0 && isset($options['richtextEditors'][$options['currentTable']]);
	
	$h = $label ? label($id, $text, '<label for="%s" class="blob">%s:</label>') : '';
	$h .= defaultValue($id, $value);
	$h .= "\n<textarea name=\"$id\" id=\"$id\" cols=\"30\" rows=\"6\">".htmlspecialchars($value)."</textarea>";	
	$h .= $extra;
	$h .= richeditable($id);
 
	return field($h);
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $label 
 * @param string $ampm 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function times($id, $value = '', $label = true,  $text = '', $ampm = false,$extra = '')
{
	$hours = range(0,23);
	$mins = range(0,59);
	
	$d = $value == '' ? time() : strtotime($value);
	$s = explode(',', date('g,G,i,A',$t));
	$hh = $ampm ?  $s[0] : $s[1];
	$i = $s[2];
	$a = $s[3];
	
	$h = $label ? label($id, $text, '<label for="%s" class="times">%s:</label>') : '';
	$h .= defaultValue($id, $value);
	$h .= hidden($id, $value);
	$h .= select("{$id}_hour", $hours, $hh, false, '', true);
	$h .= select("{$id}_minute", $mins, $i, false, '', true);
	$h .= $ampm ? select("{$id}_ampm", $amp, $a, false, '', false) : '';
	$h .= $extra;
	
	return field($h);	
	
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $label 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function dates($id, $value = '', $label =true, $text = '',$extra = '')
{
	$mos = array();
	for($i=1; $i<=12;$i+=1)
	{
		$mos[$i] = date("M",mktime(0,0,0, $i,1,date("Y")));
	}

	$days = range(1,31);
	$years = range(date('Y')-5, date('Y') + 5);
	$hours = range(0,23);
	$mins = range(0,59);
	
	$d = $value == '' ? time() : strtotime($value);
	$s = explode(',', date('n,d,Y',$t));
	$m = $s[0];
	$d = $s[1];
	$y = $s[2];
	
	$h = $label ? label($id, $text, '<label for="%s" class="dates">%s:</label>') : '';
	$h .= defaultValue($id, $value);
	$h .= hidden($id, $value);
	$h .= select("{$id}_month", $mos, $m, false, '', true);
	$h .= select("{$id}_day", $days, $d, false,'', false);
	$h .= select("{$id}_year", $years, $y, false, '', false);
	$h .= $extra;
	
	return field($h);	
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $value 
 * @param string $label 
 * @param string $ampm 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function datestamp($id, $value = '', $label = true, $text = '', $ampm = false, $extra = '')
{
	$mos = array();
	for($i=1; $i<=12;$i+=1)
	{
		$mos[$i] = date("M",mktime(0,0,0, $i,1,date("Y")));
	}

	$days = range(1,31);
	$years = range(date('Y')-5, date('Y') + 5);
	$hours = $ampm ? range(1,12) : range(0,23);
	$mins = range(0,59);
	$amp = array('AM','PM');
	
	$t = $value == '' ? time() : strtotime($value);
	$s = explode(',', date('n,d,Y,g,G,i,A',$t));
	$m = $s[0];
	$d = $s[1];
	$y = $s[2];
	$hh = $ampm ?  $s[3] : $s[4];
	$i = $s[5];
	$a = $s[6];
	
	$h = $label ? label($id, $text, '<label for="%s" class="datestamp">%s:</label>') : '';
	$h .= hidden($id, $value);
	$h .= defaultValue($id, $value);
	$h .= select("{$id}_month", $mos, $m, false, '', true);
	$h .= select("{$id}_day", $days, $d, false, '', false);
	$h .= select("{$id}_year", $years, $y, false, '', false);
	$h .= select("{$id}_hour", $hours, $hh, false, '', false);
	$h .= select("{$id}_minute", $mins, $i, false, '', false);	
	$h .= $ampm ? select("{$id}_ampm", $amp, $a, false, '', false) : '';
	$h .= $extra;
	
	return field($h);	

}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $text 
 * @param string $template 
 * @return void
 * @author Craig Hoover
 */
function label($id, $text = '', $template = false)
{
	$text = $text =='' ? readable($id) : $text;
	return sprintf($template ? $template : "<label for=\"%s\">%s:</label>", $id, $text);
}

/**
 * undocumented function
 *
 * @param string $id 
 * @param string $values 
 * @return void
 * @author Craig Hoover
 */
function defaultValue($id, $values = '')
{
	$v = is_array($values) ? implode(',',$values) : $values;
	return "<input type=\"hidden\" name=\"{$id}_default\" id=\"{$id}_default\" value=\"$v\"/>";
}

/**
 * undocumented function
 *
 * @param string $path 
 * @param string $table 
 * @param string $contents 
 * @param string $method 
 * @param string $extra 
 * @return void
 * @author Craig Hoover
 */
function form($path, $table, $contents, $method = 'post', $extra = '')
{
	$h = "<div class=\"scaffolded_form\">\n";
	$h .= "<form method=\"$method\" enctype=\"multipart/form-data\" action=\"".$path."\" accept-charset=\"utf-8\" name=\"form_".md5(uniqid())."\">";
	$h .= hidden('table' ,$table);
	$h .= $contents;
	$h .= "<div class=\"scaffolded_buttons\">\n";
	$h .= "<input type=\"reset\" id=\"form_reset\" name=\"form_reset\" value=\"Reset\" />";
	$h .= "<input type=\"submit\" id=\"form_submit\" name=\"form_submit\" value=\"Submit\"/>";
	$h .= "</div>";
	$h .= $extra;
	$h .= "</form>";
	return $h;
}


/**
 * undocumented function
 *
 * @param string $id 
 * @return void
 * @author Craig Hoover
 */
function richeditable($id)
{
	$config = getAppVar('scaffolding');
	
	if(!array_key_exists($config['currentTable'], $config['richtextEditors'])) return;
	
	$richcfg = $config['richtextEditors'][$config['currentTable']];
	$editor = in_array($id, $richcfg);
	$editorwithconfig =  array_key_exists($id,$richcfg);
	
	if($editor || $editorwithconfig)
	{
		// get config from scaffold configuration
		$richcfg = $editorwithconfig ? $richcfg[$id] : array();
		
		include_once($config['ckEditorProcessor']);

		$defaults = array(
			'height' => 150,
			'width' => 450,
			'toolbar' => array(array('Styles', 'Format'), array('Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link'))
		);

		$settings = array_merge($defaults,$richcfg);

		$CKEditor = new CKEditor();
		$CKEditor->basePath = '/ckeditor/';
		$CKEditor->returnOutput = true;

		foreach($settings as $key => $value)
		{
			$CKEditor->config[$key] = $value;
		}

		return $CKEditor->replace($id, "<p>Initial value.</p>");
	}
	return '';
}



?>