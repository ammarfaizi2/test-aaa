<?php

namespace Tests\Utils;

use Closure;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @package \Tests\Utils
 */
trait DataRules
{
	/**
	 * @param array $rules
	 * @return bool
	 */
	private function assertRules(array $array, array $rules): bool
	{
		foreach ($rules as $key => $rule) {
			$key = explode('.', $key);
			$ptrVal = null;
			$this->assertTrue((bool)$this->internalIndexCheck($array, $key, $ptrVal));
			if (is_array($rule) && ($rule[1] instanceof Closure)) {
				$this->assertTrue((bool)$rule[1]($ptrVal));
				$rule = $rule[0];
			}

			switch ($rule) {
				case 'numeric':
					$callback = 'is_numeric';
					break;
				
				default:
					$callback = function ($value) use ($rule, $key, $ptrVal) {

						// // Debug here
						// if (gettype($value) !== $rule) {
						// 	dd(gettype($value), $rule, $key, $ptrVal);
						// }

						if (is_null($rule)) {
							return true;
						}

						return gettype($value) === $rule;
					};
					break;
			}
			$this->assertTrue((bool)$callback($ptrVal));
		}
		return true;
	}

	/**
	 * @param array $array
	 * @param array $indexes
	 * @param mixed $ptrVal
	 * @return bool
	 */
	private function internalIndexCheck(array $array, array $indexes, &$ptrVal): bool
	{
		while (isset($indexes[0]) && array_key_exists($indexes[0], $array)) {
			$array = $array[$indexes[0]];
			array_shift($indexes);
		}

		if ($ret = (sizeof($indexes) === 0)) {
			$ptrVal = $array;
		}

		return $ret;
	}
}
