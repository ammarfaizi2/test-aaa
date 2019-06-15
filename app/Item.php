<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'checklist_id', 'name', 'due', 'urgency', 'assignee_id', 'task_id'
	];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setDueAttribute(string $value): void
	{
		$this->attributes['due'] = date("Y-m-d H:i:s", strtotime($value));
	}

	/**
	 * @return object
	 */
	public function items()
	{
		return $this->hasMany('App\Item');
	}
}
