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
	 * @var string|array
	 */
	private $internalWhereClause = [];

	/**
	 * @var array
	 */
	private $internalWhereBindValues = [];

	/**
	 * @var int
	 */
	private $bindPointer = 0;

	/**
	 * @var int
	 */
	private $internalLimit = 10;

	/**
	 * @var int
	 */
	private $internalOffset = 0;

	/**
	 * @var string
	 */
	private $internalSort = "id";

	/**
	 * @var string
	 */
	private $internalSortType = "ASC";

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
	 * @override {partial override}
	 * @fallback parent::__call
	 * @param string $methodName
	 * @param array  $parameters
	 * @return mixed
	 */
	public function __call($methodName, $parameters)
	{
		switch ($methodName) {
			case 'getFirstLink':
				return 'fisrt_link';
				break;
			case 'getLastLink':
				return 'last_link';
				break;
			case 'getNextLink':
				return 'next_link';
				break;
			case 'getBackLink':
				return 'back_link';
				break;
		}

		return parent::__call($methodName, $parameters);
	}

	/**
	 * @param string $field
	 * @param string $operation
	 * @param string $value
	 * @return void
	 */
	public function setInternalWhereClause(string $field, string $operation, string $value): void
	{
		$boundary = sprintf(":value_%d", $this->bindPointer);
		$this->internalWhereClause[$this->bindPointer] = [
			"boundary" => $boundary,
			"op" => $operation,
			"field" => $field
		];
		$this->internalWhereBindValues[$boundary] = $value;
		$this->bindPointer++;

		// // Debug here
		// dd($this->internalWhereClause, $this->internalWhereBindValues);
	}

	/**
	 * @param int $limit
	 * @return void
	 */
	public function setInternalLimit(int $limit): void
	{
		$this->internalLimit = $limit;
	}

	/**
	 * @param int $offset
	 * @return void
	 */
	public function setInternalOffset(int $offset): void
	{
		$this->internalOffset = $offset;
	}

	/**
	 * @param string $field
	 * @param string $sortType
	 * @return void
	 */
	public function setInternalSort(string $field, string $sortType): void
	{
		$this->internalSort = $field;
		$this->internalSortType = $sortType;
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	public function buildInternalWhereClause(): void
	{
		$clause = "WHERE ";
		foreach ($this->internalWhereClause as $key => $val) {
			switch ($val["op"]) {
				case 'like':
					$op = "LIKE";
					$this->internalWhereBindValues[$val["boundary"]] = 
						strpos($this->internalWhereBindValues[$val["boundary"]], "*") !== false ?
							str_replace("*", "%", 
								$this->internalWhereBindValues[$val["boundary"]]) :
							sprintf("%c%s%c", 
								"%", $this->internalWhereBindValues[$val["boundary"]], "%");
				break;
				case '!like':
					$op = "NOT LIKE";
					$this->internalWhereBindValues[$val["boundary"]] = 
						strpos($this->internalWhereBindValues[$val["boundary"]], "*") !== false ?
							str_replace("*", "%", 
								$this->internalWhereBindValues[$val["boundary"]]) :
							sprintf("%c%s%c", 
								"%", $this->internalWhereBindValues[$val["boundary"]], "%");
				break;
				case 'is': $op = "="; break;
				case '!is': $op = "!="; break;
				case 'in': 
					$op = "IN";
				break;
				case '!in':
					$op = "NOT IN";
				break;
				default:
					throw new Exception("Invalid operation {$val["op"]}");
					break;
			}
			$clause .= sprintf(" (%s %s %s) AND", $val["field"], $op, $val["boundary"]);
		}
		$this->internalWhereClause = rtrim($clause, " AND");
	}

	/**
	 * @return int
	 */
	public function getTotalChecklist(): int
	{
		$query = sprintf(
			"SELECT COUNT(1) FROM checklists %s ORDER BY %s %s LIMIT %s OFFSET %s",
			(is_string($this->internalWhereClause) ? $this->internalWhereClause : ""),
			$this->internalSort,
			$this->internalSortType,
			$this->internalLimit,
			$this->internalOffset
		);

		// // Debug here
		// dd($query);

		$pdo = DB::getPdo();
		$st = $pdo->prepare($query);
		$st->execute($this->internalWhereBindValues);
		if ($st = $st->fetch(PDO::FETCH_NUM)) {
			return $st[0];
		}
		return 0;
	}

	/**
	 * @return array
	 */
	public function getListOfChecklists(): array
	{
		$query = sprintf(
			"SELECT * FROM checklists %s ORDER BY %s %s LIMIT %s OFFSET %s",
			(is_string($this->internalWhereClause) ? $this->internalWhereClause : ""),
			$this->internalSort,
			$this->internalSortType,
			$this->internalLimit,
			$this->internalOffset
		);
		

		// // Debug here
		// dd($query);

		$pdo = DB::getPdo();
		$st = $pdo->prepare($query);
		$st->execute();
		$ret = [];
		$retPtr = 0;

		__internal_pdo_fetch:
		if ($r = $st->fetch(PDO::FETCH_ASSOC)) {

			// ISO 8601
			foreach (["due", "created_at", "updated_at"] as $key) {
				is_string($r[$key]) and $r[$key] = date('c', strtotime($r[$key]));
			}

			$ret[$retPtr] = [
				"type" => "checklists",
				"id" => $r["id"],
				"attributes" => $r,
				"links" => [
					"self" => sprintf("%s/api/v1/checklists/%s", env("APP_URL"), $r["id"])
				]
			];

			unset($ret[$retPtr]["attributes"]["id"]);

			$retPtr++;
			goto __internal_pdo_fetch;
		}

		// // Debug here
		// dd($ret);

		return $ret;
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
