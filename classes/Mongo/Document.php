<?php

abstract class Mongo_Document extends Krishna_Mongo_Document 
{
	/**
	 * 获得查询值
	 *
	 * @param string $op 操作符
	 * @param string $value 参数值
	 * @return MongoRegex|string|array 查询值
	 */
	public static function query_value($op, $value)
	{
		if ($op == 'like')
		{
			return new MongoRegex("/" . $value . "/");
		}
	
		if($op == '=')
		{
			return $value;
		}
		
		// 进行操作符转换
		$opmap = array('<' => 'lt', '<=' => 'lte', '>' => 'gt', '>=' => 'gte', '!=' => 'ne', '<>' => 'ne');
		$op = Arr::get($opmap, $op, $op);
		
		return array("$$op" => $value);
	}
	
	/**
	 * 返回翻译的数组
	 */
	public function translated_array()
	{
		$object = array();
	
		foreach ($this->_object as $column => $value)
		{
			// Call __get for any user processing
			$object[":$column"] = $this->__get($column);
		}
	
		return $object;
	}
	
	/******************************* 序列化 *******************************/
	/** 标识字段 */
	protected $_name_field = NULL;
	
	/**
	 * 返回标识字段
	 */
	public function name_field()
	{
		return $this->_name_field;
	}
	
	/**
	 * 返回模型名
	 */
	public function object_name()
	{
		return $this->_name;
	}
	
	/**
	 * 输出字符串
	 * @see Krishna_ORM::__toString()
	 */
	public function __toString()
	{
		if (empty($this->_name_field))
		{
			return json_encode($this->_object);
		}
	
		return (string) $this->{$this->_name_field};
	}
	
	/**
	 * 将orm的对象序列化为json
	 * @return string
	 */
	public function to_json()
	{
		return json_encode($this->as_array());
	}
	
	/**
	 * 从json中加载orm对象
	 *
	 * @param string $json
	 * @return ORM
	 */
	public function from_json($json)
	{
		$values = json_decode($json, TRUE);
	
		// 根据id来查询对象
		if (isset($values[$this->_id]))
		{
			$this->load(new MongoId($values[$this->_id]));
		}
	
		// 加载json数据
		return $this->load_values($values);
	}
	
	/******************************* 引用操作 *******************************/
	/**
	 * 保存: 连带保存关联对象
	 *   1 过滤关联对象
	 *   只保存 has_one/has_many 的关联对象,因为本对象是主表,关联对象是从表,主表能改变从表的数据
	 *   不保存 belong_to 的关联对象,因为本对象是从表,关联对象是主表,而从表不能改变主表的数据
	 *
	 *   2 设置关联对象的外键:
	 *     对创建的情况,只有等本对象创建之后才能给关联对象设置外键；
	 *     而当本对象保存之后已经分不清楚是创建还是修改,同时关联对象的外键是不可信的,因此必须要对关联对象设置外键
	 *
	 * @param Validation $validation
	 * @param array $columns
	 * @return  Mongo_Document
	 */
	public function save_include(Validation $validation = NULL, array $columns = NULL)
	{
		//TODO: 参考orm
		return $this->save($validation);
	}
	
	/**
	 * 删除: 连带删除关联对象
	 *   1 过滤关联对象
	 *   只删除 has_one/has_many 的关联对象,因为本对象是主表,关联对象是从表,主表能改变从表的数据
	 *   不删除 belong_to 的关联对象,因为本对象是从表,关联对象是主表,而从表不能改变主表的数据
	 *
	 * @param array $columns
	 * @return Mongo_Document
	 */
	public function delete_include(array $columns = NULL)
	{
		//TODO: 参考orm
		return $this->delete();
	}
	
	/******************************* 引用关系 *******************************/
	/**
	 * 获得引用关系配置
	 *
	 * @param string $field
	 * @return boolean|array 引用关系配置
	 */
	public function reference($field)
	{
		return Arr::get($this->_references, $field, FALSE);
	}
	
