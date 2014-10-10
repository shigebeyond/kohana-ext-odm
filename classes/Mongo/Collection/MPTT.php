<?php defined('SYSPATH') OR die('No direct access allowed.');

class Mongo_Collection_MPTT extends Mongo_Collection {
	
	/**
	 * Left column name
	 * @var string
	 */
	public $left_column = 'lft';

	/**
	 * Level column name
	 * @var string
	 */
	public $level_column = 'lvl';

	/**
	 * Scope column name
	 * @var string
	 */
	public $scope_column = 'scp';
	
	/**
	 * Reset the state of the query (must be called manually if re-using a collection for a new query)
	 *
	 * @param bool $cursor_only
	 * @return  Mongo_Collection
	 */
	public function reset($cursor_only = FALSE)
	{
		parent::reset($cursor_only);
		
		//sort by scope and left columns
		$this->sort_asc($this->scope_column)->sort_asc($this->left_column);
		
		return $this;
	}
	
	/**
	 * Overloads the find_list method to
	 * support indenting.
	 *
	 * @param array $query 查询参数
	 * @param string $key first table column.
	 * @param string $val second table column.
	 * @param string $indent character used for indenting.
	 * @return array
	 */
	public function findList($query = array(), $key = NULL, $val = NULL, $indent = '--', $as_obj = FALSE)
	{
		if (is_string($indent))
		{
			if ($key === NULL)
			{
				// Use the default key
				$key = '_id';
			}
	
			$result = $this->find($query);
	
			$array = array();
	
			if ($as_obj)
			{
				foreach ($result as $row)
				{
					$row->$val = str_repeat($indent, $row->{$this->level_column}).$row->$val;
					$array[(string)$row->$key] = $row;
				}
			}
			else
			{
				foreach ($result as $row)
				{
					$array[(string)$row->$key] = str_repeat($indent, $row->{$this->level_column}).$row->$val;
				}
			}
	
			return $array;
		}
	
		return parent::findList($query, $key, $val);
	}
	
	/**
	 * 连带查询关联对象
	 *
	 * @param array $query 查询参数
	 * @param string $key first table column.
	 * @param string $val second table column.
	 * @param string $indent character used for indenting.
	 * @param array $columns 关联查询的字段
	 * @return array
	 */
	public function findList_include($query = array(), $key = NULL, $val = NULL, $indent = '--', array $columns = NULL)
	{
		//TODO：实现关联查询 参考Krishna_ORM_MPTT
		// 获得所有对象
		return $this->findList ($query, $key, $val, $indent, TRUE);//$as_obj为真
	}
}