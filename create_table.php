<html>
<body>

	<script>

function jump(data){
	// console.log(data)
	  var dbname = document.getElementById("input_db").value;
      console.log(dbname);

      var host = document.getElementById("host").value;
      console.log(host);

      var username = document.getElementById("username").value;
      console.log(username);

      var password = window.btoa(document.getElementById("password").value);
      console.log(password);

      var url = "create_table.php?dbname="+dbname+'&host='+host+'&username='+username+'&password='+password;

      window.location.href=url; 
}


</script>

<div>
		请输入主机地址: <input type="text" id="host" name="fname" value='<?php echo $_REQUEST['host']; ?>' /> <br/>
		请输入mysql账户: <input type="text" id="username" name="fname" value='<?php echo $_REQUEST['username']; ?>'/> <br/>
		请输入mysql密码: <input type="text" id="password" name="fname" value='<?php echo base64_decode($_REQUEST['password']); ?>' /> <br/>

		请输入库名: <input type="text" id="input_db" name="fname" value='<?php echo $_REQUEST['dbname'] ?>' /> 



		 <button id="create_table" onclick="jump(this)"  class='create_table'>生成数据库报表</button>
</div>


</html>

<?php 

if(!empty($_REQUEST['host'])){
	$host = $_REQUEST['host'];
}else{
	$host='192.168.2.232';
}

if(!empty($_REQUEST['username'])){
	$user = $_REQUEST['username'];
}else{
	$user='liexin_credit';
}

if(!empty($_REQUEST['password'])){
	$password = base64_decode($_REQUEST['password']);
}else{
	$password='liexin_credit#zsyM';
}




if(!empty($_REQUEST['dbname'])){
	$dbName=  str_replace( "'", "", $_REQUEST['dbname']);
}else{
	$dbName='liexin_credit';
}




$link=new mysqli($host,$user,$password,$dbName);

if($link->connect_error){

die("数据库连接失败：".$link->connect_error.'请检查数据库账户密码是否正确');

}

mysqli_select_db($link,$dbName);

$sql="show tables";
$result = query($link,$sql);


foreach ($result as $key => $value) {
	
	$sql = "show create table $value[0]";
	$res = query($link,$sql);
	
	$creata_table_sql = $res[0]['Create Table'];

	$data[$value[0]] = creata_table($creata_table_sql);


}
// dump($data);
exit;


dump($result);exit;


function dump($arr){
	echo '<pre>';
	var_dump($arr);
	echo '<pre>';
}

function query($link,$sql){
	$result=$link->query($sql);
	return $result->fetch_all(MYSQLI_BOTH);//参数MYSQL_ASSOC、MYSQLI_NUM、MYSQLI_BOTH规定产生数组类型
}


/*
	根据建表语句 创建表文档
*/
function creata_table($sql){

	$is_windows =  strtoupper(substr(PHP_OS,0,3))==='WIN'?true:false; 

	if($is_windows){
		$arr = explode('\n', $sql);
	}else{
		$arr = explode(PHP_EOL, $sql);
	}
	

	//获取数据库表名
	preg_match('/CREATE TABLE `([a-zA-Z0-9_]{1,})`/',$arr[0],$table_name);

	$table_name = $table_name[1];

	$data[$table_name] = array();


	preg_match("/COMMENT=\'([\x{4e00}-\x{9fa5} 0-9a-zA-Z]{1,})/u",$arr[count($arr)-1],$remarks);
	$remarks = !empty($remarks[1])?$remarks[1]:'';

	$data['remarks'] = $remarks;




	



	//获取字段名及字段信息
	foreach ($arr as $key => $value) {
		
		$is_key = strpos($value,'KEY ',0);

		if($key != 0  && $key != count($arr)-1 && !$is_key){

			//获取数据字段
			$tmp['field_data'] = get_field_data($value);

			//字段备注
			$tmp['field_name'] = get_field_name($value);

			//字段类型
			$tmp['field_type'] = get_field_type($value);

			//获取是否有符号
			$tmp['field_symbol'] = get_field_symbol($value);

			//获取默认值
			$tmp['field_default'] = get_field_detault($value);

			//获取说明
			// $tmp['field_remarks'] = get_field_remarks($value);

			$data[$table_name][] = $tmp;

		}

	}

	show_table($data);
	// exit;
	// return $data;

}


/*
	获取数据字段
*/
function get_field_data($str){
	preg_match('`([a-zA-Z0-9_]{1,})`',$str,$result);
	return $result[0];
}

/*
	字段备注
*/
function get_field_name($str){
	preg_match("/COMMENT \'([\x{4e00}-\x{9fa5}a-zA-Z0-9 \(\（\）\)\：\,\，]{1,})/u",$str,$result);
	
	return !empty($result[1])?$result[1]:'';
}

/*
	获取字段类型
*/
function get_field_type($str){
	preg_match("/`([a-zA-Z0-9_]{1,})` ([a-zA-Z0-9_\(\)'\,]{1,})/",$str,$result);
	return $result[2];
}

/*
	获取是否有符号的
*/
function get_field_symbol($str){
	
	$result = strpos($str,'unsigned',0);
	
	if($result != FALSE){
		return true;
	}else{
		return false;
	}

}


/*
	获取默认值
*/
function get_field_detault($str){
	
	preg_match("/DEFAULT '([0-9a-zA-Z]{1,})'/",$str,$result);

	// if(strpos($str,'com_credits_id')){
	// 	dump($result);exit;
	// }
	
	// return !empty($result[0])?$result[0]:'';
	return isset($result[1])?$result[1]:'';
}

/*
	展示table
*/
function show_table($data){
	// dump($data);
	

	echo '<h3>表名:'.key($data).'  		'.$data['remarks'].'  </h3>';
	echo '<table border="1">
		<tr>
			<th>字段名</th>
			<th>字段类型</th>
			<th>默认值</th>
			<th>无符号</th>
			<th>字段备注</th>
		</tr>
	';





	foreach ($data[key($data)] as $key => $value) {
		// dump($value);exit;
		echo '<tr>';

			echo '<td>';
				echo $value['field_data'];
			echo '</td>';

			echo '<td>';
				echo $value['field_type'];
			echo '</td>';

			echo '<td>';
				echo $value['field_default'];
			echo '</td>';

			echo '<td>';
				if($value['field_symbol']){
					echo 'TRUE';
				}else{
					echo 'FALSE';
				}
			echo '</td>';

			echo '<td>';
				echo $value['field_name'];
			echo '</td>';


		echo '</tr>';
		// exit;
	}

	echo '</table>';


	
}

?>