	/**
	 * 获得多个引用关系的配置
	 *
	 * @param array $types 引用关系類型
	 * @return array
	 */
	public function references($multiple = NULL)
	{
		foreach ($this->_references as $field => $ref)
		{
			if($multiple === NULL || Arr::get($this->_references[$field], 'multiple', FALSE) === $multiple)
			{
				$result[$field] = $ref;
			}
		}
	
		return $result;
	}
	
	/**
	 * 过滤引用对象的字段
	 *
	 * @param array $fields
	 * @return array
	 */
	public function filter_references(array $fields)
	{
		$result = array();
	
		foreach ($fields as $field)
		{
			$ref = $this->reference($field);
				
			if ($ref)
			{
				$result[$field] = $ref;
			}
		}
	
		return $result;
	}
	
	/******************************* 输出表单/详情 *******************************/
	/**
	 * 返回表单选项
	 *
	 * @return array
	 */
	public function form_options()
	{
		return array();
	}
	
	/**
	 * 返回详情选项
	 *
	 * @return array
	 */
	public function detail_options()
	{
		return array();
	}
	
	/**
	 * 获得指定字段的关联枚举的所有值
	 *
	 * @param string $field
	 * @return array
	 */
	public function enum_array($field)
	{
		$option = Arr::get($this->detail_options(), $field, FALSE);
	
		if ($option AND $option['type'] === 'enum')
		{
			return $option['options'];
		}
	
		return NULL;
	}
	
	/**
	 * 获得指定字段值对应的枚举值
	 *
	 * @param string $field
	 * @return string
	 */
	public function enum_value($field)
	{
		return Arr::get($this->enum_array($field), $this->$field);
	}
	
	/**
	 * 获得表单/详情
	 *
	 * @param array $fields 输出的字段
	 * @param array $options 输出选项
	 * @return Formation
	 */
	protected function _form(array $fields = NULL, array $options = array())
	{
		// 设置默认的输出字段
		if ($fields === NULL)
		{
			$fields = array_keys($options);
		}
	
		// 构建form
		$form = new Formation($this);
	
		// 添加form元素
		foreach ($fields as $field)
		{
			$form->add_elements($options[$field]['type'], $field, $options[$field]);
		}
	
		return $form;
	}
	
	/**
	 * 输出表单
	 *
	 * @param array $fields 输出的字段
	 * @return Formation
	 */
	public function form(array $fields = NULL)
	{
		return $this->_form($fields, $this->form_options());
	}
	
	/**
	 * 输出详情
	 *
	 * @param array $fields 输出的字段
	 * @return Formation
	 */
	public function detail(array $fields = NULL)
	{
		return $this->_form($fields, $this->detail_options());
	}
	
	/**
	 * 输出元素
	 *
	 * @param string $field 输出的字段
	 * @param array $options
	 * @param string $name 控件名
	 * @return string
	 */
	public function element($field, array $options = array(), $name = NULL)
	{
		$type = $field === $this->_id ? 'hidden' : Arr::path($options, "$field.type");
		$name = $name === NULL ? $field : $name;
		return Formation::element($type, $name, $this->$field, Arr::path($options, $field));
	}
	
	/**
	 * 判断字段能否为空
	 *     当使用orm::values(), 如果$values没有指定字段的值, 则根据该函数来确定是否设置当前字段为NULL
	 *     注意由此会导致isset()返回true, 因此为减少误会, 只有表单元素为checkbox的字段才会为NULL
	 *
	 * @param string $column
	 * @return bool
	 */
	public function is_nullable($column)
	{
		return Arr::path($this->form_options(), "$column.type") === 'checkbox';
	}
	
