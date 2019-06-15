<?php

namespace App;

use Auth;
use Exception;
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

	protected $primaryKey = "id";

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$user = Auth::user();

		if (!isset($user->id)) {
			throw new Exception("Couldn't resolve the authenticated user_id");
		}

		$this->attributes['created_by'] = $user->id;
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

	/**
	 * @return int|null
	 */
	public function getUpdatedByAttribute(): ?int
	{
		return null;
	}

	/**
	 * @return string|null
	 */
	public function getCompletedAtAttribute(): ?string
	{
		return null;
	}

	/**
	 * @return string|null
	 */
	public function getLastUpdateByAttribute(): ?string
	{
		return $this->getUpdatedByAttribute();
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public function getIsCompletedAttribute(): bool
	{
		return false;
	}

	/**
	 * @return object
	 */
	public function items()
	{
		return $this->hasMany('App\Item');
	}
}
