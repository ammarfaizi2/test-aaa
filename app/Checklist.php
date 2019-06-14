<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'object_domain', 'object_id', 'due', 'urgency', 'description', 'items', 'task_id' 
	];

	protected $hidden = [
		"created_by"
	];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->attributes['created_by'] = Auth::user()->id;
	}

	/**
	 * @param string value
	 * @return string
	 */
	public function getDueAttribute(string $value): string
	{
		return date('c', strtotime($value));
	}

	/**
	 * @param string value
	 * @return string
	 */
	public function getUpdatedAtAttribute(string $value): string
	{
		return date('c', strtotime($value));
	}

	/**
	 * @param string value
	 * @return string
	 */
	public function getCreatedAtAttribute(string $value): string
	{
		return date('c', strtotime($value));
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setDueAttribute(string $value): void
	{
		$this->attributes['due'] = date("Y-m-d H:i:s", strtotime($value));
	}
}
