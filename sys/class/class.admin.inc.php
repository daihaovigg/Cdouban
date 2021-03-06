<?php

/**
 * 管理（添加，修改等）用户行为
 * PHP 5.6
 * @author dai@2015
 *
 */

 class Admin extends DB_Connect
 {
 	/*
 	 * 确定用于散列密码中盐的长度
 	 * @var int 密码盐的字符串长度
 	 */

 	private $_saltLength = 7;


 	/*
 	 * 保存生成一个DB对象，设定盐的长度
 	 * @param object $db 数据库对象
 	 * @param int $saltLength 密码盐的长度
 	 */
 	function __construct($db=NULL,$saltLength=NULL)
 	{
 		parent::__construct($db);

 		/*
 		 * 若传入整数，则用它来设定saltLength的值
 		 */
 		if(is_int($saltLength))
 		{
 			$this->_saltLength = $saltLength;
 		}
 	}


 	/**
 	 * 用以获取刚注册用户的ID号
 	 * @param string username 用户名
 	 * @return int 用户的ID
 	 */
 	private function _getID($uname)
 	{
 		$sql = "select `user_id` from `users` where `user_name` = :uname limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':uname',$uname,PDO::PARAM_STR);
 			$stmt->execute();
 			$user = array_shift($stmt->fetchAll());
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}
 		return $user['user_id'];
 	}

 	/*
 	 * 检查用户登录是否正确
 	 * @return 若成功返回TRUE，若失败返回错误信息
 	 */
 	public function processLoginForm()
 	{
 		/*
 		 * 若未提交正确的action返回出错信息
 		 */
 		if($_POST['action'] != 'user_login')
 		{
 			return "Invaild action supplied for processLoginForm.";
 		}

 		//安全起见转义用户输入数据
 		$uemail = htmlentities($_POST['uemail'],ENT_QUOTES);
 		$pword = htmlentities($_POST['pword'],ENT_QUOTES);

 		/*
 		 * 若用户存在则返回数据库中匹配信息
 		 */
 		$sql = "select `user_id`,`user_name`,`user_email`,`user_pass`
 				from `users` where `user_email` = :uemail limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':uemail',$uemail,PDO::PARAM_STR);
 			$stmt->execute();
 			$user = array_shift($stmt->fetchAll());
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}

 		/*
 		 * 若用户名不存在则返回出错信息
 		 */
 		if (!isset($user))
 		{
 			return $uemail."No user found with that ID.";
 		}

 		/*
 		 * 根据用户输入的密码生成散列后的密码
 		 */
 		$hash = $this->_getSaltedHash($pword,$user['user_pass']);
 		/*
 		 * 检查散列后的密码是否与数据库中保存的密码一致
 		 */
 		if($user['user_pass'] == $hash)
 		{
 			/*
 			 * 将用户信息以数组的形式保存到session中
 			 */
 			$_SESSION['user'] = array(
 				'id'=>$user['user_id'],
 				'name'=>$user['user_name'],
 				'email'=>$user['user_email']);
 			return TRUE;
 		}

 		/*
 		 * 如果密码不正确则返回出错信息
 		 */
 		else
 		{
 		return "Your useremail or password is invaild.";
 		}
 	}

 	/**
 	 * 为给定字符串生成一个加盐的散列值
 	 * @param string $string 即将被散列的字符串
 	 * @param string $salt 从这个串中提取盐
 	 * @return string 加盐后的散列值
 	 */
 	private function _getSaltedHash($string, $salt=NULL)
 	{
 		//如果未传入盐则自己生成一个
 		if($salt == NULL)
 		{
 			$salt = substr(md5(time()), 0, $this->_saltLength);
 		}

 		//如果传入盐，则从中提取盐
 		else
 		{
 			$salt = substr($salt, 0, $this->_saltLength);
 		}

 		//将盐添加到散列值之前，并返回散列值
 		return $salt.sha1($salt.$string);
 	}


 	//test fangfa
 	public function testSalt($string,$salt=NULL)
 	{
 		return $this->_getSaltedHash($string,$salt);
 	}

 	/*
 	 * 用户登出
 	 * @return 成功返回TRUE，失败返回出错信息
 	 */
 	public function processLogout()
 	{
 		//如果未提交适当的action，返回错误信息
 		if($_POST['action']!='user_logout')
 		{
 			return "Invaild action supplied for processLogout.";
 		}

 		//从当前会话中删除用户数据
 		session_destroy();
 		return TRUE;
 	}

 	/*
 	 * 处理注册过程，检查user_name和user_email是否存在，存在则不予注册
 	 * @return TRUE 若注册成功，将用户写入cookies
 	 * @return string 出现错误则返回出错信息
 	 */
 	public function processRegister()
 	{
 		/*
 		 * 若未提交正确的action返回出错信息
 		 */
 		if($_POST['action'] != 'user_register')
 		{
 			return "Invaild action supplied for processLoginForm.";
 		}

 		//安全起见转义用户输入数据
 		$uname = htmlentities($_POST['uname'],ENT_QUOTES);
 		$pword = htmlentities($_POST['pword'],ENT_QUOTES);
 		$uemail = htmlentities($_POST['uemail'],ENT_QUOTES);
 		$ucity = htmlentities($_POST['ucity'],ENT_QUOTES);

 		//查询user_name是否存在
 		$sql = "select * from `users` where `user_name` = :uname limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':uname',$uname,PDO::PARAM_STR);
 			$stmt->execute();
 			$user = array_shift($stmt->fetchAll());
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}

 		if(!empty($user))
 		{
 			return 'username has used!';
 		}

 		//查询user_email是否存在
 		$sql = "select * from `users` where `user_email` = :uemail limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':uemail',$uemail,PDO::PARAM_STR);
 			$stmt->execute();
 			$user = array_shift($stmt->fetchAll());
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}

 		if(!empty($user))
 		{
 			return 'email has used!';
 		}

 		//查询城市是否正确
 		/* 出于显示天气并不好用的原因，取消验证城市可用性(2015-10-28)
 		$sql = "select * from `city_sk_info` where `city` = :ucity limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':ucity',$ucity,PDO::PARAM_STR);
 			$stmt->execute();
 			$city = array_shift($stmt->fetchAll());
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}

 		if(empty($city))
 		{
 			return 'wrong city!';
 		}
 		*/


 		//正确，插入数据进users表中
 		$sql = "insert into `users` (`user_name`,`user_pass`,`user_email`,`user_city`,`user_img`)
 				values (:uname, :upword, :uemail, :ucity, :uimg)";
 		try
 		{
 			$hash = $this->_getSaltedHash($pword);
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':uname',$uname,PDO::PARAM_STR);
 			$stmt->bindParam(':upword',$hash,PDO::PARAM_STR);
 			$stmt->bindParam(':uemail',$uemail,PDO::PARAM_STR);
 			$stmt->bindParam(':ucity',$ucity,PDO::PARAM_STR);
 			$defalutimg="../static/defalut.jpg";
 			$stmt->bindParam(':uimg',$defalutimg,PDO::PARAM_STR);
 			$stmt->execute();
 			$stmt->closeCursor();
 			$_SESSION['user'] = array(
 				'id'=>$this->_getID($uname),
 				'name'=>$uname,
 				'email'=>$uemail);
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}

 		//同时，将自己加入自己的关注名单
		$sql = "insert into `attention` (`atten_id`,`attened_id`)
 				values (:nid, :nid)";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':nid',$this->_getID($uname),PDO::PARAM_INT);
 			$stmt->execute();
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}
 		return TRUE;
 	}

 	/**
 	 * 通过ID查询用户信息
 	 * @param int ID 用户的ID
 	 * @return array 用户信息组
 	 */
 	public function findUserByID($id)
 	{
 		$sql = "select * from `users` where `user_id` = :id limit 1";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':id',$id,PDO::PARAM_INT);
 			$stmt->execute();
 			$user = $stmt->fetchAll();
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}
 		return $user[0];
 	}

 	/**
 	 * 修改用户信息
 	 * @param 用户所有信息参数
 	 * @return bool 成功true，失败false
 	 */
 	public function updateUserInfo()
 	{
 		//安全起见转义用户输入数据
 		$id = htmlentities($_POST['uid'],ENT_QUOTES);
 		$name = htmlentities($_POST['uname'],ENT_QUOTES);
 		$img = htmlentities($_POST['uimg'],ENT_QUOTES);
 		$city = htmlentities($_POST['ucity'],ENT_QUOTES);
 		$about = htmlentities($_POST['uabout'],ENT_QUOTES);
 		#$img = "../static/defalut.jpg";

 		$sql = "update `users` set `user_name`=:uname,
 		`user_img`=:uimg,`user_about`=:uabout,
 		`user_city`=:ucity where `user_id` = :id ";
 		try
 		{
 			$stmt = $this->db->prepare($sql);
 			$stmt->bindParam(':id',$id,PDO::PARAM_INT);
 			$stmt->bindParam(':uname',$name,PDO::PARAM_STR);
 			$stmt->bindParam(':uimg',$img,PDO::PARAM_STR);
 			$stmt->bindParam(':uabout',$about,PDO::PARAM_STR);
 			$stmt->bindParam(':ucity',$city,PDO::PARAM_STR);
 			$stmt->execute();
 			$stmt->closeCursor();
 		}
 		catch(Exception $e)
 		{
 			die($e->getMessage());
 		}
 		return TRUE;
 	}

 }

?>