<?php defined('SYSPATH') OR die('No direct access allowed.');

class Mongo_Document_MPTT extends Krishna_Mongo_Document_MPTT 
{
	/**
	 * 扩展save方法: 如果涉及到父节点的修改, 则需要移动或制造根节点
	 *
	 * @param   Validation $validation  Validation object
	 * @param   array $columns
	 * @param   array|bool  $options  Insert options
	 * @return  mixed
	 */
	public function save_ext(Validation $validation = NULL, array $columns = NULL, $options = TRUE)
	{
		if ($this->loaded()) // update: maybe cause move
		{
			if(isset($this->_changed[$this->parent_column])) //If parent_id is changed, then move
			{
				$parent_id = $this->{$this->parent_column};
				
				// first recover its parent_id and save it
				$this->{$this->parent_column} = $this->_original[$this->parent_column];
				parent::save($validation, $options);
				
				// then move it
				return $this->move_to($parent_id, 'last');
			}
			else //If not, just save
			{
				return parent::save($validation, $options);
			}
		}
		else // create: maybe cause new_root
		{
			if (isset($this->{$this->parent_column})) // If it has a parent, just save
			{
				return $this->create_at($this->{$this->parent_column}, 'last', $validation, $options);
			}
			else // If not(it has no parent), the it's root
			{
				return $this->make_root($validation, NULL, $options);
			}
		}
		
	}
	
	/**
	 * Create a new term in the tree as a child of $parent
	 *
	 * - if `$location` is "first" or "last" the term will be the first or last child
	 * - if `$location` is an int, the term will be the next sibling of term with id $location
	 *
	 * @param   ORM_MPTT|integer  $parent    The parent
	 * @param   string|integer    $location  The location [Optional]
	 * @param   Validation $validation  Validation object
	 * @param   array|bool  $options  Insert options
	 * @return  Model_MPTT
	 * @throws  Krishna_Exception
	 */
	public function create_at($parent, $location = 'last', Validation $validation = NULL, $options = TRUE)
	{
		// Create the term as first child, last child, or as next sibling based on location
		if ($location == 'first')
		{
			$this->insert_as_first_child($parent, $validation, $options);
		}
		else if ($location == 'last')
		{
			$this->insert_as_last_child($parent, $validation, $options);
		}
		else
		{
			$target = self::factory($this->object_name(), $location);
				
			if ( ! $target->loaded())
			{
				throw new Krishna_Exception("Could not create {$this->object_name()}, could not find target for
					insert_as_next_sibling id: " . $location);
			}
	
			$this->insert_as_next_sibling($target, $validation, $options);
		}
	
		return $this;
	}
	
	/**
	* Move the item to $target based on action
	*
	* @param   $target  integer  The target term id
	* @param   $action  string   The action to perform (before/after/first/last) after
	* @throws  Krishna_Exception
	*/
	public function move_to($target, $action = 'after')
	{
		// Find the target
		$target = self::factory($this->object_name(), $target);

		// Make sure it exists
		if ( ! $target->load())
		{
			throw new Krishna_Exception("Could not move item, target item did not exist." . $target->id);
		}
	
		switch ($action)
		{
			case 'before':
				$this->move_to_prev_sibling($target);
				break;
				
			case 'after':
				$this->move_to_next_sibling($target);
				break;
	
			case 'first':
				$this->move_to_first_child($target);
				break;
				
			case 'last':
				$this->move_to_last_child($target);
				break;
				
			default:
				throw new Krishna_Exception("Could not move item, action should be 'before', 'after', 'first' or 'last'.");
		}
	}
} 