	/******************************* 文件上传 *******************************/
	/**
	 * 是否有文件上传的控件
	 * @param array $fields
	 * @return boolean
	 */
	public function has_file_uploaded(array $fields = NULL)
	{		
		foreach ($fields as $key => $field)
		{
			if (is_string($key))
			{
				// 不考虑关联对象的文件上传
			}
			elseif (Arr::path($this->form_options(), "$field.type") === 'file') // 判断该字段的控件类型是否是文件上传控件
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	* 获得关联文件的属性
	* @param array $fields
	* @return array
	*/
	protected function _get_file_uploaded(array $fields = NULL)
	{
		$result = array();
	
		foreach ($fields as $key => $field)
		{
			if (is_string($key))
			{
				// 不考虑关联对象的文件上传
			}
			elseif (Arr::path($this->form_options(), "$field.type") === 'file') // 判断该字段的控件类型是否是文件上传控件
			{
				$result[] = $field;
			}
		}
	
		return $result;
	}
	
	/**
	 * 尝试上传单个文件并设置相关属性
	 * @param array $expected
	 * @return Validation 校验器
	 */
	public function try_upload_file($expected)
	{
		//如果有文件则上传文件
		$fields = $this->_get_file_uploaded($expected);
		
		if (empty($fields)) 
		{
			return NULL;
		}
		
		//保存结果的校验器
		$result = NULL;
		
		//1 校验上传文件
		foreach ($fields as $field)
		{
			$label = Arr::get($this->labels(), $field);//获得字段标签
			$options = Arr::get($this->form_options(), $field);// 获得文件上传的选项
			$expected_exts = Arr::get($options, 'expected_exts');// 获得文件类型限制
			$size = Arr::get($options, 'size');// 获得文件大小限制
			
			//1.1 获得校验器
			$validation = Upload::validation($_FILES, $field, $label, $expected_exts, $size);
			 
			//1.2 校验
			if (!$validation->check())
			{
				$result = Validation::merge($result, $validation);//记录失败的校验器
			}
		}
		
		//2 保存上传文件
		foreach ($fields as $field)
		{
			if ($result === NULL OR $result->check())
			{
				//2.1 保存新文件
				$this->$field = Upload::save_ext($_FILES[$field], $this->object_name().'/'.$this->pk());
				
				//2.2 删除旧文件
				if (!empty($this->_original[$field])) 
				{
					Upload::delete($this->_original[$field]);
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * 上传多个文件
	 * 
	 * @param string $field 上传文件的字段
	 * @return boolean|Validation
	 */
	public function upload_files($field = 'files')
	{
		if (($multi = Upload_Multi::factory($field)) === FALSE) 
		{
			throw new Virtual_Validation_Exception("创建多文件上传的处理器失败");
		}
		
		//保存结果的校验器
		$result = NULL;
		
		$label = Arr::get($this->labels(), $field);//获得字段标签
		$options = Arr::get($this->form_options(), $field);// 获得文件上传的选项
		$expected_exts = Arr::get($options, 'expected_exts');// 获得文件类型限制
		$size = Arr::get($options, 'size');// 获得文件大小限制
			
		//遍历多个上传的文件
		for ($i = 0; $i < $multi->count_files(); $i++)
		{
			//1.1 获得校验器
			$validation = $multi->validation($i, $label, $expected_exts, $size);
			
			//1.2 校验
			if ($validation->check())
			{
				//2 保存上传文件
				$multi->save_ext($i, $this->file_code());
			}
			else
			{
				//记录失败的校验器
				$result = Validation::merge($result, $validation);
			}
		}
		
		// 抛出校验的异常
		if ($result) 
		{
			throw new Virtual_Validation_Exception($result->errors());
		}
	}
	
	/**
	 * 获得文件代码
	 * @return string
	 */
	public function file_code() 
	{
		return $this->object_name().'/'.$this->pk();
	}
	
	/**
	 * 获得关联的文件
	 * @return array
	 */
	public function files()
	{
		$query = array('code' => $this->file_code());
		return Mongo_Document::factory('File')->collection()->find($query);
	}
	
}

