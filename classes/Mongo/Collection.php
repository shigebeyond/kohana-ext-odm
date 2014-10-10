<?php

class Mongo_Collection extends Krishna_Mongo_Collection 
{
	/**
	 * 查询全部: 连带查询关联的对象
	 *
	 * @param array $query 查询参数
	 * @param array $columns 要连带查询的关联对象列
	 * @return array
	 */
	public function find_include($query = array(), array $columns = NULL)
	{
		//TODO：实现关联查询 参考ORM
		return $this->find($query);
	}
	
	/**
	 * 查询出下拉框的选项列表
	 *
	 * @param array $query 查询参数
	 * @param string $key 选项值/数组的key
	 * @param string $value 选项名/数组的value
	 * @return array
	 */
	public function findList($query = array(), $key = NULL, $value = NULL)
	{
		if ($key === NULL)
		{
			$key = '_id';
		}
	
		$items = $this->find($query);
		 
		if ($value === NULL OR !preg_match("/[^_\w]/", $value))
		{
			return Arr::as_list($items, $key, $value);
		}
		 
		return $items;
	}
}
