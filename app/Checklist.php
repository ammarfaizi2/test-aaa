<?php

namespace App;

use DB;
use PDO;
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
	 * @param string
	 */
	public function setInternalWhereClause()
	{
	}

	/**
	 * @return int
	 */
	public function getTotalChecklist(): int
	{
		$pdo = DB::getPdo();
		$st = $pdo->prepare("SELECT COUNT(1) FROM checklists");
		$st->execute();
		if ($st = $st->fetch(PDO::FETCH_NUM)) {
			return $st[0];
		}
		return 0;
	}

	/**
	 * @override {partial override} parent::__call
	 * @param string $methodName
	 * @param array  $parameters
	 * @return mixed
	 */
	public function __call(string $methodName, array $parameters)
	{

		switch ($methodName) {
			case 'getFirstLink':
				return 'fisrt_link';
				break;
			case 'getLastLink':

				break;
			case 'getNextLink':

				break;
			case 'getBackLink':

				break;
		}

		return parent::__call($methodName, $parameters);
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
